<?php

namespace PHPSemVerCheckerGit\Console\Command;

use Gitter\Client;
use Gitter\Repository;
use PHPSemVerChecker\Analyzer\Analyzer;
use PHPSemVerChecker\Finder\Finder;
use PHPSemVerChecker\Reporter\Reporter;
use PHPSemVerChecker\SemanticVersioning\Level;
use PHPSemVerCheckerGit\Filter\SourceFilter;
use PHPSemVerCheckerGit\SourceFileProcessor;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\SemVer\expression as SemanticExpression;
use vierbergenlars\SemVer\SemVerException as SemanticVersionException;
use vierbergenlars\SemVer\version as SemanticVersion;
use PHPSemVerChecker\Report\Report;

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
     * @param string $directory
     * @return Repository
     */
	private function getRepository($directory)
    {
        $client = new Client();
        return $client->getRepository($directory);
    }

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
    {
		$startTime = microtime(true);

		$targetDirectory = getcwd();
		$against = $this->config->get('against') ?: 'HEAD';

		$repository = $this->getRepository($targetDirectory);

		$tag = $this->getInitialTag($repository);

		if ($tag === null) {
			$output->writeln('<error>No tags to suggest against</error>');
			return 1;
		}

		$output->writeln('<info>Testing ' . $against . ' against tag: ' . $tag . '</info>');

        $sourceFileProcessor = new SourceFileProcessor(
            new SourceFilter(),
            $repository,
            $output,
            new Finder(),
            $targetDirectory,
            $repository->getModifiedFiles($tag, $against)
        );

		$initialBranch = $repository->getCurrentBranch();

		if ( ! $this->config->get('allow-detached') && ! $initialBranch) {
			$output->writeln('<error>You are on a detached HEAD, aborting.</error>');
			$output->writeln('<info>If you still wish to run against a detached HEAD, use --allow-detached.</info>');
			return -1;
		}
		$after = $sourceFileProcessor->processFileList(
            $against,
            $this->config->get('include-after'),
            $this->config->get('exclude-after')
        );
        $before = $sourceFileProcessor->processFileList(
            $tag,
            $this->config->get('include-before'),
            $this->config->get('exclude-before')
        );
		// Reset repository to initial branch
		if ($initialBranch) {
			$repository->checkout($initialBranch);
		}

		$analyzer = new Analyzer();
		$report = $analyzer->analyze($before->getScanner()->getRegistry(), $after->getScanner()->getRegistry());

		$tag = new SemanticVersion($tag);
		$newTag = $this->getNextTag($report, $tag);

		$output->write(
		    array(
                '',
                '<info>Initial semantic version: ' . $tag . '</info>',
                '<info>Suggested semantic version: ' . $newTag . '</info>'
            ),
            true
        );

		if ($this->config->get('details')) {
			$reporter = new Reporter($report);
			$reporter->output($output);
		}

		$duration = microtime(true) - $startTime;
		$output->write(
		    array(
		        '',
                '[Scanned files] Before: ' . count($before->getFiles()) . ' (' . $before->getOriginalAmount() . ' unfiltered), After: ' . count($after->getFiles()) . ' (' . $after->getOriginalAmount() . '  unfiltered)',
                'Time: ' . round($duration, 3) . ' seconds, Memory: ' . round(memory_get_peak_usage() / 1024 / 1024, 3) . ' MB'
            ),
            true
        );
		return 0;
	}

    /**
     * @param Report $report
     * @param SemanticVersion $tag
     * @return SemanticVersion
     */
	private function getNextTag(Report $report, SemanticVersion $tag)
    {
        $newTag = new SemanticVersion($tag);
        $suggestedLevel = $report->getSuggestedLevel();
        if ($suggestedLevel === Level::NONE) {
            return $newTag;
        }
        if ($newTag->getPrerelease()) {
            $newTag->inc('prerelease');
            return $newTag;
        }
        if ($newTag->getMajor() < 1 && $suggestedLevel === Level::MAJOR) {
            $newTag->inc('minor');
            return $newTag;
        }
        $newTag->inc(strtolower(Level::toString($suggestedLevel)));
        return $newTag;
    }

    /**
     * @param Repository $repository
     * @return null|string
     */
	private function getInitialTag(Repository $repository)
    {
        $tag = $this->config->get('tag');
        if ($tag === null) {
            return $this->findLatestTag($repository);
        }
        return $this->findTag($repository, $tag);
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
