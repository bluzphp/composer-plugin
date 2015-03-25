<?php
/**
 * @author Pavel Machekhin <pavel.machekhin@gmail.com>
 * @created 2015-03-24 11:15
 */

namespace Bashmach\Composer;

use Composer\Package\PackageInterface;
use Composer\Installers\BaseInstaller;

class BluzInstaller extends BaseInstaller
{
    /**
     * Initializes base installer.
     *
     * @param PackageInterface $package
     * @param Composer         $composer
     */
    public function __construct(PackageInterface $package = null, Composer $composer = null)
    {
        $this->composer = $composer;
        $this->package = $package;

        dump($package);
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package, $frameworkType = '')
    {
        $extra     = $package->getExtra();
        $rootExtra = $this->composer->getPackage()->getExtra();
        $settings  = array_merge($rootExtra['puppet'], $extra['puppet']);
        if (empty($settings['modules_path'])) {
            throw new \Exception('modules_path is not defined');
        }
        if (empty($settings['module_name'])) {
            throw new \Exception('module_name is not defined');
        }
        $path = $settings['modules_path'] . '/' . $settings['module_name'];
        return $path;
    }
    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return $packageType === 'bluz-module';
    }
}