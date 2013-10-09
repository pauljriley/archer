<?php
// Find the root path of the project being tested ...
$rootPath = __DIR__ . '/../../../../..';

// Install the composer autoloader ...
$autoloader = require_once $rootPath . '/vendor/autoload.php';

// Assert error handler configuration
if (class_exists('Eloquent\Asplode\Asplode')) {
    Eloquent\Asplode\Asplode::assertCompatibleHandler();
}

// Setup Phake/PHPUnit integration ...
if (class_exists('Phake')) {
    Phake::setClient(Phake::CLIENT_PHPUNIT);
}

// Add an autoloader for test fixtures, if required ...
$projectTestFixturePath = $rootPath . '/test/src';
if (is_dir($projectTestFixturePath)) {
    $autoloader->add('', array($projectTestFixturePath));
}

// Include a project-specific bootstrap file, if present ...
$projectBootstrapPath = $rootPath . '/test/bootstrap.php';
if (is_file($projectBootstrapPath)) {
    require_once $projectBootstrapPath;
}
