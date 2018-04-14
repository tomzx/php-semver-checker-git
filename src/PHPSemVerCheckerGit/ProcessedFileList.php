<?php

namespace PHPSemVerCheckerGit;

use PHPSemVerChecker\Scanner\Scanner;

class ProcessedFileList
{
    /**
     * @var int
     */
    private $unfilteredAmount;

    /**
     * @var int
     */
    private $filteredAmount;

    /**
     * @var string[]
     */
    private $filtered;

    /**
     * @var string[]
     */
    private $unfiltered;

    /**
     * @var Scanner
     */
    private $scanner;

    /**
     * ProcessedFileList constructor.
     * @param string[] $unfiltered
     * @param string[] $filtered
     * @param Scanner $scanner
     */
    public function __construct(array $unfiltered, array $filtered, Scanner &$scanner)
    {
        $this->unfilteredAmount = count($unfiltered);
        $this->filteredAmount = count($filtered);
        $this->filtered = $filtered;
        $this->unfiltered = $unfiltered;
        $this->scanner = $scanner;
    }

    /**
     * @return Scanner
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
    public function getFilteredAmount()
    {
        return $this->filteredAmount;
    }

    /**
     * @return int
     */
    public function getUnfilteredAmount()
    {
        return $this->unfilteredAmount;
    }

    /**
     * @return string[]
     */
    public function getUnfiltered()
    {
        return $this->unfiltered;
    }
}