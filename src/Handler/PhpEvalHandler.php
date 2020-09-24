<?php

namespace Civi\CompilePlugin\Handler;

use Civi\CompilePlugin\Event\CompileTaskEvent;
use Civi\CompilePlugin\Util\ShellRunner;

class PhpEvalHandler
{
    public function runTask(CompileTaskEvent $event, $runType, $phpEval)
    {
        // Surely there's a smarter way to get this?
        $vendorPath = $event->getComposer()->getConfig()->get('vendor-dir');
        $autoload =  $vendorPath . '/autoload.php';
        if (!file_exists($autoload)) {
            throw new \RuntimeException("CompilePlugin: Failed to locate autoload.php");
        }

        if (strpos($phpEval, "\n") !== false) {
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

    /**
     * @param string $phpMethod
     * @return bool
     */
    public static function isWellFormedMethod($phpMethod)
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
