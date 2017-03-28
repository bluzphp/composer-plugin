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

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    const PERMISSION_CODE = 0755;
    const REPEAT = 5;

    /**
     * @var Installer
     */
    protected $installer;

    /**
     * @var string
     */
    protected $environment;

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
        return [
            // copy files to working directory
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
            // removed unchanged files
            PackageEvents::PRE_PACKAGE_UPDATE => 'onPrePackageUpdate',
            // copy new files
            PackageEvents::POST_PACKAGE_UPDATE => 'onPostPackageUpdate',
            // removed all files
            PackageEvents::PRE_PACKAGE_UNINSTALL => 'onPrePackageRemove'
        ];
    }

    /**
     * Hook which is called after install package
     *
     * It copies bluz module
     */
    public function onPostPackageInstall()
    {
        if (file_exists($this->installer->getVendorPath())) {
            $this->copy();
        }
    }

    /**
     * Hook which is called before update package
     *
     * It checks bluz module
     */
    public function onPrePackageUpdate()
    {
        if (file_exists($this->installer->getVendorPath())) {
            $this->remove();
        }
    }

    /**
     * Hook which is called after update package
     *
     * It copies bluz module
     */
    public function onPostPackageUpdate()
    {
        if (file_exists($this->installer->getVendorPath())) {
            $this->copy();
        }
    }

    /**
     * Hook which is called before remove package
     *
     * It removes bluz module
     */
    public function onPrePackageRemove()
    {
        if (file_exists($this->installer->getVendorPath())) {
            $this->remove();
        }
    }

    /**
     * It recursively copies the files and directories
     * @return bool
     */
    protected function copy()
    {
        $this->copyRecursive('application');
        $this->copyRecursive('data');
        $this->copyRecursive('public');
        $this->copyRecursive('tests');
    }

    /**
     * It recursively copies the files and directories
     * @param $directory
     * @return bool
     */
    protected function copyRecursive($directory)
    {
        $sourcePath = $this->installer->getVendorPath() . DS . $directory;

        if (!is_dir($sourcePath)) {
            return false;
        }

        foreach ($iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $sourcePath,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        ) as $item) {
            $filePath = PATH_ROOT . DS . $directory . DS . $iterator->getSubPathName();

            if ($item->isDir()) {
                if (is_dir($filePath)) {
                    $this->installer->getIo()->write(
                        "    - <comment>Directory `{$iterator->getSubPathName()}` already exists</comment>",
                        true,
                        IOInterface::VERBOSE
                    );
                } else {
                    mkdir($filePath, self::PERMISSION_CODE);
                    $this->installer->getIo()->write(
                        "    - Created directory `{$iterator->getSubPathName()}`",
                        true,
                        IOInterface::VERBOSE
                    );
                }
            } else {
                if (file_exists($filePath)) {
                    $this->installer->getIo()->write(
                        "    - <comment>File `{$iterator->getSubPathName()}` already exists</comment>",
                        true,
                        IOInterface::VERBOSE
                    );
                } else {
                    copy($item, $filePath);
                    $this->installer->getIo()->write(
                        "    - Copied file `{$iterator->getSubPathName()}`",
                        true,
                        IOInterface::VERBOSE
                    );
                }
            }
        }

        return true;
    }

    /**
     * It recursively removes the files and empty directories
     * @return bool
     */
    protected function remove()
    {
        $this->removeRecursive('application');
        $this->removeRecursive('data');
        $this->removeRecursive('public');
        $this->removeRecursive('tests');
    }

    /**
     * It recursively removes the files and directories
     * @param $directory
     * @return bool
     */
    protected function removeRecursive($directory)
    {
        $sourcePath = $this->installer->getVendorPath() . DS . $directory;

        if (!is_dir($sourcePath)) {
            return false;
        }
        foreach ($iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $sourcePath,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $item) {
            // path to copied file
            $current = PATH_ROOT . DS . $directory . DS . $iterator->getSubPathName();

            // remove empty directories
            if (is_dir($current)) {
                if (sizeof(scandir($current)) == 2) {
                    rmdir($current);
                    $this->installer->getIo()->write(
                        "    - Removed directory `{$iterator->getSubPathName()}`",
                        true,
                        IOInterface::VERBOSE
                    );
                } else {
                    $this->installer->getIo()->write(
                        "    - <comment>Skip directory `{$iterator->getSubPathName()}`</comment>",
                        true,
                        IOInterface::VERBOSE
                    );
                }
                continue;
            }

            // skip already removed files
            if (!is_file($current)) {
                continue;
            }

            if (md5_file($item) == md5_file($current)) {
                // remove file
                unlink($current);
                $this->installer->getIo()->write(
                    "    - Removed file `{$iterator->getSubPathName()}`",
                    true,
                    IOInterface::VERBOSE
                );
            } else {
                // or skip changed files
                $this->installer->getIo()->write(
                    "    - <comment>File `{$iterator->getSubPathName()}` has changed</comment>",
                    true,
                    IOInterface::VERBOSE
                );
            }
        }

        return false;
    }
}
