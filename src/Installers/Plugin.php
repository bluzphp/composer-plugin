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
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
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
    public const PERMISSION_CODE = 0755;
    public const REPEAT = 5;
    public const DIRECTORIES = [
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
    protected $vendorPath;

    /**
     * @var string
     */
    protected $packagePath;

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
        \defined('PATH_ROOT') ?: \define('PATH_ROOT', realpath($_SERVER['DOCUMENT_ROOT']));
        \defined('DS') ?: \define('DS', DIRECTORY_SEPARATOR);
    }

    /**
     * Called after the plugin is loaded
     *
     * It setup composer installer
     *
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->installer = new Installer($io, $composer);
        $this->vendorPath = $composer->getConfig()->get('vendor-dir');
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
            // copy extra files from root composer.json
            // do it only once after create project
            ScriptEvents::POST_CREATE_PROJECT_CMD => 'copyProjectExtraFiles',
            // copy module's files to working directory
            PackageEvents::POST_PACKAGE_INSTALL => 'copyModuleFiles',
            PackageEvents::POST_PACKAGE_UPDATE => 'copyModuleFiles',
            // removed unchanged module's files
            PackageEvents::PRE_PACKAGE_UPDATE => 'removeModuleFiles',
            PackageEvents::PRE_PACKAGE_UNINSTALL => 'removeModuleFiles',
        ];
    }

    /**
     * extractPackage
     *
     * @param PackageEvent $event
     *
     * @return PackageInterface
     */
    protected function extractPackage(PackageEvent $event): PackageInterface
    {
        if ($event->getOperation() instanceof UpdateOperation) {
            return $event->getOperation()->getTargetPackage();
        }
        return $event->getOperation()->getPackage();
    }

    /**
     * Copy extra files from compose.json of project
     *
     * @param Event $event
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function copyProjectExtraFiles(Event $event): void
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
     * Hook which is called after install package
     * It copies bluz module
     *
     * @param PackageEvent $event
     *
     * @throws \InvalidArgumentException
     */
    public function copyModuleFiles(PackageEvent $event): void
    {
        $package = $this->extractPackage($event);
        $this->packagePath = $this->vendorPath . DS . $package->getName();
        if ($package->getType() === 'bluz-module' && file_exists($this->packagePath)) {
            if ($package->getExtra() && isset($package->getExtra()['copy-files'])) {
                $this->copyExtras($package->getExtra()['copy-files']);
            }
            $this->copyModule();
        }
    }

    /**
     * Hook which is called before update package
     * It checks bluz module
     *
     * @param PackageEvent $event
     */
    public function removeModuleFiles(PackageEvent $event): void
    {
        $package = $this->extractPackage($event);
        $this->packagePath = $this->vendorPath . DS . $package->getName();
        if ($package->getType() === 'bluz-module' && file_exists($this->packagePath)) {
            if ($package->getExtra() && isset($package->getExtra()['copy-files'])) {
                $this->removeExtras($package->getExtra()['copy-files']);
            }
            $this->removeModule();
        }
    }

    /**
     * Get Filesystem
     *
     * @return Filesystem
     */
    protected function getFilesystem(): Filesystem
    {
        if (!$this->filesystem) {
            $this->filesystem = new Filesystem();
        }
        return $this->filesystem;
    }

    /**
     * Copy Module files
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function copyModule(): void
    {
        foreach (self::DIRECTORIES as $directory) {
            $this->copy(
                $this->packagePath . DS . $directory . DS,
                PATH_ROOT . DS . $directory . DS
            );
        }

        $this->installer->getIo()->write(
            sprintf(
                '  - Copied <comment>%s</comment> module to application',
                basename($this->packagePath)
            ),
            true
        );
    }

    /**
     * copyExtras
     *
     * @param array $files
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function copyExtras(array $files): void
    {
        foreach ($files as $source => $target) {
            $this->copy(
                $this->vendorPath . DS . $source,
                PATH_ROOT . DS . $target
            );
        }
    }

    /**
     * It recursively copies the files and directories
     *
     * @param string $source
     * @param string $target
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function copy(string $source, string $target): void
    {
        // skip, if not exists
        if (!file_exists($source)) {
            return;
        }
        // skip, if target exists
        if (is_file($target) && !is_dir($target)) {
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
                $this->getFilesystem()->mkdir(\dirname($target));
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
            $finder = new Finder();
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
    protected function removeModule(): void
    {
        foreach (self::DIRECTORIES as $directory) {
            $this->remove($directory);
        }

        $this->installer->getIo()->write(
            sprintf(
                '  - Removed <comment>%s</comment> module from application',
                basename($this->packagePath)
            ),
            true
        );
    }

    /**
     * removeExtras
     *
     * @param array $files
     *
     * @return void
     */
    protected function removeExtras(array $files): void
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
    protected function remove($directory): void
    {
        $sourcePath = $this->packagePath . DS . $directory;

        if (!is_dir($sourcePath)) {
            return;
        }
        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $sourcePath,
                    \RecursiveDirectoryIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            ) as $item
        ) {
            // path to copied file
            $current = PATH_ROOT . DS . $directory . DS . $iterator->getSubPathName();

            // remove empty directories
            if (is_dir($current)) {
                if (\count(scandir($current, SCANDIR_SORT_ASCENDING)) === 2) {
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

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // TODO: Implement uninstall() method.
    }
}
