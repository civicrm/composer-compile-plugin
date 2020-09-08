<?php
namespace Civi\CompilePlugin;


use Composer\Composer;
use Composer\Package\PackageInterface;

class TaskList
{
    /**
     * @var Task[]
     */
    protected $tasks;

    public function load(Composer $composer) {
        $this->tasks = [];

        $this->loadPackage($composer, $composer->getPackage(), realpath('.'));
        // I'm not a huge fan of using 'realpath()' here, but other tasks (using `getInstallPath()`)
        // are effectively using `realpath()`, so we should be consistent.

        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        foreach ($localRepo->getCanonicalPackages() as $package) {
            $this->loadPackage($composer, $package, $composer->getInstallationManager()->getInstallPath($package));
        }
    }

    /**
     * @param \Composer\Composer $composer
     * @param \Composer\Package\PackageInterface $package
     * @param string $installPath
     *   The package's location on disk.
     */
    protected function loadPackage(Composer $composer, PackageInterface $package, $installPath) {
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

        $naturalWeight = 1;
        foreach ($extra['compile'] ?? [] as $taskSpec) {
            $task = new Task();
            $task->packageName = $package->getName();
            $task->pwd = $installPath;
            $task->naturalWeight = $naturalWeight++;
            foreach (['title', 'command', 'weight', 'timeout'] as $field) {
                if (isset($taskSpec[$field])) {
                    $task->{$field} = $taskSpec[$field];
                }
            }
            // TODO watch
            $task->validateRequiredFields()->resolveDefaults();
            $this->tasks[] = $task;
        }
    }

    /**
     * @return Task[]
     */
    public function getAll() {
        return $this->tasks;
    }
}