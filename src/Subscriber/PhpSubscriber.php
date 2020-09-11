<?php

namespace Civi\CompilePlugin\Subscriber;

use Civi\CompilePlugin\Event\CompileListEvent;
use Civi\CompilePlugin\Event\CompileTaskEvent;
use Civi\CompilePlugin\Exception\TaskFailedException;
use Civi\CompilePlugin\Task;
use Composer\IO\IOInterface;

class PhpSubscriber
{

  /**
   * When evaluating the tasks, any task with a 'php-method'
   * property will (by default) by handled by us.
   *
   * @param \Civi\CompilePlugin\Event\CompileListEvent $e
   */
    public static function applyDefaultCallback(CompileListEvent $e)
    {
        $tasks = $e->getTasks();
        foreach ($tasks as $task) {
          /** @var Task $task */
            if ($task->callback === null && isset($task->definition['php-method'])) {
                $task->callback = $task->definition['php-method'];
            }
        }
    }
}
