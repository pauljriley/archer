# Testing

This package provides a set of default PHPUnit, Travis CI and Git configurations for other Icecave Studios projects.

## Executing Tests

The following commands are available for test execution:

* ```vendor/bin/phpunit``` - execute all tests
* ```vendor/bin/phpunit-coverage``` - execute all tests, and produce coverage reports

Coverage reports are available in HTML format in the ```test/report/coverage``` folder.

## Configuration

### Initial Setup

The steps outlined below only need to be completed once.

```sh
# Install the travis command-line utility.
sudo gem install travis json system_timer

# Create a GitHub OAuth token for API access.
# This is used to publish coverage reports to your gh-pages branch.
# This only needs to be done once per GitHub account.
curl -u <github-username> -d '{"scopes":["repo"],"note":"icecave/testing"}' https://api.github.com/authorizations

# You can retrieve a list of existing OAuth tokens with the following command.
curl -u <github-username> https://api.github.com/authorizations
```

### Initializing Projects

To setup a new project, add ```icecave/testing``` to your composer.json configuration as a development dependency, then run:

```sh
# Pull down the icecave/testing package.
composer update --dev

# Initialize .travis.yml and other dot files, the [oauth-token] is only required if you intent to use the coverage report publishing feature.
./vendor/bin/travis-init [oauth-token]
```
