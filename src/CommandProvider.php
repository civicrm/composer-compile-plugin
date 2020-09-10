<?php

namespace Civi\CompilePlugin;

use Civi\CompilePlugin\Command\CompileCommand;
use Civi\CompilePlugin\Command\CompileListCommand;

class CommandProvider implements \Composer\Plugin\Capability\CommandProvider
{

    public function getCommands()
    {
        return [
            new CompileCommand(),
            new CompileListCommand(),
        ];
    }
}
