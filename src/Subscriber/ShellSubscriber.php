<?php

namespace Civi\CompilePlugin\Subscriber;

use Civi\CompilePlugin\Event\CompileEvents;
use Civi\CompilePlugin\Event\CompileListEvent;
use Civi\CompilePlugin\Event\CompileTaskEvent;
use Civi\CompilePlugin\Exception\TaskFailedException;
use Civi\CompilePlugin\Task;
use Civi\CompilePlugin\Util\ShellRunner;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;

class ShellSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
          CompileEvents::POST_COMPILE_LIST => 'applyDefaultCallback'
        ];
    }

    /**
     * When evaluating the tasks, any task with a 'shell'
     * property will (by default) by handled by us.
     *
     * @param \Civi\CompilePlugin\Event\CompileListEvent $e
     */
    public function applyDefaultCallback(CompileListEvent $e)
    {
        $tasks = $e->getTasks();
        foreach ($tasks as $task) {
            /** @var Task $task */
            if ($task->callback === null && isset($task->definition['shell'])) {
                $task->callback = [$this, 'runTask'];
            }
        }
    }

    public function runTask(CompileTaskEvent $e)
    {
        /** @var Task $task */
        $task = $e->getTask();

        if (empty($task->definition['shell'])) {
            throw new \InvalidArgumentException("Invalid or missing \"shell\" option");
        }

        $r = new ShellRunner($e->getComposer(), $e->getIO());
        $shellCmds = (array) $task->definition['shell'];
        foreach ($shellCmds as $shellCmd) {
            $r->run($shellCmd);
        }
    }
}
