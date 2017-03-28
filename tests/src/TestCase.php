<?php
/**
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/framework
 */

/**
 * @namespace
 */
namespace Bluz\Tests;

use Bluz\Composer\Installers\Installer;
use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;

/**
 * Bluz TestCase for Composer Plugin
 *
 * @package  Bluz\Tests
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Installer
     */
    private $installer;

    public function setUp()
    {
        $this->io = $this->getMock('Composer\IO\NullIO');
        $this->config = new Config();

        $this->composer = new Composer();
        $this->composer->setConfig($this->config);

        $this->installer = new Installer(
            $this->io,
            $this->composer
        );
    }
}
