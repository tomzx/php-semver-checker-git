# PHP Semantic Versioning Checker for `git`

[![Build Status](https://travis-ci.org/tomzx/php-semver-checker-git.svg)](https://travis-ci.org/tomzx/php-semver-checker-git)
[![Total Downloads](https://poser.pugx.org/tomzx/php-semver-checker-git/downloads.svg)](https://packagist.org/packages/tomzx/php-semver-checker-git)
[![Latest Stable Version](https://poser.pugx.org/tomzx/php-semver-checker-git/v/stable.svg)](https://packagist.org/packages/tomzx/php-semver-checker-git)
[![Latest Unstable Version](https://poser.pugx.org/tomzx/php-semver-checker-git/v/unstable.svg)](https://packagist.org/packages/tomzx/php-semver-checker-git)
[![License](https://poser.pugx.org/tomzx/php-semver-checker-git/license.svg)](https://packagist.org/packages/tomzx/php-semver-checker-git)

PHP Semantic Versioning Checker for `git` is a console/library which allows you to inspect a set of before and after source code using GIT.

The command line utility will use an existing `git` repository to compare to changesets using anything `git checkout` would accept (sha1, branch, tag). It will checkout in `detached` mode in order not to pollute your list of branches.

**Note** It is strongly suggested you do not run this directly on any repository you do not want to lose. Make a copy of it beforehand and run `php-semver-checker-git` on that copy instead.

## Example

```bash
# arguments are: repository-directory before-commit/branch/tag after-commit/branch/tag before-source after-source
php bin/php-semver-checker-git compare laravel-framework v4.2.15 v4.2.16 laravel-framework/src laravel-framework/src

Suggested semantic versioning change: MAJOR

CLASS
LEVEL	LOCATION	REASON
MAJOR	src/Illuminate/Database/Eloquent/Model.php#2550 Illuminate/Database/Eloquent/Model::getMutatorMethod	Method has been removed.
PATCH	src/Illuminate/Database/Eloquent/Model.php#243 __construct	Method implementation changed.
PATCH	src/Illuminate/Database/Eloquent/Model.php#322 addGlobalScope	Method implementation changed.
PATCH	src/Illuminate/Database/Eloquent/Model.php#333 hasGlobalScope	Method implementation changed.
PATCH	src/Illuminate/Database/Eloquent/Model.php#344 getGlobalScope	Method implementation changed.
PATCH	src/Illuminate/Database/Eloquent/Model.php#357 getGlobalScopes	Method implementation changed.
[... cut for brievity ...]

FUNCTION
LEVEL	LOCATION	REASON
```

## License

The code is licensed under the [MIT license](http://choosealicense.com/licenses/mit/). See license.txt.