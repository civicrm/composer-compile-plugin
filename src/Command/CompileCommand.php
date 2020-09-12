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
          ->setDescription('Run compilation tasks')
          ->addOption('all', null, InputOption::VALUE_NONE, 'Run all tasks, regardless of configuration')
          ->addOption('dry-run', 'N', InputOption::VALUE_NONE, 'Dry-run: Print a list of steps to be run')
          ->addArgument('filterExpr', InputArgument::IS_ARRAY, 'Optional filter to match. Ex: \'vendor/package\' or \'vendor/package:id\'')
          ->setHelp(
              "Run compilation steps in all packages\n" .
              "\n" .
              "If no filterExpr is given, then it will execute based on the current\n" .
              "configuration (per composer.json and environment-variables)."
          )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $taskList = new TaskList($this->getComposer(), $this->getIO());
        $taskList->load()->validateAll();

        $taskRunner = new TaskRunner($this->getComposer(), $this->getIO());
        $filters = $input->getArgument('filterExpr');
        if ($input->getOption('all') && !empty($filters)) {
            throw new \InvalidArgumentException("The --all option does not accept filters.");
        } elseif ($input->getOption('all')) {
            $taskRunner->run($taskList->getAll(), $input->getOption('dry-run'));
        } elseif (!empty($filters)) {
            $tasks = $taskList->getByFilters($filters);
            $taskRunner->run(
                $tasks,
                $input->getOption('dry-run')
            );
        } else {
            $taskRunner->runDefault($taskList, $input->getOption('dry-run'));
        }
    }
}
