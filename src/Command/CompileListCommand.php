<?php

namespace Civi\CompilePlugin\Command;

use Civi\CompilePlugin\Subscriber\ShellSubscriber;
use Civi\CompilePlugin\Task;
use Civi\CompilePlugin\TaskList;
use Civi\CompilePlugin\TaskRunner;
use Civi\CompilePlugin\Util\TableHelper;
use Composer\Package\Dumper\ArrayDumper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompileListCommand extends \Composer\Command\BaseCommand
{

    protected function configure()
    {
        parent::configure();

        $this
          ->setName('compile:list')
          ->setDescription('Print list of compilation tasks')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $taskList = new TaskList($this->getComposer(), $this->getIO());
        $taskList->load();

        $taskRunner = new TaskRunner($this->getComposer(), $this->getIO());
        $tasks = $taskRunner->sortTasks($taskList->getAll());

        if ($output->isVerbose()) {
            $output->writeln(
                json_encode($tasks, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),
                OutputInterface::OUTPUT_RAW
            );
        } else {
            $header = ['', 'ID', 'Title', 'Action'];
            $rows = [];
            $descAction = function ($task) {
                if ($task->callback === [ShellSubscriber::CLASS, 'runTask']) {
                    return '<info>(shell)</info> ' . $task->definition['shell'];
                } elseif (is_array($task->callback)) {
                    return '<info>(php)</info> ' . $task->callback[0] . '::' . $task->callback[1];
                } elseif (is_string($task->callback)) {
                    return '<info>(php)</info> ' . $task->callback;
                } else {
                    return '<error>(UNRECOGNIZED)</error>';
                }
            };
            foreach ($tasks as $task) {
                /** @var Task $task */
                $rows[] = [
                  $task->active ? '+' : '-',
                  $task->id,
                  $task->title,
                  $descAction($task),
                ];
            }

            TableHelper::showTable($output, $header, $rows);
        }
    }
}
