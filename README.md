# Testing

This package provides a set of default PHPUnit, Travis CI and Git configurations for other Icecave Studios projects.

## Executing Tests

The following commands are available for test execution:

* ```vendor/bin/phpunit``` - execute all tests
* ```vendor/bin/phpunit-coverage``` - execute all tests, and produce coverage reports

Coverage reports are available in HTML format in the ```test/report/coverage``` folder.

## Configuration

1. Install the Travis binary.

```sh
sudo gem install travis
```

2. Create a GitHub OAuth token.

This only needs to be done once per GitHub account/organisation.

```sh
curl -u '<github-username>' \
     -d '{"scopes":["repo"],"note":"icecave/testing"}' \
     https://api.github.com/authorizations
```

This will produce a JSON object containing a token.

3. Update your ```.travis.yml``` file.

```sh
./vendor/bin/update-dotfiles <token>
```
