<?php

namespace PHPSemVerCheckerGit;

use Gitter\Repository;
use PHPSemVerChecker\Finder\Finder;
use PHPSemVerChecker\Scanner\Scanner;
use PHPSemVerCheckerGit\Filter\SourceFilter;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class SourceFileProcessor
{
    /**
     * @var \Gitter\Repository
     */
    private $repository;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * @var \PHPSemVerChecker\Finder\Finder
     */
    private $finder;

    /**
     * @var \PHPSemVerCheckerGit\Filter\SourceFilter
     */
    private $filter;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var string[]
     */
    private $modifiedFiles;

    /**
     * SourceFileProcessor constructor.
     * @param PHPSemVerCheckerGit\Filter\SourceFilter $filter
     * @param \Gitter\Repository $repository
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \PHPSemVerChecker\Finder\Finder $finder
     * @param string $directory
     * @param string[] $modifiedFiles
     */
    public function __construct(SourceFilter $filter, Repository $repository, OutputInterface $output, Finder $finder, $directory, array $modifiedFiles)
    {
        $this->repository = $repository;
        $this->output = $output;
        $this->finder = $finder;
        $this->filter = $filter;
        $this->directory = $directory;
        $this->modifiedFiles = [];
        foreach($modifiedFiles as $file) {
            if(substr($file, -4) === '.php') {
                $this->modifiedFiles[] = $file;
            }
        }
    }

    /**
     * @param string $commitIdentifier
     * @param $include
     * @param $exclude
     * @return \PHPSemVerCheckerGit\ProcessedFileList
     */
    public function processFileList($commitIdentifier, $include, $exclude)
    {
        $scanner = new Scanner();
        $this->repository->checkout($commitIdentifier . ' --');
        $unfiltered = $this->finder->findFromString($this->directory, $include, $exclude);
        $source = $this->filter->filter($unfiltered, $this->modifiedFiles);
        $this->scanFileList($scanner, $source);
        return new ProcessedFileList($unfiltered, $source, $scanner);
    }

    /**
     * @param \PHPSemVerChecker\Scanner\Scanner $scanner
     * @param array $files
     */
    private function scanFileList(Scanner $scanner, array $files)
    {
        $progress = new ProgressBar($this->output, count($files));
        foreach ($files as $file) {
            $scanner->scan($file);
            $progress->advance();
        }
        $progress->clear();
    }
}