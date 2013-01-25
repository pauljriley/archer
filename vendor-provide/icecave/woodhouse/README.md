# Woodhouse

[![Build Status](https://secure.travis-ci.org/IcecaveStudios/woodhouse.png)](http://travis-ci.org/IcecaveStudios/woodhouse)
[![Test Coverage](http://icecavestudios.github.com/woodhouse/coverage-report/coverage.png)](http://icecavestudios.github.com/woodhouse/coverage-report/index.html)

**Woodhouse** provides a simple way to publish your PHPUnit code coverage reports to GitHub pages. It was originally designed to run in a [Travis CI](http://travis-ci.org) build, but can be used in any environment.

## Installation

**Woodhouse** requires PHP 5.3.3 or newer.

### With [Composer](http://getcomposer.org/)

* Add 'icecave/woodhouse' to the project's composer.json dependencies
* Run `php composer.phar install`

### Bare installation

* Clone from GitHub: `git clone git://github.com/IcecaveStudios/woodhouse.git`
* Use a [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)
  compatible autoloader (namespace 'Icecave\Woodhouse' in the 'lib' directory)
