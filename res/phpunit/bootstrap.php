<?php
// Find the root path of the project being tested ...
$rootPath = __DIR__ . '/../../../../..';

// Install the composer autoloader ...
$autoloader = require_once $rootPath . '/vendor/autoload.php';

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
$projectTestFixturePath = $rootPath . '/test/src';
if (is_dir($projectTestFixturePath)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $projectTestFixturePath,
            RecursiveDirectoryIterator::SKIP_DOTS
        )
    );

    foreach ($iter as $file) {
        require $file;
    }
}

// Include a project-specific bootstrap file, if present ...
$projectBootstrapPath = $rootPath . '/test/bootstrap.php';
if (is_file($projectBootstrapPath)) {
    require_once $projectBootstrapPath;
}
