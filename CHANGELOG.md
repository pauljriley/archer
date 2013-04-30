# Archer Changelog

### 0.3.1

* Disabled sub-process timeout that caused long running tests to fail
* Added User-Agent header to GitHub API client [as required](http://developer.github.com/changes/2013-04-24-user-agent-required)

### 0.3.0

* Added JUnit XML reporting to PHPUnit configuration
* Disabled notify-on-install in composer configuration (prevents inflated installation numbers on packagist)

### 0.2.1

* Removed PHP v5.3.3 from Travis CI build configuration

### 0.2.0

* Pull-request builds no longer fail due to unavailable secure environment variables
* Update command will now add Archer-specific entries to .gitignore and .gitattributes if the files already exist
* Unified oauth/no-oauth Travis CI configurations into a single YAML template

### 0.1.2

* Updated to Woodhouse 0.4.2, allows automatic creation of gh-pages branch

### 0.1.1

* Updated to Woodhouse 0.4.1, adds user.name/user.email to git config

### 0.1.0

* Initial release
