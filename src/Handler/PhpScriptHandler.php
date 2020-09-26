<?php

namespace Civi\CompilePlugin\Handler;

use Civi\CompilePlugin\Event\CompileTaskEvent;
use Civi\CompilePlugin\Util\ShellRunner;

/**
 * Class PhpScriptHandler
 * @package Civi\CompilePlugin\Handler
 *
 * This implements support for run-steps based on `@php-script <filename> [<cli-args>]`.
 */
class PhpScriptHandler
{
    /**
     * @param \Civi\CompilePlugin\Event\CompileTaskEvent $event
     * @param string $runType
     * @param string $phpScript
     *   Ex: 'echo "Hello world";'
     */
    public function runTask(CompileTaskEvent $event, $runType, $phpScriptExpr)
    {
        // Surely there's a smarter way to get this?
        $vendorPath = $event->getComposer()->getConfig()->get('vendor-dir');
        $autoload =  $vendorPath . '/autoload.php';
        if (!file_exists($autoload)) {
            throw new \RuntimeException("CompilePlugin: Failed to locate autoload.php");
        }

        if (strpos($phpScriptExpr, "\n") !== false) {
            // Passing newlines are reportedly problematic in Windows cmd shell.
            throw new \RuntimeException("CompilePlugin: Multiline script call is not permitted");
        }

        if (strpos($phpScriptExpr, ' ') !== false) {
            list ($scriptFile, $scriptArgs) = explode(' ', $phpScriptExpr, 2);
        } else {
            $scriptFile = $phpScriptExpr;
            $scriptArgs = '';
        }

        if (!file_exists($scriptFile)) {
            throw new \RuntimeException(sprintf("CompilePlugin: Script %s does not exist in %s", $scriptFile, getcwd()));
        }

        $cmd = sprintf(
            '@php -dauto_prepend_file=%s %s %s',
            escapeshellarg($autoload),
            escapeshellarg($scriptFile),
            $scriptArgs
        );

        $r = new ShellRunner($event->getComposer(), $event->getIO());
        $r->run($cmd);
    }
}
