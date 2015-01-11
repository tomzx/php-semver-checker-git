<?php

namespace PHPSemVerCheckerGit\Repository;

use TQ\Git\Repository\Repository;

class Git extends Repository
{
	public function checkout($commit)
	{
		return $this->getGit()->{'checkout'}($this->getRepositoryPath(), [$commit, '--']);
	}
}