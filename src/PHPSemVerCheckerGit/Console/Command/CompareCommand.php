<?php

namespace PHPSemVerCheckerGit\Console\Command;

use File_Iterator_Facade;
use Gitter\Client;
use PHPSemVerChecker\Analyzer\Analyzer;
use PHPSemVerChecker\Reporter\Reporter;
use PHPSemVerChecker\Scanner\Scanner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompareCommand extends Command {
	protected function configure()
	{
		$this
			->setName('compare')
			->setDescription('Compare a set of files to determine what semantic versioning change needs to be done')
			->setDefinition([
				new InputArgument('target', InputArgument::REQUIRED, 'Directory where you git repository is located (ex my-test)'),
				new InputArgument('before', InputArgument::REQUIRED, 'A branch/tag/commit to check'),
				new InputArgument('after', InputArgument::REQUIRED, 'A branch/tag/commit to against'),
				new InputArgument('source-before', InputArgument::REQUIRED, 'A directory to check (ex my-test/src)'),
				new InputArgument('source-after', InputArgument::REQUIRED, 'A directory to check against (ex my-test/src)'),
			]);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$target = $input->getArgument('target');
		$before = $input->getArgument('before');
		$after = $input->getArgument('after');
		$beforeFiles = $input->getArgument('source-before');
		$afterFiles = $input->getArgument('source-after');

		$fileIterator = new File_Iterator_Facade;
		$beforeScanner = new Scanner();
		$afterScanner = new Scanner();

		$client = new Client();

		$repository = $client->getRepository($target);

		$initialBranch = $repository->getCurrentBranch();

		$repository->checkout($before . ' --');

		$beforeFiles = $fileIterator->getFilesAsArray($beforeFiles, '.php');
		$progress = new ProgressBar($output, count($beforeFiles));
		foreach ($beforeFiles as $file) {
			$beforeScanner->scan($file);
			$progress->advance();
		}

		$progress->clear();

		$repository->checkout($after . ' --');

		$afterFiles = $fileIterator->getFilesAsArray($afterFiles, '.php');
		$progress = new ProgressBar($output, count($afterFiles));
		foreach ($afterFiles as $file) {
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
		$reporter->output($output);
	}
}
