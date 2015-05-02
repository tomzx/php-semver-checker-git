# PHP Semantic Versioning Checker for `git`

[![License](https://poser.pugx.org/tomzx/php-semver-checker-git/license.svg)](https://packagist.org/packages/tomzx/php-semver-checker-git)
[![Latest Stable Version](https://poser.pugx.org/tomzx/php-semver-checker-git/v/stable.svg)](https://packagist.org/packages/tomzx/php-semver-checker-git)
[![Latest Unstable Version](https://poser.pugx.org/tomzx/php-semver-checker-git/v/unstable.svg)](https://packagist.org/packages/tomzx/php-semver-checker-git)
[![Build Status](https://img.shields.io/travis/tomzx/php-semver-checker-git.svg)](https://travis-ci.org/tomzx/php-semver-checker-git)
[![Code Quality](https://img.shields.io/scrutinizer/g/tomzx/php-semver-checker-git.svg)](https://scrutinizer-ci.com/g/tomzx/php-semver-checker-git/code-structure)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/tomzx/php-semver-checker-git.svg)](https://scrutinizer-ci.com/g/tomzx/php-semver-checker-git)
[![Total Downloads](https://img.shields.io/packagist/dt/tomzx/php-semver-checker-git.svg)](https://packagist.org/packages/tomzx/php-semver-checker-git)

PHP Semantic Versioning Checker for `git` is a console/library which allows you to inspect a set of before and after source code using GIT.

The command line utility will use an existing `git` repository to compare to changesets using anything `git checkout` would accept (sha1, branch, tag). It will checkout in `detached` mode in order not to pollute your list of branches.

**Note** It is strongly suggested you do not run this directly on any repository you do not want to lose. Make a copy of it beforehand and run `php-semver-checker-git` on that copy instead.

## Getting started

As this is still an alpha package, it is not suggested to include `php-semver-checker-git` directly in your composer.json. There are however a couple ways to use the tool:

1. `php composer.phar create-project tomzx/php-semver-checker-git --stability=dev` will clone to a new php-semver-checker-git folder in your current working directory
2. `git clone https://github.com/tomzx/php-semver-checker-git.git` and `php composer.phar install` in the newly cloned directory.

You may also download the [latest .phar build](https://github.com/tomzx/php-semver-checker-git/releases). Note that the .phar build is generally less bleeding edge than the previously mentioned methods.

See the example section for examples of how to use the tool.

## Example

### Compare two commits (without semantic versioning)

```bash
# arguments are: before-commit/branch/tag after-commit/branch/tag before-source after-source
php bin/php-semver-checker-git compare v1.6.4 v2.0.0 src src
```

### Compare HEAD against your latest semantic version tag

```bash
# arguments are: before-source after-source
php bin/php-semver-checker-git suggest src src --allow-detached
```

Note: `--allow-detached` is very useful when you are running this command on [`travis-ci`](https://travis-ci.org) or any other continuous integration provider. It is necessary when a checkout is done on a particular commit, which makes `HEAD` become `detached`. If this option is not passed to the command, it will abort. This is done because it is impossible to revert the original `detached` branch when the `suggest` command completes.

## License

The code is licensed under the [MIT license](http://choosealicense.com/licenses/mit/). See [LICENSE](LICENSE).