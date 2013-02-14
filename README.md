# ![Archer][logo]

[![Build Status][status-icon]](http://travis-ci.org/IcecaveStudios/archer)
[![Test Coverage][coverage-icon]](http://icecavestudios.github.com/archer/artifacts/tests/coverage)

[logo]: http://icecave.com.au/assets/img/project-icons/icon-archer.png
[status-icon]: https://raw.github.com/IcecaveStudios/archer/gh-pages/artifacts/images/icecave/regular/build-status.png
[coverage-icon]: https://raw.github.com/IcecaveStudios/archer/gh-pages/artifacts/images/icecave/regular/coverage.png
---

## Overview

**Archer** is a library for standardizing PHP unit testing and continuous
integration behavior across multiple projects using a
[convention-over-configuration](http://en.wikipedia.org/wiki/Convention_over_configuration])
approach. It brings together several high-quality libraries to help improve the
quality of a project's test suite and reporting tools.

The use of Archer requires that the host project conforms to a set of
[conventions](#requirements). In return, it provides some awesome benefits:

- Configuration-free, best-practice [unit testing](#unit-testing) and
  [test coverage reports](#test-coverage-reports) with
  [PHPUnit](https://github.com/sebastianbergmann/phpunit) and
  [Xdebug](http://xdebug.org/).
- Improved [mock object](#improved-mock-object-support) support courtesy of
  [Phake](http://phake.digitalsandwich.com/docs/html/).
- Automated repository and [Travis CI](https://travis-ci.org/)
  [configuration](#automated-configuration).
- [Build artifact publication](#build-artifact-publication) to project
  [GitHub Pages](http://pages.github.com/).

## Requirements

- [PHPUnit](https://github.com/sebastianbergmann/phpunit) **must** be available
  in the user's PATH.
- The [Xdebug](http://xdebug.org/) extension is required for
  [test coverage reports](#test-coverage-reports).
- The [openssl](http://php.net/openssl) extension is required for
  [build artifact publication](#build-artifact-publication).
- Projects using Archer **must** use [Composer](http://getcomposer.org/).
- Projects **must** conform to Archer's [expected project layout](#expected-project-layout).

#### Expected project layout

```
/path/to/project/   <- project root
  ↳ lib/            <- source files (can alternatively be named 'src')
  ↳ test/
     ↳ suite/       <- tests
```

## Getting started

Change directory into a new or existing cloned GitHub repository:

```
git clone git@github.com:IcecaveStudios/archer-project.git
cd archer-project
```

Create a `lib` or `src` directory if neither exists already. Move any existing
source files into this directory. Then create a `test/suite` directory if it
does not already exist, and move any existing tests into this directory.

```
mkdir lib
mkdir test
mkdir test/suite
```

Create a `composer.json` file for the project, or edit the existing file and add
[icecave/archer](https://packagist.org/packages/icecave/archer) as a
`require-dev` dependency:

```json
{
    "name": "icecave/archer-project",
    "description": "An example project to demonstrate Archer setup.",
    "require-dev": {
        "icecave/archer": "*"
    }
}
```

Update Composer dev dependencies for the project:

```
composer update --dev
```

The `archer` executable should now be available at `vendor/bin/archer`:

```
vendor/bin/archer --version
```

If there are any existing `.gitattributes` or `.gitignore` files, they should be
removed before continuing:

```
rm -f .gitattributes .gitignore
```

Set up the git repository and Travis CI configuration files using
`archer update`:

```
vendor/bin/archer update --authorize
```

This command will prompt for a GitHub username and password, allowing Archer to
publish build artifacts from Travis CI.

The changes made by Archer should now be committed to the repository, and pushed
back to GitHub:

```
git add -A
git commit -m 'Adding Archer integration.'
git push
```

And that's it! The project is now set up to use Archer.

## Unit testing

Archer provides a wrapper around [PHPUnit](https://github.com/sebastianbergmann/phpunit)
for unit testing support. Arguments and options to this command are passed on to
PHPUnit. The test suite can be run by executing `archer test`.

If archer is run without arguments, it is another shortcut to the `test`
command, but no arguments or options are permitted.

#### Test command usage

```
vendor/bin/archer                       # shortcut to 'test' (no arguments or options allowed)
vendor/bin/archer t                     # shortcut to 'test'
vendor/bin/archer t --stop-on-failure   # passing arguments to PHPUnit
vendor/bin/archer test                  # canonical form
```

#### Example test command output

![Example test command output](http://i.imgur.com/IGqgI4U.png)

#### Improved mock object support

In addition to PHPUnit's inbuilt [test doubles](http://www.phpunit.de/manual/current/en/test-doubles.html),
Archer includes another mocking library, known as [Phake](http://phake.digitalsandwich.com/docs/html/),
and handles the configuration necessary to integrate Phake and PHPUnit.

Phake's mocking system is easier to work with, requiring less setup and
providing more flexibility than PHPUnit's mocks. It is recommended to use Phake
to create mocks instead of PHPUnit's own system.

#### Autoloading of test fixture classes

Sometimes it's necessary to create fixture classes manually for situations that
cannot easily be dealt with by creating mock objects. For this reason, Archer
will, at test time only, autoload any classes that follow the
[PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)
standard from the `test/lib` or `test/src` directory, in addition the classes
normally loaded by Composer.

## Test coverage reports

Test coverage reports provide a useful metric for determining how thoroughly a
project is tested. The test coverage report can be generated by executing
`archer coverage`. The HTML test coverage report will be generated in the
`artifacts/tests/coverage` directory, which can be opened in any web browser.

#### Coverage command usage

```
vendor/bin/archer c                     # shortcut to 'coverage'
vendor/bin/archer c --stop-on-failure   # passing arguments to PHPUnit
vendor/bin/archer coverage              # canonical form
```

#### Example coverage command output

![Example archer coverage output](http://i.imgur.com/MFM6qS4.png)

#### Example test coverage report

![Example archer coverage report](http://i.imgur.com/2to6pta.png)

For a live example see the [test coverage report](http://icecave.com.au/siesta/artifacts/tests/coverage/)
for [Siesta](https://github.com/IcecaveStudios/siesta).

## Automated configuration

Archer provides a command called `update` to assist in keeping git repository
and [Travis CI](https://travis-ci.org/) configuration consistent across multiple
projects.

Archer's generated configuration ensures that:

- Travis CI knows how to run the tests, build the coverage report, and publish
  build artifacts.
- Travis CI builds against all relevant versions of PHP.
- Test and build artifacts are ignored by Git.
- Archived versions of projects do not include development artifacts like the
  test suite.

#### Update command usage (without artifact publishing support)

```
vendor/bin/archer u        # shortcut to 'update'
vendor/bin/archer update   # canonical form
```

#### Example generated configuration (without artifact publishing support)

###### .gitattributes

```
.gitattributes export-ignore
.gitignore export-ignore
.travis.* export-ignore
.archer.* export-ignore
test export-ignore
```

###### .gitignore

```
/artifacts/
/vendor/
```

###### .travis.yml

```yaml
#
# This is a fall-back Travis CI configuration for
# use when no GitHub OAuth token is available.
#
# It uses --prefer-source when fetching composer dependencies
# to avoid IP-based API throttling (ie, it uses clone instead of downloading a zip).
#
language: php

php:
  - 5.3.3
  - 5.3
  - 5.4
  - 5.5

install:
    - composer install --dev --prefer-source --no-interaction
script:
    - ./vendor/bin/archer travis:build

matrix:
  # PHP 5.5 is still in alpha, so ignore build failures.
  allow_failures:
    - php: 5.5
```

## Build artifact publication

Wouldn't it be great if everyone could see that 100% test coverage report? Then
it would be obvious how much care and effort went into producing a quality
product. With Archer, this is as simple as authorizing a project.

To authorize a project, simply run the `update` command with the `authorize`
flag. The command will prompt for a GitHub username and password, and use these
to create an encrypted configuration file used to publish artifacts from Travis
CI:

#### Update command usage (with artifact publishing support)

```
vendor/bin/archer u -a                # shortcut to 'update --authorize'
vendor/bin/archer update --authorize  # canonical form
```

#### Example generated configuration (with artifact publishing support)

###### .gitattributes

```
.gitattributes export-ignore
.gitignore export-ignore
.travis.* export-ignore
.archer.* export-ignore
test export-ignore
```

###### .gitignore

```
/artifacts/
/vendor/
```

###### .travis.before-install

```php
#!/usr/bin/env php
<?php
/**
 * This script is executed before composer dependencies are installed,
 * and as such must be included in each project as part of the skeleton.
 */
$path   = getenv('HOME') . '/.composer/config.json';
$dir    = dirname($path);
$config = <<<EOD
{
    "config" : {
        "github-oauth" : {
            "github.com": "${_SERVER['ARCHER_TOKEN']}"
        }
    }
}
EOD;

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

file_put_contents($path, $config);
```

###### .travis.env
```
d8see0zhDehR+tWv05s8O7JUQaWyz7xDOzQtSLi8Hw+0pdWC1L6nnQGpKy6kUXOht2TEM7zptCbSjMAY1434/GCXjIadpeP3AW9pU7EXCsXK0QOpR8e69JjBqbw8Vbe63mmu1Ux5jh/t0x2L+I0uaBSdqvIT7thzlOIABIBlzVg=
```

###### .travis.key
```
-----BEGIN RSA PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCWl4g0c9rx4EAQSA42yS3XMW79
H0cj4I2dE9RA2HNZTsHyC6ERLOhcnasRe4vKuAJuhv09PspsqLZLpPvbeLBbzSg5
YqRcsQB7MRSQ6VwDDISDvb89DTFijk6lvUyPnAQlpMJSOQGZGXDE1JoE/+h7Vo6V
x0fJacUrjQtMa0EZtwIDAQAB
-----END RSA PUBLIC KEY-----
```

###### .travis.yml

```yaml
#
# This is the default Travis CI configuration.
#
# It uses a GitHub OAuth token when fetching composer dependencies
# to avoid IP-based API throttling.
#
# It also allows publication of artifacts via an additional build.
#
language: php

php:
  - 5.3.3
  - 5.3
  - 5.4
  - 5.5

env:
  global:
    - ARCHER_PUBLISH_VERSION=5.4
    - secure: "d8see0zhDehR+tWv05s8O7JUQaWyz7xDOzQtSLi8Hw+0pdWC1L6nnQGpKy6kUXOht2TEM7zptCbSjMAY1434/GCXjIadpeP3AW9pU7EXCsXK0QOpR8e69JjBqbw8Vbe63mmu1Ux5jh/t0x2L+I0uaBSdqvIT7thzlOIABIBlzVg="

before_install:
    - ./.travis.before-install
install:
    - composer install --dev --prefer-dist --no-interaction
script:
    - ./vendor/bin/archer travis:build

matrix:
  # PHP 5.5 is still in alpha, so ignore build failures.
  allow_failures:
    - php: 5.5
```

#### Published artifacts

Once the authorization has been set up, artifacts are published to the project's
`gh-pages` branch under a directory named `artifacts`. The directory structure
is as follows:

```
artifacts/
  ↳ images/         <- build status badges, organised by theme
  ↳ tests/
      ↳ coverage/   <- test coverage report
```

##### Published test coverage report

Once published, a project's test coverage report is available through GitHub's
[Pages](http://pages.github.com/) system. As an example,
[Siesta](https://github.com/IcecaveStudios/siesta)'s coverage reports are
published to [http://icecavestudios.github.com/siesta/artifacts/tests/coverage/].
Note that this link redirects to a custom domain, but it is still served through
GitHub Pages.

##### Published build status and test coverage badges

To quickly convey information about the quality of a project, Archer publishes
status 'badges' (or images, or 'shields') that can easily be utilized in a
project's README.md or other documentation. See the [source](README.md) of this
README.md to see how to display this information effectively.

Archer currently provides these badges in two themes, courtesy of
[ezzatron](https://github.com/ezzatron)'s
[ci-status-images](https://github.com/ezzatron/ci-status-images).

###### Example Icecave theme build status images

- ![passing](https://raw.github.com/ezzatron/ci-status-images/master/img/icecave/regular/build-status/build-status-passing.png)
- ![failing](https://raw.github.com/ezzatron/ci-status-images/master/img/icecave/regular/build-status/build-status-failing.png)
- ![100% test coverage](https://raw.github.com/ezzatron/ci-status-images/master/img/icecave/regular/test-coverage/test-coverage-100.png)
- ![50% test coverage](https://raw.github.com/ezzatron/ci-status-images/master/img/icecave/regular/test-coverage/test-coverage-050.png)

###### Example Travis theme build status images

- ![passing](https://raw.github.com/ezzatron/ci-status-images/master/img/travis/variable-width/build-status/build-status-passing.png)
- ![failing](https://raw.github.com/ezzatron/ci-status-images/master/img/travis/variable-width/build-status/build-status-failing.png)
- ![100% test coverage](https://raw.github.com/ezzatron/ci-status-images/master/img/travis/variable-width/test-coverage/test-coverage-100.png)
- ![50% test coverage](https://raw.github.com/ezzatron/ci-status-images/master/img/travis/variable-width/test-coverage/test-coverage-050.png)
