# Archer Changelog

### Next version (unreleased)

* **[NEW]** Integration with [Coveralls](https://coveralls.io/) for hosted test coverage reports.

### 0.5.0 (2013-06-24)

* **[NEW]** GitHub API rate-limiting headers are now output to the terminal during the Travis CI installed step
* **[IMPROVED]** Removed PHP v5.5 from the 'allow_failures' section of the Travis CI configuration file, as it is now a stable release

### 0.4.2 (2013-06-05)

* **[FIXED]** Generating API documentation will now overwrite existing documentation

### 0.4.1 (2013-05-29)

* **[IMPROVED]** API documentation title generated from the namespace name instead of the composer package name.
* **[IMPROVED]** API documentation menu opens to the project's root namespace level by default.

### 0.4.0 (2013-05-27)

* **[NEW]** New *documentation* command uses [Sami](https://github.com/fabpot/Sami) to generate HTML API documentation
* **[NEW]** HTML API documentation is published along with coverage information when building under Travis CI

### 0.3.1 (2013-04-30)

* **[FIXED]** Disabled sub-process timeout that caused long running tests to fail
* **[FIXED]** Added User-Agent header to GitHub API client [as required](http://developer.github.com/changes/2013-04-24-user-agent-required)

### 0.3.0 (2013-03-27)

* **[NEW]** Added JUnit XML reporting to PHPUnit configuration
* **[IMPROVED]** Disabled notify-on-install in composer configuration (prevents inflated installation numbers on packagist)

### 0.2.1 (2013-02-26)

* **[BC]** Removed PHP v5.3.3 from Travis CI build configuration

### 0.2.0 (2013-02-21)

* **[FIXED]** Pull-request builds no longer fail due to unavailable secure environment variables
* **[IMPROVED]** Update command will now add Archer-specific entries to .gitignore and .gitattributes if the files already exist
* **[IMPROVED]** Unified oauth/no-oauth Travis CI configurations into a single YAML template

### 0.1.2 (2013-02-17)

* **[FIXED]** Updated to Woodhouse 0.4.2, allows automatic creation of gh-pages branch

### 0.1.1 (2013-02-16)

* **[FIXED]** Updated to Woodhouse 0.4.1, adds user.name/user.email to git config

### 0.1.0 (2013-02-14)

* Initial release
