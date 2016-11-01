<?php

namespace Bluz\Composer\Config;

use \Exception;

class ConfigDb
{
    protected $environment;

    protected $options = [];

    const CONFIG_FILE_NAME = 'db.php';

    public function __construct(string $environment = null)
    {
        $this->environment = $environment;
    }

    public function init() {
        $configFile = $this->getConfigPath() . DS . self::CONFIG_FILE_NAME;
        $this->setOptions($configFile);
    }

    protected function setOptions(string $file) {
        if (!is_file($file) && !is_readable($file)) {
            throw new Exception('Configuration file `' . $file . '` not found');
        }

        $config = require $file;
        $this->options = current($config);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption($key)
    {
        $options = $this->getOptions();

        return $options[$key];
    }

    public function setEnvironment(string $environment)
    {
        $this->environment = $environment;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getConfigPath(): string
    {
        return ROOT_PATH . DS .
        'application' . DS .
        'configs' . DS .
        $this->getEnvironment();
    }
}
