<?php

namespace PHPSemVerCheckerGit;

use PHPSemVerChecker\Scanner\Scanner;

class ProcessedFileList
{
    /**
     * @var int
     */
    private $unfilteredCount;

    /**
     * @var int
     */
    private $filteredCount;

    /**
     * @var string[]
     */
    private $filtered;

    /**
     * @var string[]
     */
    private $unfiltered;

    /**
     * @var \PHPSemVerChecker\Scanner\Scanner
     */
    private $scanner;

    /**
     * ProcessedFileList constructor.
     * @param string[] $unfiltered
     * @param string[] $filtered
     * @param \PHPSemVerChecker\Scanner\Scanner $scanner
     */
    public function __construct(array $unfiltered, array $filtered, Scanner &$scanner)
    {
        $this->unfilteredCount = count($unfiltered);
        $this->filteredCount = count($filtered);
        $this->filtered = $filtered;
        $this->unfiltered = $unfiltered;
        $this->scanner = $scanner;
    }

    /**
     * @return \PHPSemVerChecker\Scanner\Scanner
     */
    public function getScanner()
    {
        return $this->scanner;
    }

    /**
     * @return string[]
     */
    public function getFiltered()
    {
        return $this->filtered;
    }

    /**
     * @return int
     */
    public function getFilteredCount()
    {
        return $this->filteredCount;
    }

    /**
     * @return int
     */
    public function getUnfilteredCount()
    {
        return $this->unfilteredCount;
    }

    /**
     * @return string[]
     */
    public function getUnfiltered()
    {
        return $this->unfiltered;
    }
}