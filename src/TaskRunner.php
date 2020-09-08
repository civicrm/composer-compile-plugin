<?php
namespace Civi\CompilePlugin;

use Civi\CompilePlugin\Command\Task;
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
        $origTimeout = ProcessExecutor::getTimeout();
        try {
            $p = new ProcessExecutor($io);
            foreach ($tasks as $task) {
                /** @var \Civi\CompilePlugin\Task $task */
                $io->write('<info>Compile</info>: ' . ($task->title ?: $task->command));
                if ($io->isVerbose()) {
                    $io->write("<info>In <comment>{$task->pwd}</comment>, execute <comment>{$task->command}</comment></info>");
                }
                $p->execute($task->command, $ignore, $task->pwd);
            }
        }
        finally {
            ProcessExecutor::setTimeout($origTimeout);
        }
    }

}