<?php

namespace Civi\CompilePlugin\Command;

use Civi\CompilePlugin\TaskList;
use Civi\CompilePlugin\TaskRunner;
use Symfony\Component\Console\Input\InputArgument;
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
          ->addOption('dry-run', 'N', InputOption::VALUE_NONE, 'Dry-run: Print a list of steps to be run')
          ->addArgument('filterExpr', InputArgument::IS_ARRAY, 'Optional filter to match. Ex: \'vendor/package\' or \'vendor/package:id\'')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $taskList = new TaskList($this->getComposer(), $this->getIO());
        $taskList->load()->validateAll();

        $filters = $input->getArgument('filterExpr');
        if (empty($filters)) {
            $tasks = $taskList->getAll();
        } else {
            $tasks = $taskList->getByFilters($filters);
        }

        $taskRunner = new TaskRunner($this->getComposer(), $this->getIO());
        $taskRunner->run($tasks, $input->getOption('dry-run'));
    }
}
