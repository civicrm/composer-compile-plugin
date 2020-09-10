<?php
namespace Civi\CompilePlugin;

use Civi\CompilePlugin\Event\CompileEvents;
use Civi\CompilePlugin\Event\CompileListEvent;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;

class TaskList
{
    /**
     * @var Task[]
     */
    protected $tasks;

    /**
     * @var array
     *   Ex: ['foo/upstream' => 1, 'foo/downstream' => 2]
     */
    protected $packageWeights;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * TaskList constructor.
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
     * Scan the composer data and build a list of compilation tasks.
     *
     * @return static
     */
    public function load()
    {
        $this->tasks = [];
        $this->packageWeights = array_flip(PackageSorter::sortPackages(array_merge(
            $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages(),
            [$this->composer->getPackage()]
        )));

        $rootPackage = $this->composer->getPackage();
        $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();
        foreach ($this->packageWeights as $packageName => $packageWeight) {
            if ($packageName === $rootPackage->getName()) {
                $this->loadPackage($rootPackage, realpath('.'));
                // I'm not a huge fan of using 'realpath()' here, but other tasks (using `getInstallPath()`)
                // are effectively using `realpath()`, so we should be consistent.
            } else {
                $package = $localRepo->findPackage($packageName, '*');
                $this->loadPackage($package, $this->composer->getInstallationManager()->getInstallPath($package));
            }
        }

        return $this;
    }

    /**
     * @param \Composer\Package\PackageInterface $package
     * @param string $installPath
     *   The package's location on disk.
     */
    protected function loadPackage(PackageInterface $package, $installPath)
    {
        // Typically, a package folder has its own copy of composer.json. We prefer to read
        // from that file in case one is drafting or applying patches.
        // Tangentially, this means it would be invalid for another composer plugin to try
        // to inject data here at runtime. If that's needed, add an event for hooking in here.
        $extra = null;
        $sourceFile = NULL;
        if ($extra === null && file_exists("$installPath/composer.json")) {
            $json = json_decode(file_get_contents("$installPath/composer.json"), 1);
            $extra = $json['extra'] ?: null;
            $sourceFile = "$installPath/composer.json";
        }
        if ($extra === null) {
            $extra = $package->getExtra();
        }
        $taskDefinitions = $extra['compile'] ?? [];

        $event = new CompileListEvent(CompileEvents::PRE_COMPILE_LIST, $this->composer, $this->io, $package, $taskDefinitions);
        $this->composer->getEventDispatcher()->dispatch(CompileEvents::PRE_COMPILE_LIST, $event);
        $taskDefinitions = $event->getTasksSpecs();

        $naturalWeight = 1;
        $tasks = [];
        foreach ($taskDefinitions as $taskDefinition) {
            $defaults = [
                'active' => true,
                'callback' => null,
                'title' => sprintf(
                    'Task <comment>%s</comment>#<comment>%s</comment>',
                    $package->getName(),
                    $naturalWeight
                ),
                'passthru' => 'error',
                'watches' => NULL,
            ];

            $taskDefinition = array_merge($defaults, $taskDefinition);
            $task = new Task();
            $task->sourceFile = $sourceFile;
            $task->definition = $taskDefinition;
            $task->packageName = $package->getName();
            $task->pwd = $installPath;
            $task->weight = 0;
            $task->packageWeight = $this->packageWeights[$package->getName()];
            $task->naturalWeight = $naturalWeight;
            foreach (['title', 'callback', 'passthru', 'active', 'watches'] as $field) {
                $task->{$field} = $taskDefinition[$field];
            }
            $tasks[] = $task;
            $naturalWeight++;
        }

        $event = new CompileListEvent(CompileEvents::POST_COMPILE_LIST, $this->composer, $this->io, $package, $taskDefinitions, $tasks);
        $this->composer->getEventDispatcher()->dispatch(CompileEvents::POST_COMPILE_LIST, $event);

        $this->tasks = array_merge($this->tasks, $event->getTasks());
    }

    /**
     * @return Task[]
     */
    public function getAll()
    {
        return $this->tasks;
    }

    /**
     * @return static
     */
    public function validateAll()
    {
        foreach ($this->tasks as $task) {
            $task->validate();
        }
        return $this;
    }
}
