<?php
namespace Civi\CompilePlugin;


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
    public function load() {
        $this->tasks = [];
        $this->packageWeights = array_flip(PackageSorter::sortPackages(array_merge(
          $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages(),
          [$this->composer->getPackage()]
        )));

        $this->loadPackage($this->composer->getPackage(), realpath('.'));
        // I'm not a huge fan of using 'realpath()' here, but other tasks (using `getInstallPath()`)
        // are effectively using `realpath()`, so we should be consistent.

        $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();
        foreach ($localRepo->getCanonicalPackages() as $package) {
            $this->loadPackage($package, $this->composer->getInstallationManager()->getInstallPath($package));
        }

        return $this;
    }

    /**
     * @param \Composer\Package\PackageInterface $package
     * @param string $installPath
     *   The package's location on disk.
     */
    protected function loadPackage(PackageInterface $package, $installPath) {
        // Typically, a package folder has its own copy of composer.json. We prefer to read
        // from that file in case one is drafting or applying patches.
        // Tangentially, this means it would be invalid for another composer plugin to try
        // to inject data here at runtime. If that's needed, add an event for hooking in here.
        $extra = NULL;
        if ($extra === NULL && file_exists("$installPath/composer.json")) {
            $json = json_decode(file_get_contents("$installPath/composer.json"), 1);
            $extra = $json['extra'] ?: null;
        }
        if ($extra === NULL) {
            $extra = $package->getExtra();
        }
        $taskSpecs = $extra['compile'] ?? [];

        $naturalWeight = 1;
        $tasks = [];
        foreach ($taskSpecs as $taskSpec) {
            $task = new Task();
            $task->definition = $taskSpec;
            $task->packageName = $package->getName();
            $task->pwd = $installPath;
            $task->packageWeight = $this->packageWeights[$package->getName()];
            $task->naturalWeight = $naturalWeight++;
            foreach (['title', 'command', 'weight', 'timeout', 'active'] as $field) {
                if (isset($taskSpec[$field])) {
                    $task->{$field} = $taskSpec[$field];
                }
            }
            // TODO watch
            $task->validateRequiredFields()->resolveDefaults();
            $tasks[] = $task;
        }

        $this->tasks = array_merge($this->tasks, $tasks);
    }

    /**
     * @return Task[]
     */
    public function getAll() {
        return $this->tasks;
    }

}