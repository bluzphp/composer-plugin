<?php

namespace Bluz\Composer\Installers;

use Bluz\Composer\Config\ConfigDb;
use Bluz\Composer\Helper\PathHelper;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\PackageEvent;
use Composer\Script\ScriptEvents;
use Exception;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Bluz\Application\Application as App;
use Bluz\Db\Db;
use Bluz\Proxy\Config;

class BluzModuleInstallerPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var BluzModuleInstaller
     */
    protected $installer;

    protected $db;

    const PERMISSION_CODE = 0755;
    const REPEAT = 5;
    const SKIP_MODELS = [
        'auth'
    ];

    protected $app = null;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var PathHelper
     */
    protected $pathHelper;

    /**
     * @var PDO
     */
    protected $connection;

    public function __construct()
    {
        $this->filesystem = new Filesystem();

        defined('ROOT_PATH') ? : define('ROOT_PATH', realpath($_SERVER['DOCUMENT_ROOT']));
        defined('DS') ? : define('DS', DIRECTORY_SEPARATOR);
        defined('PATH_APPLICATION') ? : define('PATH_APPLICATION', ROOT_PATH . '/application');
        defined('PATH_DATA') ? : define('PATH_DATA', ROOT_PATH . '/data');
        defined('PATH_ROOT') ? : define('PATH_ROOT', ROOT_PATH);
    }

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->installer = new BluzModuleInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($this->installer);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        $result = [
            ScriptEvents::POST_PACKAGE_INSTALL => [
                ['onPostPackageInstallOrUpdate', 0]
            ],
            ScriptEvents::POST_PACKAGE_UPDATE  => [
                ['onPostPackageInstallOrUpdate', 0]
            ],
            ScriptEvents::POST_PACKAGE_UNINSTALL  => [
                ['onPostPackageUninstall', 0]
            ]
        ];

        return $result;
    }

    public function onPostPackageInstallOrUpdate(PackageEvent $event)
    {
        $this->pathHelper = new PathHelper(
            $this->installer->getSetting('module_name')
        );

        $this->copyFolders();

        if (file_exists($this->getPathHelper()->getDumpPath())) {
            $this->execSqlScript();
        }
    }

    public function onPostPackageUninstall(PackageEvent $event)
    {
        $this->pathHelper = new PathHelper(
            $this->installer->getSetting('module_name')
        );

        if ($this->getFilesystem()->exists(
            $this->getPathHelper()->getModulesPath() . DS .
            $this->installer->getSetting('module_name'))
        ) {
            $this->removeModule();
            $this->removeTests();
            $this->removeAssetsFiles();

            if ($this->installer->getSetting('required_models')) {
                $this->removeModels();
                $this->removeTable();
            }
        }
    }

    protected function removeTable()
    {
        $repeat = self::REPEAT;
        $answer = null;

        $this->installer->getIo()->write(
            '    <info>' .
            'Removing `bluz-' . $this->installer->getSetting('module_name') . '-module` package' .
            '</info>'
        );
        while ($repeat) {
            $answer = $this->installer->getIo()
                ->ask(
                    '    <info>'.
                    'Do you want remove tables: ' .
                    $this->installer->getSetting('required_models') .
                    '[y, n]' .
                    '</info> ', '?'
                );

            switch ($answer) {
                case 'y':
                case 'n':
                    $repeat = false;
                    break;
                default :
                    $repeat--;
            }

        }

        if ($answer === 'y') {
            $tables = explode(',', $this->installer->getSetting('required_models'));

            foreach ($tables as $table) {
                $this->getDbConnection()->exec('DROP TABLE IF EXISTS ' . $table);
            }
        }
    }

    protected function initApplication()
    {
        $repeat = self::REPEAT;

        while ($repeat) {
            try {
                $environment = !empty(getenv('ENV')) ? getenv('ENV') : $this->installer->getIo()
                    ->ask('    <info>Please, enter  your environment[dev, production, testing or another]</info> ', '!');

                App::getInstance()->init($environment);

                $repeat = false;
            } catch (Exception $exception) {
                $this->installer->getIo()->writeError('<error>' . $exception->getMessage() . '</error>');
                --$repeat;
            }
        }
    }

    protected function copyFolders()
    {
        if (file_exists($this->installer->getSetting('vendorPath'))) {
            $this->copyModule();
            $this->copyAssets();
            $this->copyTests();
        }
    }

    protected function copyModule()
    {
        $finder = new Finder();

        $finder->in($this->installer->getSetting('vendorPath'))
            ->path('src')
            ->depth('== 1')
            ->ignoreUnreadableDirs();

        foreach ($finder as $file) {
            switch ($file->getBasename()) {
                case 'modules':
                    $this->copy(
                        $file->getRealPath(),
                        $this->getPathHelper()->getModulesPath()
                    );
                    break;
                case 'models':
                    $this->copy(
                        $file->getRealPath(),
                        $this->getPathHelper()->getModelsPath()
                    );
                    break;
            }
        }
    }

    public function getDbConnection()
    {
        if (empty($this->db)) {
            $this->initApplication();

            $db = new Db();

            $connectData = Config::getData('db', 'connect');
            $db->setConnect($connectData);
            $this->db = $db->handler();
        }

        return $this->db;
    }

    protected function execSqlScript()
    {
        if (!empty($this->getDbConnection())) {
            $dumpPath = $this->getPathHelper()->getDumpPath();

            if (is_file($dumpPath) && is_readable($dumpPath)) {
                $sql = file_get_contents($dumpPath);

                $this->getDbConnection()->exec($sql);
                $this->getFilesystem()->remove($dumpPath);
            }
        }
    }

    protected function copyTests()
    {
        $finder = new Finder();

        $finder->in($this->installer->getSetting('vendorPath'))
            ->path('tests')
            ->depth('== 1')
            ->ignoreUnreadableDirs();

        foreach ($finder as $file) {
            switch ($file->getBasename()) {
                case 'modules':
                    $this->copy($file->getRealPath(), $this->pathHelper->getTestModulesPath());
                    break;
                case 'models':
                    $this->copy($file->getRealPath(), $this->pathHelper->getTestModelsPath());
            }
        }
    }

    protected function copyAssets()
    {
        $finder = new Finder();
        $finder->directories()
            ->in($this->installer->getSetting('vendorPath'))
            ->path('assets')
            ->depth('== 1')
            ->ignoreUnreadableDirs();

        foreach ($finder as $file) {
            $this->copy(
                $file->getRealPath(),
                $this->getPathHelper()->getPublicPath() . DS . $file->getBasename() . DS .
                $this->getPathHelper()->getModuleName()
            );
        }
    }

    protected function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    protected function removeModels()
    {
        $modelNames = explode(',', $this->installer->getSetting('required_models'));

        foreach ($modelNames as $name) {
            $this->getFilesystem()->remove($this->getPathHelper()->getModelsPath() .DS .ucfirst(trim($name)));
        }
    }

    protected function removeTests()
    {
        $modelNames = explode(',', $this->installer->getSetting('required_models'));

        if (empty($modelNames)) {
            $this->getFilesystem()->remove(
                $this->getPathHelper()->getTestModelsPath() . DS .
                ucfirst(trim($this->getPathHelper()->getModuleName())));
        }

        foreach ($modelNames as $name) {
            $this->getFilesystem()->remove($this->getPathHelper()->getTestModelsPath() .DS .ucfirst(trim($name)));
        }
        $this->getFilesystem()->remove(
            $this->getPathHelper()->getTestModulesPath() . DS .
            $this->getPathHelper()->getModuleName()
        );
    }

    protected function removeAssetsFiles()
    {
        $this->getFilesystem()->remove($this->getPathHelper()->getJsFilesPath());
        $this->getFilesystem()->remove($this->getPathHelper()->getCssFilesPath());
        $this->getFilesystem()->remove($this->getPathHelper()->getFontsFilesPath());
    }

    protected function removeModule()
    {
        $this->getFilesystem()->remove($this->getPathHelper()->getModulePath());
    }

    public function getPathHelper(): PathHelper
    {
        return $this->pathHelper;
    }

    public function copy(string $source, string $dest)
    {
        $filesystem = $this->getFilesystem();

        if (!$filesystem->exists($dest)) {
            $filesystem->mkdir($dest, self::PERMISSION_CODE);
        }

        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
            if ($item->isDir()) {
                $filesystem->mkdir($dest . DS . $iterator->getSubPathName());
            } else {
                $filesystem->copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }
}
