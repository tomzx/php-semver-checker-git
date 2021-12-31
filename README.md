# PHP Semantic Versioning Checker for `git`

[![License](https://poser.pugx.org/tomzx/php-semver-checker-git/license.svg)](https://packagist.org/packages/tomzx/php-semver-checker-git)
[![Latest Stable Version](https://poser.pugx.org/tomzx/php-semver-checker-git/v/stable.svg)](https://packagist.org/packages/tomzx/php-semver-checker-git)
[![Latest Unstable Version](https://poser.pugx.org/tomzx/php-semver-checker-git/v/unstable.svg)](https://packagist.org/packages/tomzx/php-semver-checker-git)
[![Build Status](https://img.shields.io/github/workflow/status/tomzx/php-semver-checker-git/Continuous%20integration.svg)](https://github.com/tomzx/php-semver-checker-git/actions?query=workflow%3A%22Continuous+integration%22)
[![Code Coverage](https://img.shields.io/codecov/c/github/tomzx/php-semver-checker-git)](https://app.codecov.io/gh/tomzx/php-semver-checker-git/)
[![Total Downloads](https://img.shields.io/packagist/dt/tomzx/php-semver-checker-git.svg)](https://packagist.org/packages/tomzx/php-semver-checker-git)

PHP Semantic Versioning Checker for `git` is a console/library which allows you to inspect a set of before and after source code using GIT.

The command line utility will use an existing `git` repository to compare to changesets using anything `git checkout` would accept (sha1, branch, tag). It will checkout in `detached` mode in order not to pollute your list of branches.

**Note** It is strongly suggested you do not run this directly on any repository you do not want to lose. Make a copy of it beforehand and run `php-semver-checker-git` on that copy instead.

## Getting started

As this is still an alpha package, it is not suggested to include `php-semver-checker-git` directly in your composer.json. There are however a couple ways to use the tool:

1. **Preferred method** Download the [latest .phar build](https://psvcg.coreteks.org/php-semver-checker-git.phar). Note that the .phar build is generally less bleeding edge than the following methods.
2. `php composer.phar create-project tomzx/php-semver-checker-git --stability=dev` will clone to a new php-semver-checker-git folder in your current working directory
3. `git clone https://github.com/tomzx/php-semver-checker-git.git` and `php composer.phar install` in the newly cloned directory.

See the example section for examples of how to use the tool.

### Building `php-semver-checker-git.phar`
First, make sure you have [box](https://github.com/box-project/box2) installed. Then, in the base directory, you can run the following command which will generate the `php-semver-checker-git.phar` file.

```bash
box build
```

### Using `php-semver-checker-git` with `GitHub actions`

It is very easy to add `php-semver-checker-git` to your build process and to get a nice report you can check in the `GitHub actions` logs.
In a file under `.github/workflows/`, add the following:

```yaml
name: Continuous integration

on: [push]

jobs:
  build:
    runs-on: ubuntu-latest
    
    steps:
    - name: Checkout code
      uses: actions/checkout@v2
      with:
        fetch-depth: 0

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
          php-version: ${{ matrix.php }}
          tools: composer:v2
          coverage: xdebug

    # Your own CI configuration goes here

    - name: Evaluate semantic versioning
      run: |
        wget https://psvcg.coreteks.org/php-semver-checker-git.phar
        php php-semver-checker-git.phar suggest -vvv --include-before=src --include-after=src --details --allow-detached
```

In order to simplify the above call to `php-semver-checker-git`, we suggest you create a `php-semver-checker-git.yml` configuration file at the root of your project. In it, you can put the following:

```yml
allow-detached: true
details: true
include-before: src
include-after: src
```

With this configuration file, you can update the configuration file created previously:

Before:
```yaml
  # Your own CI configuration goes here

    - name: Evaluate semantic versioning
      run: |
        wget https://psvcg.coreteks.org/php-semver-checker-git.phar
        php php-semver-checker-git.phar suggest -vvv --include-before=src --include-after=src --details --allow-detached
```

After:
```yaml
  # Your own CI configuration goes here

    - name: Evaluate semantic versioning
      run: |
        wget https://psvcg.coreteks.org/php-semver-checker-git.phar
        php php-semver-checker-git.phar suggest -vvv
```

## Example

### Compare two commits (without semantic versioning)

```bash
# arguments are: before-commit/branch/tag after-commit/branch/tag
php bin/php-semver-checker-git compare v1.6.4 v2.0.0 --include-before=src --include-after=src
```

### Compare HEAD against your latest semantic version tag

```bash
php bin/php-semver-checker-git suggest --allow-detached --include-before=src --include-after=src
```

Note: `--allow-detached` is very useful when you are running this command on [`GitHub actions`](https://github.com/features/actions) or any other continuous integration provider. It is necessary when a checkout is done on a particular commit, which makes `HEAD` become `detached`. If this option is not passed to the command, it will abort. This is done because it is impossible to revert the original `detached` branch when the `suggest` command completes.

### Compare HEAD against a specific tag constraint

```bash
php bin/php-semver-checker-git suggest --allow-detached --include-before=src --include-after=src  --tag=~5.0
```

Note: `--tag` supports any semantic versioning constraint such as `<`, `<=`, `>=`, '>', `~x.y.z`, `^x.y.z` `x.y.*`.

## License

The code is licensed under the [MIT license](https://choosealicense.com/licenses/mit/). See [LICENSE](LICENSE).
