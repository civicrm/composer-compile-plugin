<?php
namespace Civi\CompilePlugin;

use Civi\CompilePlugin\Event\CompileEvents;
use Civi\CompilePlugin\Event\CompileTaskEvent;
use Civi\CompilePlugin\Exception\TaskFailedException;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;

class TaskRunner
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
     * TaskRunner constructor.
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
     * Execute a list of compilation tasks.
     *
     * @param Task[] $tasks
     */
    public function run(array $tasks)
    {
        /** @var IOInterface $io */
        $io = $this->io;

        usort($tasks, function($a, $b){
            $fields = ['weight', 'packageWeight', 'naturalWeight'];
            foreach ($fields as $field) {
                if ($a->{$field} > $b->{$field}) {
                    return 1;
                }
                elseif ($a->{$field} < $b->{$field}) {
                    return -1;
                }
            }
            return 0;
        });

        foreach ($tasks as $task) {
            /** @var \Civi\CompilePlugin\Task $task */

            $package = ($this->composer->getPackage()->getName() === $task->packageName)
              ? $this->composer->getPackage()
              : $this->composer->getRepositoryManager()->getLocalRepository()->findPackage($task->packageName, '*');

            $event = new CompileTaskEvent(CompileEvents::PRE_COMPILE_TASK, $this->composer, $this->io, $package, $task);
            $dispatcher = $this->composer->getEventDispatcher();
            $dispatcher->dispatch(CompileEvents::PRE_COMPILE_TASK, $event);

            if (!$task->active) {
                $io->write('<error>Skip</error>: ' . ($task->title),
                  true, IOInterface::VERBOSE);
                continue;
            }

            $io->write('<info>Compile</info>: ' . ($task->title));

            $this->runTask($task, $package);

            $event = new CompileTaskEvent(CompileEvents::POST_COMPILE_TASK, $this->composer, $this->io, $package, $task);
            $this->composer->getEventDispatcher()->dispatch(CompileEvents::POST_COMPILE_TASK, $event);
        }
    }

    protected function runTask(Task $task, PackageInterface $package) {
        $orig = [
          'pwd' => getcwd(),
        ];

        try {
            chdir($task->pwd);
            $e = new CompileTaskEvent(NULL, $this->composer, $this->io, $package, $task);
            call_user_func($task->callback, $e);
        }
        finally {
            chdir($orig['pwd']);
        }
    }

}
