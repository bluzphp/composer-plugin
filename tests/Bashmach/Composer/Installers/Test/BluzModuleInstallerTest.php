<?php
/**
 * @author Pavel Machekhin <pavel.machekhin@gmail.com>
 * @created 2015-03-26 13:32
 */

namespace Bashmach\Composer\Installers\Test;

use Bashmach\Composer\Installers\BluzModuleInstaller;
use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;

class BluzModuleInstallerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IOInterface
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var BluzModuleInstaller
     */
    private $installer;

    public function setUp()
    {
        $this->io = $this->getMock('Composer\IO\IOInterface');
        $this->config = new Config();

        $this->composer = new Composer();
        $this->composer->setConfig($this->config);

        $this->installer = new BluzModuleInstaller(
            $this->io,
            $this->composer
        );
    }

    public function testFooBar()
    {
        $this->assertTrue(false);
    }
}