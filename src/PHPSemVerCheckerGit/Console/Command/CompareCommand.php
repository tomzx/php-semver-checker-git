<?php

namespace PHPSemVerCheckerGit\Console\Command;

use Gitter\Client;
use PHPSemVerChecker\Analyzer\Analyzer;
use PHPSemVerChecker\Finder\Finder;
use PHPSemVerChecker\Reporter\Reporter;
use PHPSemVerChecker\Scanner\Scanner;
use PHPSemVerCheckerGit\Filter\SourceFilter;
use PHPSemVerCheckerGit\Reporter\JsonReporter;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompareCommand extends BaseCommand {
	protected function configure()
	{
		$this
			->setName('compare')
			->setDescription('Compare a set of files to determine what semantic versioning change needs to be done')
			->setDefinition([
				new InputArgument('before', InputArgument::REQUIRED, 'A branch/tag/commit to check'),
				new InputArgument('after', InputArgument::REQUIRED, 'A branch/tag/commit to against'),
				new InputOption('include-before', null,  InputOption::VALUE_OPTIONAL, 'List of paths to include <info>(comma separated)</info>'),
				new InputOption('include-after', null, InputOption::VALUE_OPTIONAL, 'List of paths to include <info>(comma separated)</info>'),
				new InputOption('exclude-before', null,  InputOption::VALUE_REQUIRED, 'List of paths to exclude <info>(comma separated)</info>'),
				new InputOption('exclude-after', null, InputOption::VALUE_REQUIRED, 'List of paths to exclude <info>(comma separated)</info>'),
				new InputOption('config', null, InputOption::VALUE_REQUIRED, 'A configuration file to configure php-semver-checker-git'),
				new InputOption('to-json', null, InputOption::VALUE_REQUIRED, 'Output the result to a JSON file')
			]);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$startTime = microtime(true);

		$targetDirectory = getcwd();
		$commitBefore = $this->config->get('before');
		$commitAfter = $this->config->get('after');

		$includeBefore = $this->config->get('include-before');
		$excludeBefore = $this->config->get('exclude-before');

		$includeAfter = $this->config->get('include-after');
		$excludeAfter = $this->config->get('exclude-after');

		$finder = new Finder();
		$sourceFilter = new SourceFilter();
		$beforeScanner = new Scanner();
		$afterScanner = new Scanner();

		$client = new Client();

		$repository = $client->getRepository($targetDirectory);

		$modifiedFiles = $repository->getModifiedFiles($commitBefore, $commitAfter);
		$modifiedFiles = array_filter($modifiedFiles, function ($modifiedFile) {
			return substr($modifiedFile, -4) === '.php';
		});

		$initialBranch = $repository->getCurrentBranch();

		$repository->checkout($commitBefore . ' --');

		$sourceBefore = $finder->findFromString($targetDirectory, $includeBefore, $excludeBefore);
		$sourceBeforeMatchedCount = count($sourceBefore);
		$sourceBefore = $sourceFilter->filter($sourceBefore, $modifiedFiles);
		$progress = new ProgressBar($output, count($sourceBefore));
		foreach ($sourceBefore as $file) {
			$beforeScanner->scan($file);
			$progress->advance();
		}

		$progress->clear();

		$repository->checkout($commitAfter . ' --');

		$sourceAfter = $finder->findFromString($targetDirectory, $includeAfter, $excludeAfter);
		$sourceAfterMatchedCount = count($sourceAfter);
		$sourceAfter = $sourceFilter->filter($sourceAfter, $modifiedFiles);
		$progress = new ProgressBar($output, count($sourceAfter));
		foreach ($sourceAfter as $file) {
			$afterScanner->scan($file);
			$progress->advance();
		}

		$progress->clear();

		if ($initialBranch) {
			$repository->checkout($initialBranch);
		}

		$progress->clear();

		$registryBefore = $beforeScanner->getRegistry();
		$registryAfter = $afterScanner->getRegistry();

		$analyzer = new Analyzer();
		$report = $analyzer->analyze($registryBefore, $registryAfter);

		$reporter = new Reporter($report);
		$reporter->setFullPath(true);
		$reporter->output($output);

		$toJson = $this->config->get('to-json');
		if ($toJson) {
			$commitBeforeHash = $repository->getCommit($commitBefore)->getHash();
			$commitAfterHash = $repository->getCommit($commitAfter)->getHash();
			$jsonReporter = new JsonReporter($report, $toJson, $commitBeforeHash, $commitAfterHash);
			$jsonReporter->output();
		}

		$duration = microtime(true) - $startTime;
		$output->writeln('');
		$output->writeln('[Scanned files] Before: ' . count($sourceBefore) . ' ('.$sourceBeforeMatchedCount.' unfiltered), After: ' . count($sourceAfter) . ' ('.$sourceAfterMatchedCount.' unfiltered)');
		$output->writeln('Time: ' . round($duration, 3) . ' seconds, Memory: ' . round(memory_get_peak_usage() / 1024 / 1024, 3) . ' MB');
	}
}
