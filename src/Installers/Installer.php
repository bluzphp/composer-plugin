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

use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;

class Installer extends LibraryInstaller
{
    protected $vendorPath;

    /**
     * Get path to the installation package
     *
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package): string
    {
        $this->vendorPath = parent::getInstallPath($package);
        return $this->vendorPath;
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

    /**
     * Get path to current vendor
     */
    public function getVendorPath()
    {
        return $this->vendorPath;
    }
}
