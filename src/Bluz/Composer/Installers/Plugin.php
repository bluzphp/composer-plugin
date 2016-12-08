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

use Bluz\Composer\Helper\PathHelper;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Installer
     */
    protected $installer;

    protected $environment;

    const PERMISSION_CODE = 0755;
    const REPEAT = 5;

    /**
     * @var PathHelper
     */
    protected $pathHelper;

    /**
     * Create instance, define constants
     */
    public function __construct()
    {
        defined('PATH_ROOT') ? : define('PATH_ROOT', realpath($_SERVER['DOCUMENT_ROOT']));
        defined('DS') ? : define('DS', DIRECTORY_SEPARATOR);
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
        $this->installer = new Installer($io, $composer);
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
            $this->installer->getOption('module_name')
        );

        $this->copyFolders();
    }
    /**
     * Hook which is called after removing package
     *
     * It removes bluz module
     */
    public function onPostPackageUninstall()
    {
        $this->pathHelper = new PathHelper(
            $this->installer->getOption('module_name')
        );

        if (file_exists(
            $this->getPathHelper()->getModulesPath() . DS .
            $this->installer->getOption('module_name')
        )) {
            $this->removeModule();
            $this->removeTests();
            $this->removeAssetsFiles();

            if ($this->installer->getOption('required_models')) {
                $this->removeModels();
            }
        }
    }

    protected function executeMigrations(string $command, string $version = null)
    {
        $this->setEnvironment();

        $migrationCommand = $this->getPathHelper()->getPhinxPath() . $command . '  -e default';
        $migrationCommand .= $version ? (' -t ' . $version) : '';

        $this->installer->getIo()->write($migrationCommand);
        $this->installer->getIo()->write(shell_exec($migrationCommand));
    }

    /**
     *  It set environment
     */
    protected function setEnvironment()
    {
        $environment = !empty(getenv('BLUZ_ENV')) ? getenv('BLUZ_ENV') : $this->installer->getIo()
            ->ask('    <info>Please, enter  your environment[dev, production, testing or another]</info> ', '!');

        $this->environment = $environment;
    }

    /**
     * It copies all folders
     */
    protected function copyFolders()
    {
        if (file_exists($this->installer->getOption('vendorPath'))) {
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
        $srcDir = $this->installer->getOption('vendorPath') . DS . 'src';

        if (file_exists($srcDir)) {
            $handle = opendir($srcDir);

            while ($fileName = readdir($handle)) {
                $realPath = $srcDir . DS . $fileName;

                if (is_dir($realPath)) {
                    switch ($fileName) {
                        case 'modules':
                            $this->copy(
                                $realPath,
                                $this->pathHelper->getModulesPath()
                            );
                            break;
                        case 'models':
                            $this->copy(
                                $realPath,
                                $this->pathHelper->getModelsPath()
                            );
                            break;
                        case 'migrations':
                            $this->copy(
                                $realPath,
                                $this->pathHelper->getMigrationsPath()
                            );

                            $this->executeMigrations('migrate');
                            break;
                    }
                }
            }
        }
    }

    /**
     * Copy tests of module
     */
    protected function copyTests()
    {
        $testsPath = $this->installer->getOption('vendorPath') . DS . 'tests';

        if (file_exists($testsPath)) {
            $handle = opendir($testsPath);

            while ($fileName = readdir($handle)) {
                $realPath = $testsPath . DS . $fileName;

                if (file_exists($realPath)) {
                    switch ($fileName) {
                        case 'modules':
                            $this->copy(
                                $realPath,
                                $this->pathHelper->getTestModulesPath()
                            );
                            break;
                        case 'models':
                            $this->copy(
                                $realPath,
                                $this->pathHelper->getTestModelsPath()
                            );
                            break;
                        case 'postman':
                            if (!file_exists($this->pathHelper->getTestsPostmanPath() . DS .
                                $this->pathHelper->getModuleName())) {
                                mkdir($this->pathHelper->getTestsPostmanPath() . DS .
                                    $this->pathHelper->getModuleName(), self::PERMISSION_CODE);
                            }
                            $this->copy(
                                $realPath,
                                $this->pathHelper->getTestsPostmanPath()
                            );
                    }
                }
            }
        }
    }

    /**
     * It copies assets files of module
     */
    protected function copyAssets()
    {
        $assetsPath = $this->installer->getOption('vendorPath') . DS . 'assets';

        if (file_exists($assetsPath)) {
            $handle = opendir($assetsPath);

            while ($fileName = readdir($handle)) {
                if ($fileName != "." && $fileName != "..") {
                    $this->copy(
                        $assetsPath . DS . $fileName,
                        $this->getPathHelper()->getPublicPath() . DS . $fileName . DS .
                        $this->getPathHelper()->getModuleName()
                    );
                }
            }

        }
    }

    /**
     * It removes models of module
     */
    protected function removeModels()
    {
        $modelNames = explode(',', $this->installer->getOption('required_models'));

        foreach ($modelNames as $name) {
            $this->remove($this->getPathHelper()->getModelsPath() . DS . ucfirst(trim($name)));
        }
    }

    /**
     * It removes tests of module
     */
    protected function removeTests()
    {
        $modelNames = explode(',', $this->installer->getOption('required_models'));

        if (empty($modelNames)) {
            $this->remove(
                $this->getPathHelper()->getTestModelsPath() . DS .
                ucfirst(trim($this->getPathHelper()->getModuleName()))
            );
        }

        foreach ($modelNames as $name) {
            $this->remove($this->getPathHelper()->getTestModelsPath() . DS . ucfirst(trim($name)));
        }

        $this->remove(
            $this->getPathHelper()->getTestModulesPath() . DS .
            $this->getPathHelper()->getModuleName()
        );

        if (file_exists(
            $this->pathHelper->getTestsPostmanPath() . DS . $this->pathHelper->getModuleName()
        )) {
            $this->remove(
                $this->pathHelper->getTestsPostmanPath() . DS . $this->pathHelper->getModuleName()
            );
        }
    }

    /**
     * It removes assets files of module
     */
    protected function removeAssetsFiles()
    {
        $this->remove($this->getPathHelper()->getJsFilesPath());
        $this->remove($this->getPathHelper()->getCssFilesPath());
        $this->remove($this->getPathHelper()->getFontsFilesPath());
    }

    /**
     * It removes controllers and views of module
     */
    protected function removeModule()
    {
        $this->remove($this->getPathHelper()->getModulePath());
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
        if (!file_exists($dest)) {
            mkdir($dest, self::PERMISSION_CODE);
        }

        foreach ($iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        ) as $item) {
            $filePath = $dest . DS . $iterator->getSubPathName();

            if (!file_exists($filePath)) {
                if ($item->isDir()) {
                    mkdir($filePath);
                } else {
                    copy($item, $filePath);
                }
            }
        }
    }

    /**
     * It recursively removes the files and directories
     */
    public function remove(string $path)
    {
        if (is_file($path)) {
            return unlink($path);
        }

        if (is_dir($path)) {
            foreach ($iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            ) as $item) {
                if ($item->isDir()) {
                    rmdir($item->getRealPath());
                } else {
                    unlink($item->getRealPath());
                }
            }
            rmdir($path);
        }
    }
}
