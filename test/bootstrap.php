<?php
error_reporting(-1);

$autoloader = require_once __DIR__ . '/../vendor/autoload.php';

Phake::setClient(Phake::CLIENT_PHPUNIT);
