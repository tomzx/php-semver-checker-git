<?php

namespace PHPSemVerCheckerGit\Console\Command;

use File_Iterator_Facade;
use Gitter\Client;
use PHPSemVerChecker\Analyzer\Analyzer;
use PHPSemVerChecker\Reporter\JsonReporter;
use PHPSemVerChecker\Reporter\Reporter;
use PHPSemVerChecker\Scanner\Scanner;
use PHPSemVerCheckerGit\Filter\SourceFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompareCommand extends Command {
	protected function configure()
	{
		$this
			->setName('compare')
			->setDescription('Compare a set of files to determine what semantic versioning change needs to be done')
			->setDefinition([
				new InputArgument('before', InputArgument::REQUIRED, 'A branch/tag/commit to check'),
				new InputArgument('after', InputArgument::REQUIRED, 'A branch/tag/commit to against'),
				new InputArgument('source-before', InputArgument::REQUIRED, 'A directory to check (ex my-test/src)'),
				new InputArgument('source-after', InputArgument::REQUIRED, 'A directory to check against (ex my-test/src)'),
				new InputOption('to-json', null, InputOption::VALUE_REQUIRED, 'Output the result to a JSON file')
			]);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$startTime = microtime(true);

		$target = getcwd();
		$commitBefore = $input->getArgument('before');
		$commitAfter = $input->getArgument('after');
		$sourceBefore = $input->getArgument('source-before');
		$sourceAfter = $input->getArgument('source-after');

		$fileIterator = new File_Iterator_Facade;
		$sourceFilter = new SourceFilter();
		$beforeScanner = new Scanner();
		$afterScanner = new Scanner();

		$client = new Client();

		$repository = $client->getRepository($target);

		$modifiedFiles = $repository->getModifiedFiles($commitBefore, $commitAfter);
		$modifiedFiles = array_filter($modifiedFiles, function ($modifiedFile) {
			return substr($modifiedFile, -4) === '.php';
		});

		$initialBranch = $repository->getCurrentBranch();

		$repository->checkout($commitBefore . ' --');

		$sourceBefore = $fileIterator->getFilesAsArray($sourceBefore, '.php');
		$sourceBeforeMatchedCount = count($sourceBefore);
		$sourceBefore = $sourceFilter->filter($sourceBefore, $modifiedFiles);
		$progress = new ProgressBar($output, count($sourceBefore));
		foreach ($sourceBefore as $file) {
			$beforeScanner->scan($file);
			$progress->advance();
		}

		$progress->clear();

		$repository->checkout($commitAfter . ' --');

		$sourceAfter = $fileIterator->getFilesAsArray($sourceAfter, '.php');
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

		$toJson = $input->getOption('to-json');
		if ($toJson) {
			$jsonReporter = new JsonReporter($report, $toJson);
			$jsonReporter->output();
		}

		$duration = microtime(true) - $startTime;
		$output->writeln('');
		$output->writeln('[Scanned files] Before: ' . count($sourceBefore) . ' ('.$sourceBeforeMatchedCount.' unfiltered), After: ' . count($sourceAfter) . ' ('.$sourceAfterMatchedCount.'  unfiltered)');
		$output->writeln('Time: ' . round($duration, 3) . ' seconds, Memory: ' . round(memory_get_peak_usage() / 1024 / 1024, 3) . ' MB');
	}
}
