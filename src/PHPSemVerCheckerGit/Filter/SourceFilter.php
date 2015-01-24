<?php

namespace PHPSemVerCheckerGit\Filter;

class SourceFilter
{
	public function filter(array $beforeFiles, array $modifiedFiles)
	{
		$beforeFiles = array_flip($beforeFiles);

		$filteredFiles = [];
		foreach ($modifiedFiles as $modifiedFile) {
			$modifiedFile = realpath($modifiedFile);
			if (isset($beforeFiles[$modifiedFile])) {
				$filteredFiles[] = $modifiedFile;
			}
		}

		return $filteredFiles;
	}
}
