# ![Archer]

[![Build Status]](http://travis-ci.org/IcecaveStudios/archer)
[![Test Coverage]](http://icecavestudios.github.com/archer/artifacts/tests/coverage)

---

## Overview

**Archer** is a library for standardizing PHP unit testing and continuous integration behavior across multiple projects
using a [convention-over-configuration] approach. It brings together several high-quality libraries to help
improve the quality of a project's test suite and reporting tools.

The use of **Archer** requires that the host project conforms to a set of [conventions](#requirements). In return, it
provides the following benefits:

* Configuration-free, best-practice [unit testing](#unit-testing) and [test coverage reports](#test-coverage-reports)
  with [PHPUnit] and [Xdebug].
* Improved [mock object](#improved-mock-object-support) support courtesy of [Phake].
* Automated [configuration](#automated-configuration) of repository and [Travis CI].
* [Build artifact publication](#build-artifact-publication) to project [GitHub Pages].

## Requirements

* [PHPUnit] **must** be available in the user's PATH.
* Projects **must** use [Composer].
* Projects **must** conform to the [expected project layout](#expected-project-layout).
* The [Xdebug] PHP extension is required for [test coverage reports](#test-coverage-reports).
* The [openssl] PHP extension is required for [build artifact publication](#build-artifact-publication).

## Getting started

### Expected project layout

**Archer** expects projects to be laid out using the directory structure below:

    .                # Project / git root
    ├── lib/         # PHP source files (can alternatively be named 'src')
    └── test/
        └── suite/   # PHPUnit test suite

### Composer configuration

Add [icecave/archer](https://packagist.org/packages/icecave/archer) to the project's `composer.json` file as a
development dependency:

    composer require icecave/archer:0.1.0 --dev

This will create a new `composer.json` file if it does not exist, and update all dependencies. The `archer`
executable should now be available at `vendor/bin/archer`:

    vendor/bin/archer --version

### Initializing Archer

Set up the git repository and Travis CI configuration files using the update command.

    vendor/bin/archer update --authorize

This command will prompt for a GitHub username and password in order to authorize **Archer** to publish build artifacts
from [Travis CI]. For more information about security, please see the
[Security section](https://github.com/IcecaveStudios/woodhouse#security) of the [Woodhouse] documentation.

The changes made by **Archer** should now be committed to the repository, and pushed back to GitHub:

    git add -A
    git commit -m 'Adding Archer integration.'
    git push

And that's it! The project is now set up to use **Archer**.

## Unit testing

**Archer** provides a wrapper around [PHPUnit] for unit testing support. Arguments and options to this command are
passed on to PHPUnit. The test suite can be run by executing `archer` with no parameters:

    vendor/bin/archer

Running `archer` with no arguments is equivalent to `archer test`.

#### Test command usage

    vendor/bin/archer test                  # canonical form
    vendor/bin/archer                       # shortcut to 'test' (no arguments or options allowed)
    vendor/bin/archer t                     # shortcut to 'test'
    vendor/bin/archer t --stop-on-failure   # arguments are forwarded to PHPUnit

#### Example test command output

```console
$ vendor/bin/archer
Using PHP: /path/to/php
Using PHPUnit: /path/to/phpunit
PHPUnit 3.7.13 by Sebastian Bergmann.

Configuration read from /path/to/project/vendor/icecave/archer/res/phpunit/phpunit.xml

...............................................................  63 / 414 ( 15%)
............................................................... 126 / 414 ( 30%)
............................................................... 189 / 414 ( 45%)
............................................................... 252 / 414 ( 60%)
............................................................... 315 / 414 ( 76%)
............................................................... 378 / 414 ( 91%)
....................................

Time: 1 second, Memory: 14.00Mb

OK (414 tests, 915 assertions)
```

#### Improved mock object support

In addition to [PHPUnit's test doubles], **Archer** includes [Phake] - an alternative mocking library - and handles
the configuration necessary to integrate Phake and PHPUnit.

Phake's mocking system is easier to work with, requiring less setup and providing more flexibility than PHPUnit mocks.
For this reason it is the recommended mocking system.

#### Autoloading of test fixture classes

For some tests a test-double is not suitable and a fixture class needs to be used. For this reason, **Archer** will, at
test time only, autoload any classes that follow the [PSR-0] standard from the `test/lib` (or `test/src`) directory, in
addition the classes normally loaded by Composer.

## Test coverage reports

Test coverage reports provide a useful metric for determining how thoroughly a project is tested. The test coverage
report can be generated by executing `archer coverage`. The HTML test coverage report will be generated in the
`artifacts/tests/coverage` directory, which can be opened in any web browser.

#### Coverage command usage

    vendor/bin/archer coverage              # canonical form
    vendor/bin/archer c                     # shortcut to 'coverage'
    vendor/bin/archer c --stop-on-failure   # passing arguments to PHPUnit

#### Example coverage command output

```console
$ vendor/bin/archer c
Using PHP: /path/to/php
Using PHPUnit: /path/to/phpunit
PHPUnit 3.7.13 by Sebastian Bergmann.

Configuration read from /path/to/project/vendor/icecave/archer/res/phpunit/phpunit.coverage.xml

...............................................................  63 / 414 ( 15%)
............................................................... 126 / 414 ( 30%)
............................................................... 189 / 414 ( 45%)
............................................................... 252 / 414 ( 60%)
............................................................... 315 / 414 ( 76%)
............................................................... 378 / 414 ( 91%)
....................................

Time: 1 second, Memory: 17.00Mb

OK (414 tests, 915 assertions)

Generating code coverage report in HTML format ... done
```

#### Example test coverage report

![Example archer coverage report](http://i.imgur.com/2to6pta.png)

For a live example see the [test coverage report](http://icecave.com.au/siesta/artifacts/tests/coverage/) for [Siesta].

## Automated configuration

**Archer** provides a command called `update` to assist in keeping git repository and [Travis CI] configuration
consistent across multiple projects.

The generated configuration ensures that:

* Travis CI knows how to run the tests, build the coverage report, and publish build artifacts.
* Travis CI builds against all relevant versions of PHP.
* Travis CI builds are much less likely to fail because of GitHub API throttling.
* Test and build artifacts are ignored by Git.
* Archived versions of projects do not include development artifacts like the test suite.

#### Update command usage (without artifact publishing support)

    vendor/bin/archer update   # canonical form
    vendor/bin/archer u        # shortcut to 'update'

## Build artifact publication

Wouldn't it be great if everyone could see that 100% test coverage report? Then it would be obvious how much care and
effort went into producing a quality product. With **Archer**, this is as simple as authorizing a project.

To authorize a project, simply run the `update` command with the `--authorize` option. The command will prompt for a
GitHub username and password, and use these to create an encrypted configuration file used to publish artifacts from
[Travis CI].

#### Update command usage (with artifact publishing support)

    vendor/bin/archer update --authorize  # canonical form
    vendor/bin/archer u -a                # shortcut to 'update --authorize'

#### Published artifacts

Once the authorization has been set up, artifacts are published to the project's `gh-pages` branch under a directory
named `artifacts` whenever Travis builds the repository's [default branch][github default branch].

The directory structure is as follows:

    artifacts/
    ├── images/         # Build status and coverage badges, organised by theme
    └── tests/
        └── coverage/   # Test coverage reports

##### Published test coverage report

Once published, a project's test coverage report is available through GitHub's [Pages][github pages] system. As an
example, [Siesta]'s coverage reports coverage reports are published to
http://icecavestudios.github.com/siesta/artifacts/tests/coverage/.

Note that the above URL redirects to a custom domain, but it is still served through GitHub Pages.

##### Published build status and test coverage badges

To quickly convey information about the quality of a project, **Archer** publishes status 'badges' (or 'shields') that
can easily be displayed in a project's README.md or other documentation. See the
[source](https://raw.github.com/IcecaveStudios/siesta/develop/README.md) of Seista's README.md file to see how to
display this information effectively.

**Archer** currently provides status badges in two themes, courtesy of [ezzatron]'s [ci-status-images] project.

###### Example **Icecave** theme build status images

- ![passing](https://raw.github.com/ezzatron/ci-status-images/master/img/icecave/regular/build-status/build-status-passing.png)
- ![failing](https://raw.github.com/ezzatron/ci-status-images/master/img/icecave/regular/build-status/build-status-failing.png)
- ![100% test coverage](https://raw.github.com/ezzatron/ci-status-images/master/img/icecave/regular/test-coverage/test-coverage-100.png)
- ![50% test coverage](https://raw.github.com/ezzatron/ci-status-images/master/img/icecave/regular/test-coverage/test-coverage-050.png)

###### Example **Travis** theme build status images

- ![passing](https://raw.github.com/ezzatron/ci-status-images/master/img/travis/variable-width/build-status/build-status-passing.png)
- ![failing](https://raw.github.com/ezzatron/ci-status-images/master/img/travis/variable-width/build-status/build-status-failing.png)
- ![100% test coverage](https://raw.github.com/ezzatron/ci-status-images/master/img/travis/variable-width/test-coverage/test-coverage-100.png)
- ![50% test coverage](https://raw.github.com/ezzatron/ci-status-images/master/img/travis/variable-width/test-coverage/test-coverage-050.png)

<!-- references -->
[Archer]: http://icecave.com.au/assets/img/project-icons/icon-archer.png
[Build Status]: https://raw.github.com/IcecaveStudios/archer/gh-pages/artifacts/images/icecave/regular/build-status.png
[Test Coverage]: https://raw.github.com/IcecaveStudios/archer/gh-pages/artifacts/images/icecave/regular/coverage.png

[composer]: http://getcomposer.org
[convention-over-configuration]: http://en.wikipedia.org/wiki/Convention_over_configuration
[github pages]: http://pages.github.com
[github default branch]: https://help.github.com/articles/setting-the-default-branch-for-a-repository
[openssl]: http://php.net/openssl
[phake]: http://phake.digitalsandwich.com/docs/html
[phpunit's test doubles]: http://www.phpunit.de/manual/current/en/test-doubles.html
[phpunit]: https://github.com/sebastianbergmann/phpunit
[psr-0]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
[siesta]: https://github.com/IcecaveStudios/siesta
[travis ci]: https://travis-ci.org
[woodhouse]: https://github.com/IcecaveStudios/woodhouse
[xdebug]: http://xdebug.org
[ezzatron]: https://github.com/ezzatron
[ci-status-images]: https://github.com/ezzatron/ci-status-images
