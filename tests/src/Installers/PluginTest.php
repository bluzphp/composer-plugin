<?php
/**
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/framework
 */

/**
 * @namespace
 */
namespace Bluz\Tests\Composer\Installers;

use Bluz\Composer\Installers\Plugin;
use Bluz\Tests\TestCase;

/**
 * Class Plugin
 * @package Bluz\Tests\TestCase
 */
class PluginTest extends TestCase
{
    public function testSubscribedEventIsPresent()
    {
        self::assertCount(5, Plugin::getSubscribedEvents());
    }
}
