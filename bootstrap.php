<?php
use Composer\Autoload\ClassLoader;
use Eloquent\Asplode\Asplode;

// Find the root path of the project being tested ...
$rootPath = __DIR__ . '/../../..';

// Install the composer autoloader ...
require_once $rootPath . '/vendor/autoload.php';

// Setup asplode for strict error reporting ...
Asplode::instance()->install();

// Setup Phake/PHPUnit integration ...
Phake::setClient(Phake::CLIENT_PHPUNIT);

// Add an autoloader for test fixtures, if required ...
$projectTestLibPath = $rootPath . '/test/lib';
if (is_dir($projectTestLibPath)) {
    $loader = new ClassLoader;
    $loader->add('Icecave', array($projectTestLibPath));
    $loader->register();
}

// Include a project-specific bootstrap file, if present ...
$projectBootstrapPath = $rootPath . '/test/bootstrap.php';
if (is_file($projectBootstrapPath)) {
    require_once $projectBootstrapPath;
}
