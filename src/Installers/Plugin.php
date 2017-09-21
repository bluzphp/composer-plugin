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
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class Plugin
 *
 * @package Bluz\Composer\Installers
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    const PERMISSION_CODE = 0755;
    const REPEAT = 5;
    const DIRECTORIES = [
        'application',
        'data',
        'public',
        'tests'
    ];

    /**
     * @var Installer
     */
    protected $installer;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Create instance, define constants
     */
    public function __construct()
    {
        defined('PATH_ROOT') ?: define('PATH_ROOT', realpath($_SERVER['DOCUMENT_ROOT']));
        defined('DS') ?: define('DS', DIRECTORY_SEPARATOR);
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
            PackageEvents::POST_PACKAGE_INSTALL => 'copyFiles',
            // copy new files
            PackageEvents::POST_PACKAGE_UPDATE => 'copyFiles',
            // removed unchanged files
            PackageEvents::PRE_PACKAGE_UPDATE => 'removeFiles',
            // removed all files
            PackageEvents::PRE_PACKAGE_UNINSTALL => 'removeFiles',
            // copy extra files from root composer.json
            ScriptEvents::POST_UPDATE_CMD => 'copyExtraFiles',
            // remove extra files from root composer.json
            // ScriptEvents::PRE_UPDATE_CMD => 'removeExtraFiles'
        ];
    }


    /**
     * Hook which is called after install package
     * It copies bluz module
     *
     * @throws \InvalidArgumentException
     */
    public function copyFiles()
    {
        if (file_exists($this->installer->getVendorPath())) {
            $this->copyModule();
        }
    }

    /**
     * Hook which is called before update package
     * It checks bluz module
     */
    public function removeFiles()
    {
        if (file_exists($this->installer->getVendorPath())) {
            $this->removeModule();
        }
    }

    /**
     * Copy extra files from compose.json of project
     *
     * @param Event $event
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function copyExtraFiles(Event $event)
    {
        $extras = $event->getComposer()->getPackage()->getExtra();
        if (array_key_exists('copy-files', $extras)) {
            $this->installer->getIo()->write(
                sprintf('  - Copied additional file(s)'),
                true
            );
            $this->copyExtras($extras['copy-files']);
        }
    }

    /**
     * Remove extra files from compose.json of project
     *
     * @param Event $event
     *
     * @return void
     */
    public function removeExtraFiles(Event $event)
    {
        $extras = $event->getComposer()->getPackage()->getExtra();
        if (array_key_exists('copy-files', $extras)) {
            $this->removeExtras($extras['copy-files']);
        }
    }

    /**
     * Get Filesystem
     *
     * @return Filesystem
     */
    protected function getFilesystem()
    {
        if (!$this->filesystem) {
            $this->filesystem = new Filesystem();
        }
        return $this->filesystem;
    }

    /**
     * getExtra
     *
     * @return array
     */
    protected function getExtraFiles() : array
    {
        $moduleJson = json_decode(file_get_contents($this->installer->getVendorPath() . DS . 'composer.json'), true);

        if (isset($moduleJson, $moduleJson['extra'], $moduleJson['extra']['copy-files'])) {
            return $moduleJson['extra']['copy-files'];
        }
        return [];
    }

    /**
     * Copy Module files
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function copyModule()
    {
        $this->copyExtras($this->getExtraFiles());

        foreach (self::DIRECTORIES as $directory) {
            $this->copy(
                $this->installer->getVendorPath() . DS . $directory . DS,
                PATH_ROOT . DS . $directory . DS
            );
        }

        $this->installer->getIo()->write(
            sprintf(
                '  - Copied <comment>%s</comment> module to application',
                basename($this->installer->getVendorPath())
            ),
            true
        );
    }

    /**
     * copyExtras
     *
     * @param  array $files
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function copyExtras($files)
    {
        foreach ($files as $source => $target) {
            $this->copy(
                dirname($this->installer->getVendorPath(), 2) . DS . $source,
                PATH_ROOT . DS . $target
            );
        }
    }

    /**
     * It recursively copies the files and directories
     *
     * @param $source
     * @param $target
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function copy($source, $target)
    {
        // skip, if not exists
        if (!file_exists($source)) {
            return;
        }
        // skip, if target exists
        if (is_file($target)) {
            $this->installer->getIo()->write(
                sprintf('  - File <comment>%s</comment> already exists', $target),
                true,
                IOInterface::VERBOSE
            );
            return;
        }

        // Check the renaming of file for direct moving (file-to-file)
        $isRenameFile = substr($target, -1) !== '/' && !is_dir($source);

        if (file_exists($target) && !is_dir($target) && !$isRenameFile) {
            throw new \InvalidArgumentException('Destination directory is not a directory');
        }

        try {
            if ($isRenameFile) {
                $this->getFilesystem()->mkdir(dirname($target));
            } else {
                $this->getFilesystem()->mkdir($target);
            }
        } catch (IOException $e) {
            throw new \InvalidArgumentException(
                sprintf('Could not create directory `%s`', $target)
            );
        }

        if (false === file_exists($source)) {
            throw new \InvalidArgumentException(
                sprintf('Source directory or file `%s` does not exist', $source)
            );
        }

        if (is_dir($source)) {
            $finder = new Finder;
            $finder->files()->in($source);

            foreach ($finder as $file) {
                try {
                    $this->getFilesystem()->copy($file, $target . DS . $file->getRelativePathname());
                } catch (IOException $e) {
                    throw new \InvalidArgumentException(
                        sprintf('Could not copy `%s`', $file->getBaseName())
                    );
                }
            }
        } else {
            try {
                if ($isRenameFile) {
                    $this->getFilesystem()->copy($source, $target);
                } else {
                    $this->getFilesystem()->copy($source, $target . '/' . basename($source));
                }
            } catch (IOException $e) {
                throw new \InvalidArgumentException(sprintf('Could not copy `%s`', $source));
            }
        }

        $this->installer->getIo()->write(
            sprintf('  - Copied file(s) from <comment>%s</comment> to <comment>%s</comment>', $source, $target),
            true,
            IOInterface::VERBOSE
        );
    }

    /**
     * It recursively removes the files and empty directories
     * @return void
     */
    protected function removeModule()
    {
        $this->removeExtras($this->getExtraFiles());

        foreach (self::DIRECTORIES as $directory) {
            $this->remove($directory);
        }

        $this->installer->getIo()->write(
            sprintf(
                '  - Removed <comment>%s</comment> module from application',
                basename($this->installer->getVendorPath())
            ),
            true
        );
    }

    /**
     * removeExtras
     *
     * @param  array $files
     *
     * @return void
     */
    protected function removeExtras($files)
    {
        foreach ($files as $source => $target) {
            $this->installer->getIo()->write(
                sprintf('  - Skipped additional file(s) <comment>%s</comment>', $target),
                true
            );
        }
    }

    /**
     * It recursively removes the files and directories
     * @param $directory
     * @return void
     */
    protected function remove($directory)
    {
        $sourcePath = $this->installer->getVendorPath() . DS . $directory;

        if (!is_dir($sourcePath)) {
            return;
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
                if (count(scandir($current, SCANDIR_SORT_ASCENDING)) === 2) {
                    rmdir($current);
                    $this->installer->getIo()->write(
                        "  - Removed directory `{$iterator->getSubPathName()}`",
                        true,
                        IOInterface::VERBOSE
                    );
                } else {
                    $this->installer->getIo()->write(
                        sprintf(
                            '  - <comment>Skipped directory `%s`</comment>',
                            $directory . DS . $iterator->getSubPathName()
                        ),
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

            if (md5_file($item) === md5_file($current)) {
                // remove file
                unlink($current);
                $this->installer->getIo()->write(
                    "  - Removed file `{$iterator->getSubPathName()}`",
                    true,
                    IOInterface::VERBOSE
                );
            } else {
                // or skip changed files
                $this->installer->getIo()->write(
                    "  - <comment>File `{$iterator->getSubPathName()}` has changed</comment>",
                    true,
                    IOInterface::VERBOSE
                );
            }
        }
    }
}
