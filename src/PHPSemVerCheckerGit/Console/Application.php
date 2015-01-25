<?php

namespace PHPSemVerCheckerGit\Console;

use PHPSemVerCheckerGit\Console\Command\CompareCommand;
use PHPSemVerCheckerGit\Console\Command\SuggestCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication {

	const VERSION = '@package_version@';

	private static $logo = '                                 _ __
    ____  ______   ___________ _/_/ /_
   / __ \/ ___/ | / / ___/ __ `/ / __/
  / /_/ /__  /| |/ / /__/ /_/ / / /_
 / .___/____/ |___/\___/\__, /_/\__/
/_/                    /____/
';

	public function __construct()
	{
		parent::__construct('PHP Semantic Versioning Checker GIT by Tom Rochette', self::VERSION);
	}

	public function getHelp()
	{
		return self::$logo . parent::getHelp();
	}

	protected function getDefaultCommands()
	{
		$commands = parent::getDefaultCommands();
		$commands[] = $this->add(new CompareCommand());
		$commands[] = $this->add(new SuggestCommand());
		return $commands;
	}
}
