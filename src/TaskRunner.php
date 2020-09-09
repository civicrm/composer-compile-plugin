<?php
namespace Civi\CompilePlugin;

use Civi\CompilePlugin\Event\CompileEvents;
use Civi\CompilePlugin\Event\CompileTaskEvent;
use Civi\CompilePlugin\Exception\TaskFailedException;
use Composer\Composer;
use Composer\IO\IOInterface;

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
                $io->write('<error>Skip</error>: ' . ($task->title ?: $task->command),
                  true, IOInterface::VERBOSE);
                continue;
            }

            $io->write('<info>Compile</info>: ' . ($task->title ?: $task->command));
            if ($io->isVerbose()) {
                $io->write("<info>In <comment>{$task->pwd}</comment>, execute <comment>{$task->command}</comment></info>");
            }

            $this->runTask($task);

            $event = new CompileTaskEvent(CompileEvents::POST_COMPILE_TASK, $this->composer, $this->io, $package, $task);
            $this->composer->getEventDispatcher()->dispatch(CompileEvents::POST_COMPILE_TASK, $event);
        }
    }

    protected function runTask(Task $task) {
        $orig = [
          'pwd' => getcwd(),
        ];

        try {
            chdir($task->pwd);

            switch ($task->passthru) {
                case 'always':
                    passthru($task->command, $retVal);
                    if ($retVal !== 0) {
                        throw new TaskFailedException($task);
                    }
                    break;

                case 'error':
                    exec($task->command, $output, $retVal);
                    if ($retVal !== 0) {
                        if (is_callable([$this->io, 'writeErrorRaw'])) {
                            $this->io->writeErrorRaw($output);
                        }
                        else {
                            $this->io->writeError($output);
                        }
                        throw new TaskFailedException($task);
                    }
                    break;

                case 'never':
                    exec($task->command, $output, $retVal);
                    if ($retVal !== 0) {
                        throw new TaskFailedException($task);
                    }
                    break;

                default:
                    throw new \InvalidArgumentException("Invalid passthru option: \"$task->passthru\"");
            }
        }
        finally {
            chdir($orig['pwd']);
        }
    }

}
