<?php

namespace Bluz\Composer\Helper;

class PathHelper
{
    const DUMP_FILE_NAME = 'dump.sql';

    const MODULES_PATH = 'application/modules';

    protected $moduleName;

    public function __construct($moduleName)
    {
        $this->moduleName = $moduleName;
    }

    public function getModuleName()
    {
        return $this->moduleName;
    }

    public function getTestModulePath(): string
    {
        return ROOT_PATH . DS .
        'tests' . DS .
        'modules' . DS .
        $this->moduleName;
    }

    public function getTestModelsPath(): string
    {
        return ROOT_PATH . DS .
        'tests' . DS .
        'models' . DS;
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
        self::MODULES_PATH . DS;
    }

    public function getDumpPath(): string
    {
        return $this->getModulesPath() .
        $this->moduleName . DS .
        self::DUMP_FILE_NAME;
    }

    public function getModulePath(): string
    {
        return $this->getModulesPath() . $this->moduleName;
    }
}
