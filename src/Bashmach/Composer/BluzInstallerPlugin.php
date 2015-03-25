<?php
/**
 * @author Pavel Machekhin <pavel.machekhin@gmail.com>
 * @created 2015-03-24 12:39
 */

namespace Bashmach\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class BluzInstallerPlugin implements PluginInterface
{
    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {


        var_dump($io);
        var_dump($composer);
        die;

        $installer = new BluzInstaller($io, $composer);
        $composer->getInstallationmanager()->addInstaller($installer);
    }
}