<?php
/**
 * Bluz composer plugin
 *
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/composer-plugin
 */

/**
 * @namespace
 */
namespace Bluz\Composer\Installers;

use Bluz\Application\Application as App;
use Bluz\Composer\Helper\PathHelper;
use Bluz\Db\Db;
use Bluz\Proxy\Config;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

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
     * @var \PDO
     */
    protected $connection;

    /**
     * Create instance, define constants
     */
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
     * Called after the plugin is loaded
     *
     * It setup composer installer
     *
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->installer = new BluzModuleInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($this->installer);
    }

    /**
     * Registered events after the plugin is loaded
     *
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

    /**
     * Hook which is called after install or update package
     *
     * It copies bluz module
     */
    public function onPostPackageInstallOrUpdate()
    {
        $this->pathHelper = new PathHelper(
            $this->installer->getSetting('module_name')
        );

        $this->copyFolders();

        if (file_exists($this->getPathHelper()->getDumpPath())) {
            $this->execSqlScript();
        }
    }
    /**
     * Hook which is called after removing package
     *
     * It removes bluz module
     */
    public function onPostPackageUninstall()
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

    /**
     * It removes module tables
     */
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

    /**
     * Initializing the bluz application, set environment
     *
     * @throws \Exception if initialization failed.
     */
    protected function initApplication()
    {
        $repeat = self::REPEAT;

        while ($repeat) {
            try {
                $environment = !empty(getenv('ENV')) ? getenv('ENV') : $this->installer->getIo()
                    ->ask('    <info>Please, enter  your environment[dev, production, testing or another]</info> ', '!');

                App::getInstance()->init($environment);

                $repeat = false;
            } catch (\Exception $exception) {
                $this->installer->getIo()->writeError('<error>' . $exception->getMessage() . '</error>');
                --$repeat;
            }
        }
    }

    /**
     * It copies all folders
     */
    protected function copyFolders()
    {
        if (file_exists($this->installer->getSetting('vendorPath'))) {
            $this->copyModule();
            $this->copyAssets();
            $this->copyTests();
        }
    }

    /**
     * It copies module and models
     */
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

    /**
     * Get database connection
     */
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

    /**
     * It executes sql dump
     */
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

    /**
     * Copy tests of module
     */
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

    /**
     * It copies assets files of module
     */
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

    /**
     * Get fileSystem instance
     */
    protected function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * It removes models of module
     */
    protected function removeModels()
    {
        $modelNames = explode(',', $this->installer->getSetting('required_models'));

        foreach ($modelNames as $name) {
            $this->getFilesystem()->remove($this->getPathHelper()->getModelsPath() .DS .ucfirst(trim($name)));
        }
    }

    /**
     * It removes tests of module
     */
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

    /**
     * It removes assets files of module
     */
    protected function removeAssetsFiles()
    {
        $this->getFilesystem()->remove($this->getPathHelper()->getJsFilesPath());
        $this->getFilesystem()->remove($this->getPathHelper()->getCssFilesPath());
        $this->getFilesystem()->remove($this->getPathHelper()->getFontsFilesPath());
    }

    /**
     * It removes controllers and views of module
     */
    protected function removeModule()
    {
        $this->getFilesystem()->remove($this->getPathHelper()->getModulePath());
    }

    /**
     * Get pathHelper object
     */
    public function getPathHelper(): PathHelper
    {
        return $this->pathHelper;
    }

    /**
     * It recursively copies the files and directories
     */
    public function copy(string $source, string $dest)
    {
        $filesystem = $this->getFilesystem();

        if (!$filesystem->exists($dest)) {
            $filesystem->mkdir($dest, self::PERMISSION_CODE);
        }

        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
            if ($item->isDir()) {
                $filesystem->mkdir($dest . DS . $iterator->getSubPathName());
            } else {
                $filesystem->copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }
}
