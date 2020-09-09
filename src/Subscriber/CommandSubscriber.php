<?php

namespace Civi\CompilePlugin\Subscriber;

use Civi\CompilePlugin\Event\CompileListEvent;
use Civi\CompilePlugin\Event\CompileTaskEvent;
use Civi\CompilePlugin\Exception\TaskFailedException;
use Civi\CompilePlugin\Task;
use Composer\IO\IOInterface;

class CommandSubscriber
{

    /**
     * When evaluating the tasks, any task with a 'command'
     * property will (by default) by handled by us.
     *
     * @param \Civi\CompilePlugin\Event\CompileListEvent $e
     */
    public static function applyDefaultCallback(CompileListEvent $e)
    {
        $definitions = $e->getTasksSpecs();
        foreach ($definitions as &$definition) {
            if (!isset($definition['callback']) && isset($definition['command'])) {
                $definition['callback'] = [static::CLASS, 'runTask'];
            }
        }
        $e->setTasksSpecs($definitions);
    }

    public static function runTask(CompileTaskEvent $e)
    {
        /** @var Task $task */
        $task = $e->getTask();
        /** @var IOInterface $io */
        $io = $e->getIO();

        if (empty($task->definition['command'])) {
            throw new \InvalidArgumentException("Invalid or missing command option");
        }

        if ($io->isVerbose()) {
            $io->write("<info>In <comment>{$task->pwd}</comment>, execute <comment>{$task->definition['command']}</comment></info>");
        }

        switch ($task->passthru) {
            case 'always':
                passthru($task->definition['command'], $retVal);
                if ($retVal !== 0) {
                    throw new TaskFailedException($task);
                }
                break;

            case 'error':
                exec($task->definition['command'], $output, $retVal);
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
                exec($task->definition['command'], $output, $retVal);
                if ($retVal !== 0) {
                    throw new TaskFailedException($task);
                }
                break;

            default:
                throw new \InvalidArgumentException("Invalid passthru option: \"$task->passthru\"");
        }
    }
}
