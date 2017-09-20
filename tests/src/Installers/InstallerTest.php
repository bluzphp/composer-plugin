<?php
/**
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/framework
 */

/**
 * @namespace
 */
namespace Bluz\Tests\Composer\Installers;

use Bluz\Tests\TestCase;

/**
 * Class Installer
 * @package Bluz\Tests\TestCase
 */
class InstallerTest extends TestCase
{
    public function testSupportedType()
    {
        self::assertTrue($this->getInstaller()->supports('bluz-module'));
    }

    public function testUnSupportedType()
    {
        self::assertFalse($this->getInstaller()->supports('not-bluz-module'));
    }

    public function testInstallerGetIO()
    {
        self::assertInstanceOf('Composer\\IO\\IOInterface', $this->getInstaller()->getIo());
    }
}
