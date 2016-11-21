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

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getTestModulesPath(): string
    {
        return ROOT_PATH . DS .
        'tests' . DS .
        'modules';
    }

    public function getTestModelsPath(): string
    {
        return ROOT_PATH . DS .
        'tests' . DS .
        'models';
    }

    public function getModelsPath(): string
    {
        return $this->getModulesPath() . DS .
        '..' . DS .
        'models';
    }

    public function getPublicPath(): string
    {
        return ROOT_PATH . DS. 'public';
    }

    public function getModulesPath(): string
    {
        return ROOT_PATH . DS .
        self::MODULES_PATH;
    }

    public function getDumpPath(): string
    {
        return $this->getModulesPath() . DS .
        $this->moduleName . DS .
        self::DUMP_FILE_NAME;
    }

    public function getModulePath(): string
    {
        return $this->getModulesPath() . DS . $this->moduleName;
    }

    public function getJsFilesPath(): string
    {
        return $this->getPublicPath() . DS . 'js' . DS . $this->moduleName;
    }

    public function getCssFilesPath(): string
    {
        return $this->getPublicPath() . DS . 'css' . DS . $this->moduleName;
    }

    public function getFontsFilesPath(): string
    {
        return $this->getPublicPath() . DS . 'css' . DS . $this->moduleName;
    }
}
