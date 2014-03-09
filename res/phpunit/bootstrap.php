<?php
use Icecave\Archer\Support\Composer\Autoload\ClassMapGenerator;

// Find the root path of the project being tested ...
define('ARCHER_ROOT_PATH', __DIR__ . '/../../../../..');

// Install the composer autoloader ...
$autoloader = require_once ARCHER_ROOT_PATH . '/vendor/autoload.php';

// Install Asplode if available
if (is_callable('Eloquent\Asplode\Asplode::install')) {
    $installMethod = new ReflectionMethod('Eloquent\Asplode\Asplode::install');
    if ($installMethod->isStatic()) {
        Eloquent\Asplode\Asplode::install();
    }
}

// Setup Phake/PHPUnit integration ...
Phake::setClient(Phake::CLIENT_PHPUNIT);

// Add an autoloader for test fixtures, if required ...
$projectTestFixturePath = ARCHER_ROOT_PATH . '/test/src';
if (is_dir($projectTestFixturePath)) {

    $buildClassMap = function () use ($projectTestFixturePath) {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $projectTestFixturePath,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        $classMap = array();

        foreach ($iter as $file) {
            foreach (ClassMapGenerator::findClasses($file) as $class) {
                $classMap[$class] = $file->getPathname();
            }
        }

        return $classMap;
    };


    $autoloader->addClassMap($buildClassMap());
}

// Include a project-specific bootstrap file, if present ...
$projectBootstrapPath = ARCHER_ROOT_PATH . '/test/bootstrap.php';
if (is_file($projectBootstrapPath)) {
    require_once $projectBootstrapPath;
}
