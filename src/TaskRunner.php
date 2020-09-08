<?php
namespace Civi\CompilePlugin;

use Composer\IO\IOInterface;
use Composer\Util\ProcessExecutor;

class TaskRunner
{

    /**
     * @return static
     */
    public static function create() {
        return new static();
    }

    /**
     * Execute a list of compilation tasks.
     *
     * @param \Composer\IO\IOInterface $io
     * @param Task[] $tasks
     */
    public function run(IOInterface $io, array $tasks)
    {
        usort($tasks, function($a, $b){
            $fields = ['weight', 'packageWeight', 'naturalWeight'];
            foreach ($fields as $field) {
                if ($a->{$field} > $b->{$field}) {
                    return 1;
                }
                elseif ($a->{$field} < $b->{$field}) {
                    return -1;
                }
            }
            return 0;
        });

        $origTimeout = ProcessExecutor::getTimeout();
        $p = new ProcessExecutor($io);
        foreach ($tasks as $task) {
            /** @var \Civi\CompilePlugin\Task $task */
            if (!$task->active) {
                $io->write('<error>Skip</error>: ' . ($task->title ?: $task->command),
                  true, IOInterface::VERBOSE);
                continue;
            }

            $io->write('<info>Compile</info>: ' . ($task->title ?: $task->command));
            if ($io->isVerbose()) {
                $io->write("<info>In <comment>{$task->pwd}</comment>, execute <comment>{$task->command}</comment></info>");
            }

            try {
                ProcessExecutor::setTimeout($task->timeout);
                $p->execute($task->command, $ignore, $task->pwd);
            } finally {
                ProcessExecutor::setTimeout($origTimeout);
            }
        }
    }

}
