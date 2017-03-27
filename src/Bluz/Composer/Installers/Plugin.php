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
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

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
        $result = [
            // copy files to working directory
            ScriptEvents::POST_PACKAGE_INSTALL => [
                ['onPostPackageInstall', 0]
            ],
            // check changes
            ScriptEvents::PRE_PACKAGE_UPDATE  => [
                ['onPrePackageUpdate', 0]
            ],
            // if working files is changed skip this step
            // else copy files to working directory
            ScriptEvents::POST_PACKAGE_UPDATE  => [
                ['onPostPackageUpdate', 0]
            ],
            // removed all files
            ScriptEvents::PRE_PACKAGE_UNINSTALL  => [
                ['onPrePackageUninstall', 0]
            ]
        ];

        return $result;
    }

    /**
     * Hook which is called after install package
     *
     * It copies bluz module
     */
    public function onPostPackageInstall()
    {
        $this->copy();
    }

    /**
     * Hook which is called before update package
     *
     * It copies bluz module
     */
    public function onPrePackageUpdate()
    {
        $this->check();
    }

    /**
     * Hook which is called after update package
     *
     * It copies bluz module
     */
    public function onPostPackageUpdate()
    {
        $this->copy();
    }

    /**
     * Hook which is called before remove package
     *
     * It copies bluz module
     */
    public function onPrePackageRemove()
    {
        $this->remove();
    }

    /**
     * It recursively copies the files and directories
     * @return bool
     */
    protected function copy()
    {
        foreach ($iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->installer->getOption('vendorPath'),
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        ) as $item) {
            $filePath = PATH_ROOT . DS . $iterator->getSubPathName();

            if (file_exists($filePath)) {
                $this->installer->getIo()->write("File {$iterator->getSubPathName()} already exists");
            } else {
                if ($item->isDir()) {
                    mkdir($filePath, self::PERMISSION_CODE);
                } else {
                    copy($item, $filePath);
                }
            }
        }
        return true;
    }

    /**
     * It recursively check changes in the files
     * @return bool
     */
    protected function check()
    {
        foreach ($iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->installer->getOption('vendorPath'),
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        ) as $item) {
            $this->installer->getIo()->write($iterator->getSubPathName());
            $this->installer->getIo()->write(PATH_ROOT . DS . $iterator->getSubPathName());

        }

        return false;
    }

    /**
     * It recursively removes the files and directories
     * @return bool
     */
    protected function remove()
    {
        foreach ($iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->installer->getOption('vendorPath'),
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        ) as $item) {
            $this->installer->getIo()->write($iterator->getSubPathName());
            $this->installer->getIo()->write(PATH_ROOT . DS . $iterator->getSubPathName());

        }

        return false;
    }
}
