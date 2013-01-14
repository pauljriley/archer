# Testing

This package provides a set of default PHPUnit, Travis CI and Git configurations for other Icecave Studios projects.

## Installation

* Add 'icecave/collections' to the project's composer.json development dependencies
* Run `php composer.phar install --dev`

## Executing Tests

To execute unit tests run ```vendor/bin/phpunit``` from the project's root directory.
To include coverage reporting, instead run ```vendor/bin/phpunit-coverage```.
