# Testing

This package provides a set of default PHPUnit, Travis CI and Git configurations for other Icecave Studios projects.

## Installation

### GitHub Account Configuration

The steps outlined below only need to be completed once.

In order to publish code coverage reports you need to authorize icecave/testing for API access by creating an OAuth token.
The following command creates the authorization and outputs a JSON packet containing the token. Record this token for later use.
```sh
curl -u <github-username> -d '{"scopes":["repo"],"note":"icecave/testing"}' https://api.github.com/authorizations
```

If you forget your token you can retrieve a full list of authorized applications and their tokens with the following command:
```sh
curl -u <github-username> https://api.github.com/authorizations
```

### Repository

To setup a new project, add `icecave/testing` to your composer.json configuration as a development dependency, then run:

```sh
composer update --dev
```

If you haven't already done so, install the travis command-line utility using the following command:
```sh
sudo gem install travis json system_timer
```

Initialize your project for use with `icecave/testing` with the command below.
This command also installs other common dotfiles such as `.gitignore` and `.gitattributes`
```sh
./vendor/bin/travis-init [oauth-token]
```

Finally, Follow [these instructions](https://help.github.com/articles/creating-project-pages-manually) to setup the `gh-pages` branch.

## Executing Tests Manually

The following commands are available for test execution:

* `vendor/bin/phpunit` - execute all tests
* `vendor/bin/phpunit-coverage` - execute all tests, and produce coverage reports

Coverage reports are available in HTML format in the `test/report/coverage` folder.
