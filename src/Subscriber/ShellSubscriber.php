<?php

namespace Civi\CompilePlugin\Subscriber;

use Civi\CompilePlugin\Event\CompileListEvent;
use Civi\CompilePlugin\Event\CompileTaskEvent;
use Civi\CompilePlugin\Exception\TaskFailedException;
use Civi\CompilePlugin\Task;
use Composer\IO\IOInterface;

class ShellSubscriber
{

    /**
     * When evaluating the tasks, any task with a 'shell'
     * property will (by default) by handled by us.
     *
     * @param \Civi\CompilePlugin\Event\CompileListEvent $e
     */
    public static function applyDefaultCallback(CompileListEvent $e)
    {
        $tasks = $e->getTasks();
        foreach ($tasks as $task) {
            /** @var Task $task */
            if ($task->callback === null && isset($task->definition['shell'])) {
                $task->callback = [static::CLASS, 'runTask'];
            }
        }
    }

    public static function runTask(CompileTaskEvent $e)
    {
        /** @var Task $task */
        $task = $e->getTask();
        /** @var IOInterface $io */
        $io = $e->getIO();

        if (empty($task->definition['shell'])) {
            throw new \InvalidArgumentException("Invalid or missing \"shell\" option");
        }

        if ($io->isVerbose()) {
            $io->write("<info>In <comment>{$task->pwd}</comment>, execute <comment>{$task->definition['shell']}</comment></info>");
        }

        switch ($task->passthru) {
            case 'always':
                passthru($task->definition['shell'], $retVal);
                if ($retVal !== 0) {
                    throw new TaskFailedException($task);
                }
                break;

            case 'error':
                exec($task->definition['shell'], $output, $retVal);
                if ($retVal !== 0) {
                    if (is_callable([$io, 'writeErrorRaw'])) {
                        $io->writeErrorRaw($output);
                    } else {
                        $io->writeError($output);
                    }
                    throw new TaskFailedException($task);
                }
                break;

            case 'never':
                exec($task->definition['shell'], $output, $retVal);
                if ($retVal !== 0) {
                    throw new TaskFailedException($task);
                }
                break;

            default:
                throw new \InvalidArgumentException("Invalid passthru option: \"$task->passthru\"");
        }
    }
}
