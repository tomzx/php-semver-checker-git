<?php

namespace PHPSemVerCheckerGit\Console\Command;

use Gitter\Client;
use Gitter\Repository;
use PHPSemVerChecker\Analyzer\Analyzer;
use PHPSemVerChecker\Finder\Finder;
use PHPSemVerChecker\Reporter\Reporter;
use PHPSemVerChecker\Scanner\Scanner;
use PHPSemVerChecker\SemanticVersioning\Level;
use PHPSemVerCheckerGit\Filter\SourceFilter;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\SemVer\expression as SemanticExpression;
use vierbergenlars\SemVer\SemVerException as SemanticVersionException;
use vierbergenlars\SemVer\version as SemanticVersion;

class SuggestCommand extends BaseCommand
{
	/**
	 * @return void
	 */
	protected function configure()
	{
		$this->setName('suggest')->setDescription('Compare a semantic versioned tag against a commit and provide a semantic version suggestion')->setDefinition([
			new InputOption('include-before', null, InputOption::VALUE_REQUIRED, 'List of paths to include <info>(comma separated)</info>'),
			new InputOption('include-after', null, InputOption::VALUE_REQUIRED, 'List of paths to include <info>(comma separated)</info>'),
			new InputOption('exclude-before', null, InputOption::VALUE_REQUIRED, 'List of paths to exclude <info>(comma separated)</info>'),
			new InputOption('exclude-after', null, InputOption::VALUE_REQUIRED, 'List of paths to exclude <info>(comma separated)</info>'),
			new InputOption('tag', 't', InputOption::VALUE_REQUIRED, 'A tag to test against (latest by default)'),
			new InputOption('against', 'a', InputOption::VALUE_REQUIRED, 'What to test against the tag (HEAD by default)'),
			new InputOption('allow-detached', 'd', InputOption::VALUE_NONE, 'Allow suggest to start from a detached HEAD'),
			new InputOption('details', null, InputOption::VALUE_NONE, 'Report the changes on which the suggestion is based'),
			new InputOption('config', null, InputOption::VALUE_REQUIRED, 'A configuration file to configure php-semver-checker-git'),
		]);
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$startTime = microtime(true);

		$targetDirectory = getcwd();
		$tag = $this->config->get('tag');
		$against = $this->config->get('against') ?: 'HEAD';

		$includeBefore = $this->config->get('include-before');
		$excludeBefore = $this->config->get('exclude-before');

		$includeAfter = $this->config->get('include-after');
		$excludeAfter = $this->config->get('exclude-after');

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

		if ( ! $this->config->get('allow-detached') && ! $initialBranch) {
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
				if ($newTag->getMajor() < 1 && $suggestedLevel === Level::MAJOR) {
					$newTag->inc('minor');
				} else {
					$newTag->inc(strtolower(Level::toString($suggestedLevel)));
				}
			}
		}

		$output->writeln('');
		$output->writeln('<info>Initial semantic version: ' . $tag . '</info>');
		$output->writeln('<info>Suggested semantic version: ' . $newTag . '</info>');

		if ($this->config->get('details')) {
			$reporter = new Reporter($report);
			$reporter->output($output);
		}

		$duration = microtime(true) - $startTime;
		$output->writeln('');
		$output->writeln('[Scanned files] Before: ' . count($sourceBefore) . ' (' . $sourceBeforeMatchedCount . ' unfiltered), After: ' . count($sourceAfter) . ' (' . $sourceAfterMatchedCount . '  unfiltered)');
		$output->writeln('Time: ' . round($duration, 3) . ' seconds, Memory: ' . round(memory_get_peak_usage() / 1024 / 1024, 3) . ' MB');
	}

	/**
	 * @param \Gitter\Repository $repository
	 * @return string|null
	 */
	protected function findLatestTag(Repository $repository)
	{
		return $this->findTag($repository, '*');
	}

	/**
	 * @param \Gitter\Repository $repository
	 * @param string             $tag
	 * @return string|null
	 */
	protected function findTag(Repository $repository, $tag)
	{
		$tags = (array)$repository->getTags();
		$tags = $this->filterTags($tags);

		$tagExpression = new SemanticExpression($tag);

		try {
			// Throws an exception if it cannot find a matching version
			$satisfyingTag = $tagExpression->maxSatisfying($tags);
		} catch (SemanticVersionException $e) {
			return null;
		}

		return $this->getMappedVersionTag($tags, $satisfyingTag);
	}

	private function filterTags(array $tags)
	{
		$filteredTags = [];
		foreach ($tags as $tag) {
			try {
				new SemanticVersion($tag);
				$filteredTags[] = $tag;
			} catch (SemanticVersionException $e) {
				// Do nothing
			}
		}
		return $filteredTags;
	}

	/**
	 * @param string[]                                   $tags
	 * @param \vierbergenlars\SemVer\version|string|null $versionTag
	 * @return string|null
	 */
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
