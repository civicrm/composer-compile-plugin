<?php

namespace Civi\CompilePlugin;

use Civi\CompilePlugin\Event\CompileEvents;
use Civi\CompilePlugin\Subscriber\CommandSubscriber;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class CompilePlugin implements PluginInterface, EventSubscriberInterface, Capable
{

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;

    public static function getSubscribedEvents()
    {
        return [
          ScriptEvents::PRE_INSTALL_CMD => ['validateMode', 5],
          ScriptEvents::PRE_UPDATE_CMD => ['validateMode', 5],
          ScriptEvents::POST_INSTALL_CMD => ['runTasks', 5],
          ScriptEvents::POST_UPDATE_CMD => ['runTasks', 5],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities()
    {
        return [
          'Composer\Plugin\Capability\CommandProvider' => CommandProvider::class,
        ];
    }


    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $dispatch = $composer->getEventDispatcher();
        $dispatch->addListener(CompileEvents::POST_COMPILE_LIST, [CommandSubscriber::class, 'applyDefaultCallback']);
    }

    public function deactivate(Composer $composer, IOInterface $io) {
      // NOTE: This method is only valid on composer v2.
      $dispatch = $composer->getEventDispatcher();
      $dispatch->removeListener(CompileEvents::POST_COMPILE_LIST, [CommandSubscriber::class, 'applyDefaultCallback']);
    }

    public function uninstall(Composer $composer, IOInterface $io) {
      // NOTE: This method is only valid on composer v2.
    }

    public function validateMode(Event $event)
    {
        $mode = $this->getMode();
        if ($mode === 'prompt' && !$this->io->isInteractive()) {
            $this->io->write(file_get_contents(__DIR__ . '/messages/cannot-prompt.txt'));
            throw new \Exception("Please configure COMPOSER_COMPILE or extra.compile-mode");
        }
    }

    public function runTasks(Event $event)
    {
        $taskList = new TaskList($this->composer, $this->io);
        $taskList->load()->validateAll();

        $taskRunner = new TaskRunner($this->composer, $this->io);

        if (empty($taskList->getAll())) {
            return;
        }

        $this->io->write("<info>Locating compilation tasks</info>");

        $mode = $this->getMode();
        switch ($mode) {
            case 'on':
                $taskRunner->run($taskList->getAll());
                break;

            case 'off':
                $this->io->write(sprintf("<error>ERROR</error>: Automatic compilation is disabled. These packages have compilation tasks which have not been executed:"));
                $this->io->write($this->createTaskSummary($taskList));
                $this->io->writeError(sprintf("<error>Skipped %d compilation task(s)</error>", count($taskList->getAll())));
                // FIXME follow-up steps
                break;

            case 'prompt':
                $this->io->write(sprintf("The following packages have compilation tasks:"));
                $this->io->write($this->createTaskSummary($taskList));
                $choice = $this->askRunCompile();

                $composerCfg = $this->composer->getConfig();
                if (empty($composerCfg->get('extra.compile-mode')) && $choice === 'y') {
                    $rememberPref = $this->askRememberPreference();
                    if ($rememberPref === 'y') {
                        $composerCfg->getConfigSource()->addProperty("extra.compile-mode", "on");
                    }
                }

                switch ($choice) {
                    case 'y':
                        $taskRunner->run($taskList->getAll());
                        break;

                    case 'n':
                        $this->io->writeError(sprintf("<error>Skipped %d compilation task(s)</error>", count($taskList->getAll())));
                        $this->io->writeError(sprintf("<error>You may run tasks manually with \"composer compile\"</error>", count($taskList->getAll())));
                        break;
                }
        }
    }

    /**
     * Determine whether compilation is enabled.
     *
     * @return string
     *   One of:
     *   - 'on': Automatically compile during installation
     *   - 'prompt': Ask before compiling
     *   - 'off': Do not compile automatically.
     */
    protected function getMode()
    {
        $aliases = [
            '0' => 'off',
            '1' => 'on',
        ];

        $mode = getenv('COMPOSER_COMPILE');

        if ($mode === '' || $mode === false || $mode === null) {
            $extra = $this->composer->getPackage()->getExtra();
            $mode = $extra['compile-mode'] ?? '';
        }

        if ($mode === '' || $mode === false || $mode === null) {
            $mode = 'prompt';
        }

        $mode = strtolower($mode);
        if (isset($aliases[$mode])) {
            $mode = $aliases[$mode];
        }

        $options = ['on', 'prompt', 'off'];
        if (in_array($mode, $options)) {
            return $mode;
        } else {
            throw new \InvalidArgumentException("The compilation policy (COMPOSER_COMPILE or extra.compile-mode) is invalid. Valid options are \"" . implode('", "', $options) . "\".");
        }
    }

    /**
     * @param TaskList $taskList
     * @return string
     */
    protected function createTaskSummary($taskList)
    {
        $tallies = [];
        foreach ($taskList->getAll() as $task) {
            $tallies[$task->packageName] = $tallies[$task->packageName] ?? 0;
            $tallies[$task->packageName]++;
        }
        $buf = '';
        foreach ($tallies as $package => $tally) {
            if ($tally === 1) {
                $buf .= sprintf(
                    " - <comment>%s</comment> has <comment>%d</comment> task\n",
                    $package,
                    $tally
                );
            } else {
                $buf .= sprintf(
                    " - <comment>%s</comment> has <comment>%d</comment> task(s)\n",
                    $package,
                    $tally
                );
            }
        }
        return $buf;
    }

    /**
     * @return string
     *   Returns 'y' or 'n'.
     */
    protected function askRememberPreference()
    {
        do {
            $saveChoice = $this->io->askAndValidate(
                '<info>Remember this preference for the future?</info> ([<comment>y</comment>]es, [<comment>n</comment>]o) ',
                function ($x) {
                    $x = strtolower($x);
                    return in_array($x, ['y', 'n']) ? $x : null;
                }
            );
            return $saveChoice;
        } while (!in_array($saveChoice, ['y', 'n']));
    }

    /**
     * @return string
     *   Returns 'y' or 'n'
     */
    protected function askRunCompile()
    {
        $choice = null;
        do {
            $choice = $this->io->askAndValidate(
                '<info>Run compilation tasks?</info> ([<comment>y</comment>]es, [<comment>n</comment>]o, [<comment>h</comment>]elp) ',
                function ($x) {
                    $x = strtolower($x);
                    return in_array($x, ['y', 'n', 'h']) ? $x : null;
                }
            );
            if ($choice === 'h') {
                $this->io->write("\n" . file_get_contents(__DIR__ . '/messages/prompt-help.txt'));
            }
        } while (!in_array($choice, ['y', 'n']));

        return $choice;
    }
}
