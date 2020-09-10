<?php

namespace Civi\CompilePlugin;

use Civi\CompilePlugin\Command\CompileCommand;

class CommandProvider implements \Composer\Plugin\Capability\CommandProvider
{

    public function getCommands()
    {
        return [
          new CompileCommand(),
        ];
    }
}
