# Changelog

This project follows [Semantic Versioning 2.0.0](http://semver.org/).

## <a name="unreleased"></a>Unreleased
### Added
* Let Composer automatically install executable in `vendor/bin`

## <a name="v0.5.4"></a>v0.5.4 (2017-01-21)
[Full Changelog](https://github.com/tomzx/php-semver-checker-git/compare/v0.5.3...v0.5.4)
### Fixed
* [#24] Invalid Version: v2.0.0BETA1 when parsing Symfony repository
* [#25] Invalid Version: [[]] when no tag can be found

## <a name="v0.5.3"></a>v0.5.3 (2016-05-13)
[Full Changelog](https://github.com/tomzx/php-semver-checker-git/compare/v0.5.2...v0.5.3)
### Changed
* Update `php-semver-checker` to v0.10.0

## <a name="v0.5.2"></a>v0.5.2 (2016-02-11)
[Full Changelog](https://github.com/tomzx/php-semver-checker-git/compare/v0.5.1...v0.5.2)
### Changed
* Update `php-semver-checker` to v0.9.1

## <a name="v0.5.1"></a>v0.5.1 (2016-01-23)
[Full Changelog](https://github.com/tomzx/php-semver-checker-git/compare/v0.5.0...v0.5.1)
### Changed
* Update `php-semver-checker` to v0.8.1
* Pass an `InspectableArgvInput` object when calling `Application::run()`

## <a name="v0.5.0"></a>v0.5.0 (2016-01-23)
[Full Changelog](https://github.com/tomzx/php-semver-checker-git/compare/v0.4.2...v0.5.0)
### Added
* `php-semver-checker-git` can now be called from `vendor/bin/php-semver-checker-git`
* [#15] Read `php-semver-checker-git.yaml` configuration file by default
* [#17] Add configuration file support

### Changed
* Update `php-semver-checker` to v0.8.0

## <a name="v0.4.2"></a>v0.4.2 (2015-06-25)
[Full Changelog](https://github.com/tomzx/php-semver-checker-git/compare/v0.4.1...v0.4.2)
### Changed
* Update `php-semver-checker` to v0.7.0

## <a name="v0.4.1"></a>v0.4.1 (2015-06-20)
[Full Changelog](https://github.com/tomzx/php-semver-checker-git/compare/v0.4.0...v0.4.1)
### Changed
* Update `php-semver-checker` to v0.6.3

## <a name="v0.4.0"></a>v0.4.0 (2015-05-03)
[Full Changelog](https://github.com/tomzx/php-semver-checker-git/compare/v0.3.0...v0.4.0)
### Added
* Support for includes/excludes through the command line options

### Removed
* Console argument for source-before/after have been replaced with the `--include-before`/`--include-after` options

### Fixed
* Fix a minor issue where a `MINOR` would be suggested instead of a `PATCH` for version <1.0.0

## <a name="v0.3.0"></a>v0.3.0 (2015-05-02)
[Full Changelog](https://github.com/tomzx/php-semver-checker-git/compare/v0.2.0...v0.3.0)
### Added
* Support for self-updating of the phar file through a new `SelfUpdateCommand`
* You can run the `suggest` command and pass it a `--tag=~1.0` option (support semantic versioning constraints)
* Display on what the `suggest` command result is based using the `--details` option

### Changed
* Replace PHPSemVerChecker `JSONReporter` with a PHPSemVerCheckerGIT `JSONReporter`
	* Adds a before/after hash to the exported JSON
* Increased `xdebug.max_nesting_level` to 5000

## <a name="v0.2.0"></a>v0.2.0 (2015-01-25)
[Full Changelog](https://github.com/tomzx/php-semver-checker-git/compare/v0.1.0...v0.2.0)
### Added
* Filter source based on the list of modified files between two given commits
* Added `--to-json` option to the compare command
* Added a `suggest` command which will compare a semantic versioned tag against a commit
* Allowed the `suggest` command to run on detached HEAD by passing the `--allow-detached` option
* Scanned files and time/memory tracking statistics

### Removed
* Removed target from the `compare` command arguments

## <a name="v0.1.0"></a>v0.1.0 (2015-01-23)

Initial release
