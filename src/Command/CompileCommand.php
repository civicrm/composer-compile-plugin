<?php

namespace Civi\CompilePlugin\Command;

use Civi\CompilePlugin\TaskList;
use Civi\CompilePlugin\TaskRunner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompileCommand extends \Composer\Command\BaseCommand
{

    protected function configure()
    {
        parent::configure();

        $this
          ->setName('compile')
          ->setDescription('Run compilation steps in all packages')
          // ->addOption('dry-run', 'N', InputOption::VALUE_NONE, 'Dry-run: Print a list of steps to be run')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $taskList = new TaskList($this->getComposer(), $this->getIO());
        $taskList->load();
        $taskRunner = new TaskRunner($this->getComposer(), $this->getIO());
        $taskRunner->run($taskList->getAll());
    }

}
