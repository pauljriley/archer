<?php
// Find the root path of the project being tested ...
$rootPath = __DIR__ . '/../../../..';

// Install the composer autoloader ...
$autoloader = require_once $rootPath . '/vendor/autoload.php';

// Setup asplode for strict error reporting ...
if (class_exists('Eloquent\Asplode\Asplode')) {
    Eloquent\Asplode\Asplode::instance()->install();
}

// Setup Phake/PHPUnit integration ...
Phake::setClient(Phake::CLIENT_PHPUNIT);

// Add an autoloader for test fixtures, if required ...
foreach (array('lib', 'src') as $path) {
    $projectTestFixturePath = $rootPath . '/test/' . $path;
    if (is_dir($projectTestFixturePath)) {
        $autoloader->add('Icecave', array($projectTestFixturePath));
    }
}

// Include a project-specific bootstrap file, if present ...
$projectBootstrapPath = $rootPath . '/test/bootstrap.php';
if (is_file($projectBootstrapPath)) {
    require_once $projectBootstrapPath;
}
