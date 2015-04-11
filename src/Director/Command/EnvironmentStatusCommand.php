<?php

namespace Director\Command;

use Director\DirectorApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use TQ\Git\Repository\Repository;
use GitWrapper\GitWrapper;

class EnvironmentStatusCommand extends Command
{
  public $director;

  function __construct(DirectorApplication $director) {
    parent::__construct();
    $this->director = $director;
  }

  protected function configure()
  {
    $this
      ->setName('environment:status')
      ->setDescription('Display the current status of an environment.')
      ->addArgument(
        'app',
        InputArgument::REQUIRED,
        'The app to lookup.'
      )
      ->addArgument(
        'environment',
        InputArgument::REQUIRED,
        'The environment to lookup.'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $app = $this->director->getApp($input->getArgument('app'));

    $environment = $app->getEnvironment($input->getArgument('environment'));

    $output->writeln('<info>PATH:</info> ' . $environment->getSourcePath());
    $output->writeln('<info>BRANCH:</info> ' . $environment->getRepo()->getCurrentBranch());

    // Look for .director.yml
    $config = $environment->getConfig();
    if (empty($config)) {
      $output->writeln('<error>CONFIG:</error> .director.yml not found at ' . $environment->getSourcePath());
    }
    else {
      $output->writeln('<info>CONFIG:</info> Loaded .director.yml');
    }

    // Show git status
    $status = $environment->getRepo()->getStatus();
    if (!empty($status)){
      $wrapper = new GitWrapper();
      $wrapper->streamOutput();
      chdir($environment->getSourcePath());
      $wrapper->git('status');
    }

    // Save to yml
    $this->director->config['apps'][$input->getArgument('app')]['environments'][$input->getArgument('environment')]['config'] = $environment->getConfig();

    $this->director->config['apps'][$input->getArgument('app')]['environments'][$input->getArgument('environment')]['git_ref'] =
      $environment->getRepo()->getCurrentBranch();
    $this->director->saveData();

    $output->writeln("Saved environment details.");
  }
}