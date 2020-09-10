<?php

namespace Civi\CompilePlugin\Util;

use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Composer;

class ShellRunner
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * ShellRunner constructor.
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(
        \Composer\Composer $composer,
        \Composer\IO\IOInterface $io
    ) {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Run a shell command in the same style as Composer's EventDispatcher.
     *
     * @param string $cmd
     *   Ex: '@php -r "echo 123;"'
     *   Ex: '@composer require foo/bar'
     */
    public function run($cmd)
    {
        $d = new EventDispatcher($this->composer, $this->io);
        $d->addListener('shell-runner', $cmd);
        $d->dispatch('shell-runner', new Event('shell-runner'));
    }
}
