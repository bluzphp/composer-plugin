<?php
// This is global bootstrap for autoloading
// Environment
define('DEBUG', true);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// init autoloader
$loader = require PATH_ROOT . '/vendor/autoload.php';
$loader->addPsr4('Bluz\\Tests\\', __DIR__ .'/src');
