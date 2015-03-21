<?php

namespace PHPSemVerCheckerGit\Reporter;

use PHPSemVerChecker\Report\Report;
use PHPSemVerChecker\Wrapper\Filesystem;
use PHPSemVerChecker\Reporter\JsonReporter as BaseJsonReporter;

class JsonReporter
{
	/**
	 * @var \PHPSemVerChecker\Report\Report
	 */
	protected $report;
	/**
	 * @var string
	 */
	protected $path;
	/**
	 * @var \PHPSemVerChecker\Wrapper\Filesystem
	 */
	protected $filesystem;
	/**
	 * @var \PHPSemVerChecker\Reporter\JsonReporter
	 */
	protected $baseJsonReporter;
	private $beforeHash;
	private $afterHash;

	/**
	 * @param \PHPSemVerChecker\Report\Report      $report
	 * @param string                               $path
	 * @param string                               $beforeHash
	 * @param string                               $afterHash
	 * @param \PHPSemVerChecker\Wrapper\Filesystem $filesystem
	 */
	public function __construct(Report $report, $path, $beforeHash, $afterHash, Filesystem $filesystem = null)
	{
		$this->report = $report;
		$this->path = $path;
		$this->beforeHash = $beforeHash;
		$this->afterHash = $afterHash;
		$this->filesystem = $filesystem ?: new Filesystem();
		$this->baseJsonReporter = new BaseJsonReporter($report, $path);
	}

	/**
	 * @return array
	 */
	public function getOutput()
	{
		$output = [];
		$output['before'] = $this->beforeHash;
		$output['after'] = $this->afterHash;
		$output['delta'] = $this->baseJsonReporter->getOutput();

		return $output;
	}

	public function output()
	{
		$this->filesystem->write($this->path, json_encode($this->getOutput(), JSON_PRETTY_PRINT));
	}
}
