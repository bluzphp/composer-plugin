<?php

namespace Bluz\Composer\Installers;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;


class BluzModuleInstaller extends LibraryInstaller
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package): string
    {
        $extra     = $package->getExtra();
        $rootExtra = $this->composer->getPackage()->getExtra();

        $this->setSettings(array_merge($rootExtra, $extra['bluz']));

        if (empty($this->getSetting('module_name'))) {
            throw new \Exception('module_name is not defined');
        }

        $vendorPath = parent::getInstallPath($package);
        $this->setSetting('vendorPath', $vendorPath);

        return $vendorPath;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return $packageType === 'bluz-module';
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    public function setSetting(string $key, string $value)
    {
        $this->settings[$key] = $value;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function getSetting(string $key)
    {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        return null;
    }

    public function getIo()
    {
        return $this->io;
    }
}
