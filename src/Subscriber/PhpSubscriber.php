<?php

namespace Civi\CompilePlugin\Subscriber;

use Civi\CompilePlugin\Event\CompileEvents;
use Civi\CompilePlugin\Event\CompileListEvent;
use Civi\CompilePlugin\Event\CompileTaskEvent;
use Civi\CompilePlugin\Task;
use Civi\CompilePlugin\Util\ShellRunner;
use Composer\EventDispatcher\EventSubscriberInterface;

class PhpSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
          CompileEvents::POST_COMPILE_LIST => 'applyDefaultCallback'
        ];
    }

    /**
     * When evaluating the tasks, any task with a 'php-method'
     * property will (by default) by handled by us.
     *
     * @param \Civi\CompilePlugin\Event\CompileListEvent $e
     */
    public function applyDefaultCallback(CompileListEvent $e)
    {
        $tasks = $e->getTasks();
        foreach ($tasks as $task) {
          /** @var Task $task */
            if ($task->callback === null && isset($task->definition['php-method'])) {
                $phpMethods = (array) $task->definition['php-method'];
                foreach ($phpMethods as $phpMethod) {
                    if (self::isWellFormedMethod($phpMethod)) {
                        $task->callback = [$this, 'runTask'];
                    } else {
                        throw new \InvalidArgumentException("Malformed callback: " . json_encode($phpMethod, JSON_UNESCAPED_SLASHES));
                    }
                }
            }
        }
    }

    public function runTask(CompileTaskEvent $event)
    {
        // Surely there's a smarter way to get this?
        $vendorPath = $event->getComposer()->getConfig()->get('vendor-dir');
        $autoload =  $vendorPath . '/autoload.php';
        if (!file_exists($autoload)) {
            throw new \RuntimeException("CompilePlugin: Failed to locate autoload.php");
        }

        $phpMethods = (array) $event->getTask()->definition['php-method'];
        foreach ($phpMethods as $phpMethod) {
            $cmd = '@php -r ' . escapeshellarg(sprintf(
                'require_once %s; %s(json_decode(base64_decode(%s), 1));',
                var_export($autoload, 1),
                $phpMethod,
                var_export(base64_encode(json_encode($event->getTask()->definition)), 1)
            ));

            $r = new ShellRunner($event->getComposer(), $event->getIO());
            $r->run($cmd);
        }
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
