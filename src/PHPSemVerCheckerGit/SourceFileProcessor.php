<?php

namespace PHPSemVerCheckerGit;


use PHPSemVerChecker\Finder\Finder;
use PHPSemVerChecker\Scanner\Scanner;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use PHPSemVerCheckerGit\Filter\SourceFilter;
use Gitter\Repository;

class SourceFileProcessor
{
    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var Finder
     */
    private $finder;
    /**
     * @var SourceFilter
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
     * @param SourceFilter &$filter
     * @param Repository $repository
     * @param OutputInterface $output
     * @param Finder $finder
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
     * @return ProcessedFileList
     */
    public function processFileList(
        $commitIdentifier,
        $include,
        $exclude
    ) {
        $scanner = new Scanner();
        $this->repository->checkout($commitIdentifier . ' --');
        $source = $this->finder->findFromString($this->directory, $include, $exclude);
        $count = count($source);
        $source = $this->filter->filter($source, $this->modifiedFiles);
        $this->scanFileList($scanner, $source);
        return new ProcessedFileList($count, $source, $scanner);
    }

    /**
     * @param Scanner $scanner
     * @param array $files
     */
    private function scanFileList(Scanner &$scanner, array &$files)
    {
        $progress = new ProgressBar($this->output, count($files));
        foreach ($files as $file) {
            $scanner->scan($file);
            $progress->advance();
        }
        $progress->clear();
    }

}