<?php

namespace PHPSemVerCheckerGit\Console\Command;

use Gitter\Client;
use Gitter\Repository;
use PHPSemVerChecker\Analyzer\Analyzer;
use PHPSemVerChecker\Finder\Finder;
use PHPSemVerChecker\Registry\Registry;
use PHPSemVerChecker\Reporter\Reporter;
use PHPSemVerChecker\Scanner\Scanner;
use PHPSemVerChecker\SemanticVersioning\Level;
use PHPSemVerCheckerGit\Filter\SourceFilter;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\SemVer\expression as SemanticExpression;
use vierbergenlars\SemVer\version as SemanticVersion;

class SuggestCommand extends Command
{
	protected function configure()
	{
		$this->setName('suggest')->setDescription('Compare a semantic versioned tag against a commit and provide a semantic version suggestion')->setDefinition([
			new InputOption('include-before', null,  InputOption::VALUE_OPTIONAL, 'List of paths to include <info>(comma separated)</info>'),
			new InputOption('include-after', null, InputOption::VALUE_OPTIONAL, 'List of paths to include <info>(comma separated)</info>'),
			new InputOption('exclude-before', null,  InputOption::VALUE_REQUIRED, 'List of paths to exclude <info>(comma separated)</info>'),
			new InputOption('exclude-after', null, InputOption::VALUE_REQUIRED, 'List of paths to exclude <info>(comma separated)</info>'),
			new InputOption('tag', 't', InputOption::VALUE_REQUIRED, 'A tag to test against (latest by default)'),
			new InputOption('against', 'a', InputOption::VALUE_REQUIRED, 'What to test against the tag (HEAD by default)'),
			new InputOption('allow-detached', 'd', InputOption::VALUE_NONE, 'Allow suggest to start from a detached HEAD'),
			new InputOption('details', null, InputOption::VALUE_NONE, 'Report the changes on which the suggestion is based'),
		]);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$startTime = microtime(true);

		$targetDirectory = getcwd();
		$tag = $input->getOption('tag');
		$against = $input->getOption('against') ?: 'HEAD';

		$includeBefore = $input->getOption('include-before');
		$excludeBefore = $input->getOption('exclude-before');

		$includeAfter = $input->getOption('include-after');
		$excludeAfter = $input->getOption('exclude-after');

		$client = new Client();

		$repository = $client->getRepository($targetDirectory);

		if ($tag === null) {
			$tag = $this->findLatestTag($repository);
		} else {
			$tag = $this->findTag($repository, $tag);
		}

		if ($tag === null) {
			$output->writeln('<error>No tags to suggest against</error>');
			return;
		}

		$output->writeln('<info>Testing ' . $against . ' against tag: ' . $tag . '</info>');

		$finder = new Finder();
		$sourceFilter = new SourceFilter();
		$beforeScanner = new Scanner();
		$afterScanner = new Scanner();

		$modifiedFiles = $repository->getModifiedFiles($tag, $against);
		$modifiedFiles = array_filter($modifiedFiles, function ($modifiedFile) {
			return substr($modifiedFile, -4) === '.php';
		});

		$initialBranch = $repository->getCurrentBranch();

		if ( ! $input->getOption('allow-detached') && ! $initialBranch) {
			$output->writeln('<error>You are on a detached HEAD, aborting.</error>');
			$output->writeln('<info>If you still wish to run against a detached HEAD, use --allow-detached.</info>');
			return -1;
		}

		// Start with the against commit
		$repository->checkout($against . ' --');

		$sourceAfter = $finder->findFromString($targetDirectory, $includeAfter, $excludeAfter);
		$sourceAfterMatchedCount = count($sourceAfter);
		$sourceAfter = $sourceFilter->filter($sourceAfter, $modifiedFiles);
		$progress = new ProgressBar($output, count($sourceAfter));
		foreach ($sourceAfter as $file) {
			$afterScanner->scan($file);
			$progress->advance();
		}

		$progress->clear();

		// Finish with the tag commit
		$repository->checkout($tag . ' --');

		$sourceBefore = $finder->findFromString($targetDirectory, $includeBefore, $excludeBefore);
		$sourceBeforeMatchedCount = count($sourceBefore);
		$sourceBefore = $sourceFilter->filter($sourceBefore, $modifiedFiles);
		$progress = new ProgressBar($output, count($sourceBefore));
		foreach ($sourceBefore as $file) {
			$beforeScanner->scan($file);
			$progress->advance();
		}

		$progress->clear();

		// Reset repository to initial branch
		if ($initialBranch) {
			$repository->checkout($initialBranch);
		}

		$registryBefore = $beforeScanner->getRegistry();
		$registryAfter = $afterScanner->getRegistry();

		$analyzer = new Analyzer();
		$report = $analyzer->analyze($registryBefore, $registryAfter);

		$tag = new SemanticVersion($tag);
		$newTag = new SemanticVersion($tag);

		$suggestedLevel = $report->getSuggestedLevel();

		if ($suggestedLevel !== Level::NONE) {
			if ($newTag->getPrerelease()) {
				$newTag->inc('prerelease');
			} else {
				if ($newTag->getMajor() < 1) {
					$newTag->inc('minor');
				} else {
					$newTag->inc(strtolower(Level::toString($suggestedLevel)));
				}
			}
		}

		$output->writeln('');
		$output->writeln('<info>Initial semantic version: ' . $tag . '</info>');
		$output->writeln('<info>Suggested semantic version: ' . $newTag . '</info>');

		if ($input->getOption('details')) {
			$reporter = new Reporter($report);
			$reporter->output($output);
		}

		$duration = microtime(true) - $startTime;
		$output->writeln('');
		$output->writeln('[Scanned files] Before: ' . count($sourceBefore) . ' ('.$sourceBeforeMatchedCount.' unfiltered), After: ' . count($sourceAfter) . ' ('.$sourceAfterMatchedCount.'  unfiltered)');
		$output->writeln('Time: ' . round($duration, 3) . ' seconds, Memory: ' . round(memory_get_peak_usage() / 1024 / 1024, 3) . ' MB');
	}

	protected function findLatestTag(Repository $repository)
	{
		return $this->findTag($repository, '*');
	}

	protected function findTag(Repository $repository, $tag)
	{
		$tags = (array)$repository->getTags();

		$tagExpression = new SemanticExpression($tag);

		return $this->getMappedVersionTag($tags, $tagExpression->maxSatisfying($tags));
	}

	private function getMappedVersionTag(array $tags, $versionTag)
	{
		foreach ($tags as $tag) {
			try {
				if (SemanticVersion::eq($versionTag, $tag)) {
					return $tag;
				}
			} catch (RuntimeException $e) {
				// Do nothing
			}
		}
		return null;
	}
}
