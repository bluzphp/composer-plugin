<?php
/**
 * @author Pavel Machekhin <pavel.machekhin@gmail.com>
 * @created 2015-03-24 11:15
 */

namespace Composer\Installer;

use Composer\Package\PackageInterface;
use Composer\Installer\BaseInstaller;

class BluzInstaller extends BaseInstaller
{
    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
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