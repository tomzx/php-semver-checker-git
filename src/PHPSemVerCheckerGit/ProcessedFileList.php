<?php

namespace PHPSemVerCheckerGit;

use PHPSemVerChecker\Scanner\Scanner;

class ProcessedFileList
{
    /**
     * @var int
     */
    private $originalAmount;
    /**
     * @var string[]
     */
    private $files;
    /**
     * @var Scanner
     */
    private $scanner;

    /**
     * ProcessedFileList constructor.
     * @param int $originalAmount
     * @param string[] $files
     * @param Scanner $scanner
     */
    public function __construct($originalAmount, array &$files, Scanner &$scanner)
    {
        $this->originalAmount = $originalAmount;
        $this->files = $files;
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
     * @return int
     */
    public function getOriginalAmount()
    {
        return $this->originalAmount;
    }

    /**
     * @return string[]
     */
    public function getFiles()
    {
        return $this->files;
    }

}