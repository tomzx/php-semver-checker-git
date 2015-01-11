<?php

namespace PHPSemVerCheckerGit\Console;

use PHPSemVerCheckerGit\Console\Command\CompareCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication {

	private static $VERSION = '0.1';

	private static $logo = '    ____  ______   _______
   / __ \/ ___/ | / / ___/
  / /_/ (__  )| |/ / /__
 / .___/____/ |___/\___/
/_/
';

	public function __construct()
	{
		parent::__construct('PHP Semantic Versioning Checker GIT by Tom Rochette', static::$VERSION);
	}

	public function getHelp()
	{
		return self::$logo . parent::getHelp();
	}

	protected function getDefaultCommands()
	{
		$commands = parent::getDefaultCommands();
		$commands[] = $this->add(new CompareCommand());
		return $commands;
	}
}