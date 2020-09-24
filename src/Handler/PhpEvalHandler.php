<?php

namespace Civi\CompilePlugin\Handler;

use Civi\CompilePlugin\Event\CompileTaskEvent;
use Civi\CompilePlugin\Util\ShellRunner;

/**
 * Class PhpEvalHandler
 * @package Civi\CompilePlugin\Handler
 *
 * This implements support for run-steps based on `@php-eval <phpcode>`.
 */
class PhpEvalHandler
{
    /**
     * @param \Civi\CompilePlugin\Event\CompileTaskEvent $event
     * @param string $runType
     * @param string $phpEval
     *   Ex: 'echo "Hello world";'
     */
    public function runTask(CompileTaskEvent $event, $runType, $phpEval)
    {
        // Surely there's a smarter way to get this?
        $vendorPath = $event->getComposer()->getConfig()->get('vendor-dir');
        $autoload =  $vendorPath . '/autoload.php';
        if (!file_exists($autoload)) {
            throw new \RuntimeException("CompilePlugin: Failed to locate autoload.php");
        }

        if (strpos($phpEval, "\n") !== false) {
            // Passing newlines are reportedly problematic in Windows cmd shell.
            throw new \RuntimeException("CompilePlugin: Multiline eval is not permitted");
        }

        $cmd = '@php -r ' . escapeshellarg(sprintf(
            'require_once %s; %s',
            var_export($autoload, 1),
            $phpEval
        ));

        $r = new ShellRunner($event->getComposer(), $event->getIO());
        $r->run($cmd);
    }
}
