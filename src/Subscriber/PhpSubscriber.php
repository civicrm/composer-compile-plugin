<?php

namespace Civi\CompilePlugin\Subscriber;

use Civi\CompilePlugin\Event\CompileListEvent;
use Civi\CompilePlugin\Event\CompileTaskEvent;
use Civi\CompilePlugin\Task;
use Civi\CompilePlugin\Util\ShellRunner;

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
                $phpMethod = $task->definition['php-method'];
                if (self::isWellFormedMethod($phpMethod)) {
                    $task->callback = [static::CLASS, 'runTask'];
                } else {
                    throw new \InvalidArgumentException("Malformed callback");
                }
            }
        }
    }

    public static function runTask(CompileTaskEvent $event)
    {
        // Surely there's a smarter way to get this?
        $vendorPath = $event->getComposer()->getConfig()->get('vendor-dir');
        $autoload =  $vendorPath . '/autoload.php';
        if (!file_exists($autoload)) {
            throw new \RuntimeException("CompilePlugin: Failed to locate autoload.php");
        }
        $cmd = '@php -r ' . escapeshellarg(sprintf(
            'require_once %s; %s(json_decode(base64_decode(%s), 1));',
            var_export($autoload, 1),
            $event->getTask()->definition['php-method'],
            var_export(base64_encode(json_encode($event->getTask()->definition)), 1)
        ));

        $r = new ShellRunner($event->getComposer(), $event->getIO());
        $r->run($cmd);
    }

    /**
     * @param string $phpMethod
     * @return bool
     */
    private static function isWellFormedMethod($phpMethod)
    {
        if (!is_string($phpMethod)) {
            return false;
        }
        $parts = explode('::', $phpMethod);
        if (count($parts) > 2) {
            return false;
        }
        return preg_match(';^[a-zA-Z0-9_\\\:]+$;', $phpMethod);
    }
}
