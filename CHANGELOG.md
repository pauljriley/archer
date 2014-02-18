# Archer Changelog

### 1.1.4 (2014-02-17)

* **[FIXED]** The `travis:build` command no longer publishes artifacts when building a PR from a branch on the main repository
* **[FIXED]** Updated to [Woodhouse 0.5.2](https://github.com/IcecaveStudios/woodhouse/releases/tag/0.5.2), to fix an [artifact publication issue](https://github.com/IcecaveStudios/woodhouse/issues/38)

### 1.1.3 (2014-02-10)

* **[FIXED]** Documentation generation no longer fails if a project does not have a `src` folder
* **[IMPROVED]** Archer no longer forces use of PSR-0 autoloading for the `test/src` folder, allowing for PSR-4 (or anything else)
* **[IMPROVED]** Updated autoloader to [PSR-4](http://www.php-fig.org/psr/psr-4/)
* **[IMPROVED]** Reverted `apt-get update git` in `travis:build` as the Git version on Travis CI has been restored to 1.8.x

### 1.1.2 (2014-01-21)

* **[FIXED]** Automatic creation of `gh-pages` branch no longer fails due to unsupported Git version (see [travis-ci/travis-ci#1710](https://github.com/travis-ci/travis-ci/issues/1710))

### 1.1.1 (2014-01-08)

* **[FIXED]** Updated to [Woodhouse 0.5.1](https://github.com/IcecaveStudios/woodhouse/releases/tag/0.5.1), which includes some minor fixes

### 1.1.0 (2013-10-14)

* **[FIXED]** The `update` command now configures Travis CI to publish artifacts under the most recent PHP version supported
* **[FIXED]** Updated bundled [Asplode](https://github.com/eloquent/asplode) to latest version, this fixes issues with `symfony/process` 2.3.5+
* **[IMPROVED]** Increased timeouts rather generously when running coverage reports
* **[NEW]** Added test groups `exclude-by-default` and `exclude-from-coverage` to PHPUnit configuration files
* **[NEW]** Added `--always-publish` optiont to `travis:build` to force publication of test artificats even when Coveralls is enabled

### 1.0.1 (2013-10-01)

* **[WORKAROUND]** Pinned `symfony/process` below version 2.3.5 to work around issues with strict error reporting and error suppression

### 1.0.0 (2013-09-09)

* **[FIXED]** The `update` command no longer fails when run against a non-GitHub repository (however only dotfile updates are supported)

### 1.0.0-alpha.3 (2013-09-08)

* **[BC]** Removed support for 'lib' folders for consistency, 'src' is now the only supported folder for source and test fixtures
* **[BC]** Test coverage artifacts are only published if Coveralls support is disabled
* **[BC]** Archer no longer publishes build status images (use Travis CI dynamic images instead)
* **[NEW]** Added INI directives to enable PHP 5.5 opcode cache while running tests
* **[IMPROVED]** Coverage badges now use the 'buckler' theme

### 1.0.0-alpha.2 (2013-07-30)

* **[IMPROVED]** Travis CI installation script now runs `composer self-update` before installing dependencies
* **[FIXED]** Minor PHP version constraints are now checked correctly when building Travis CI configuration file

### 1.0.0-alpha.1 (2013-07-08)

* **[NEW]** Integration with [Coveralls](https://coveralls.io/) for hosted test coverage reports
* **[NEW]** `--open` option on `coverage` and `documentation` commands automatically opens generated content in browser
* **[IMPROVED]** `update` command now uses PHP version constraint in `composer.json` to limit Travis CI builds to supported versions

### 0.5.0 (2013-06-24)

* **[NEW]** GitHub API rate-limiting headers are now output to the terminal during the Travis CI installed step
* **[IMPROVED]** Removed PHP v5.5 from the 'allow_failures' section of the Travis CI configuration file, as it is now a stable release

### 0.4.2 (2013-06-05)

* **[FIXED]** Generating API documentation will now overwrite existing documentation

### 0.4.1 (2013-05-29)

* **[IMPROVED]** API documentation title generated from the namespace name instead of the Composer package name.
* **[IMPROVED]** API documentation menu opens to the project's root namespace level by default.

### 0.4.0 (2013-05-27)

* **[NEW]** New `documentation` command uses [Sami](https://github.com/fabpot/Sami) to generate HTML API documentation
* **[NEW]** HTML API documentation is published along with coverage information when building under Travis CI

### 0.3.1 (2013-04-30)

* **[FIXED]** Disabled sub-process timeout that caused long running tests to fail
* **[FIXED]** Added User-Agent header to GitHub API client [as required](http://developer.github.com/changes/2013-04-24-user-agent-required)

### 0.3.0 (2013-03-27)

* **[NEW]** Added JUnit XML reporting to PHPUnit configuration
* **[IMPROVED]** Disabled notify-on-install in Composer configuration (prevents inflated installation numbers on packagist)

### 0.2.1 (2013-02-26)

* **[BC]** Removed PHP v5.3.3 from Travis CI build configuration

### 0.2.0 (2013-02-21)

* **[FIXED]** Pull-request builds no longer fail due to unavailable secure environment variables
* **[IMPROVED]** Update command will now add Archer-specific entries to .gitignore and .gitattributes if the files already exist
* **[IMPROVED]** Unified oauth/no-oauth Travis CI configurations into a single YAML template

### 0.1.2 (2013-02-17)

* **[FIXED]** Updated to [Woodhouse 0.4.2](https://github.com/IcecaveStudios/woodhouse/releases/tag/0.4.2), allows automatic creation of gh-pages branch

### 0.1.1 (2013-02-16)

* **[FIXED]** Updated to [Woodhouse 0.4.1](https://github.com/IcecaveStudios/woodhouse/releases/tag/0.4.1), adds user.name/user.email to git config

### 0.1.0 (2013-02-14)

* Initial release
