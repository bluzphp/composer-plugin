<?php
/**
 * Bluz composer installer
 *
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/composer-plugin
 */

/**
 * @namespace
 */
namespace Bluz\Composer\Installers;

use Bluz\Common\Options;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;

class Installer extends LibraryInstaller
{
    use Options;

    /**
     * Get path to the installation package
     *
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package): string
    {
        $extra     = $package->getExtra();
        $rootExtra = $this->composer->getPackage()->getExtra();

        $this->setOptions(array_merge($rootExtra, $extra['bluz']));

        if (empty($this->getOption('module_name'))) {
            throw new \Exception('module_name is not defined');
        }

        $vendorPath = parent::getInstallPath($package);
        $this->setOption('vendorPath', $vendorPath);

        return $vendorPath;
    }

    /**
     * Check type of the plugin
     *
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return $packageType === 'bluz-module';
    }

    /**
     * Get inputOutput instance
     */
    public function getIo(): IOInterface
    {
        return $this->io;
    }
}
