<?php
/**
 * Bluz composer plugin component
 *
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/composer-plugin
 */

/**
 * @namespace
 */
namespace Bluz\Composer\Helper;

class PathHelper
{
    const DUMP_FILE_NAME = 'dump.sql';

    const MODULES_PATH = 'application/modules';

    protected $moduleName;

    /**
     * Create instance
     */
    public function __construct($moduleName)
    {
        $this->moduleName = $moduleName;
    }

    /**
     * Get module name
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    /**
     * Get path to the tests module directory
     */
    public function getTestModulesPath(): string
    {
        return PATH_ROOT . DS .
        'tests' . DS .
        'modules';
    }

    /**
     * Get path to the tests model directory
     */
    public function getTestModelsPath(): string
    {
        return PATH_ROOT . DS .
        'tests' . DS .
        'models';
    }

    /**
     * Get path to the models
     */
    public function getModelsPath(): string
    {
        return $this->getModulesPath() . DS .
        '..' . DS .
        'models';
    }

    /**
     * Get path to the public directory
     */
    public function getPublicPath(): string
    {
        return PATH_ROOT . DS. 'public';
    }

    /**
     * Get path to the modules directory
     */
    public function getModulesPath(): string
    {
        return PATH_ROOT . DS .
        self::MODULES_PATH;
    }

    /**
     * Get path to the sql dump
     */
    public function getDumpPath(): string
    {
        return $this->getModulesPath() . DS .
        $this->moduleName . DS .
        self::DUMP_FILE_NAME;
    }

    /**
     * Get path to the module
     */
    public function getModulePath(): string
    {
        return $this->getModulesPath() . DS . $this->moduleName;
    }

    /**
     * Get path to the js files
     */
    public function getJsFilesPath(): string
    {
        return $this->getPublicPath() . DS . 'js' . DS . $this->moduleName;
    }

    /**
     * Get path to the css files
     */
    public function getCssFilesPath(): string
    {
        return $this->getPublicPath() . DS . 'css' . DS . $this->moduleName;
    }

    /**
     * Get path to the css files
     */
    public function getFontsFilesPath(): string
    {
        return $this->getPublicPath() . DS . 'fonts' . DS . $this->moduleName;
    }
}
