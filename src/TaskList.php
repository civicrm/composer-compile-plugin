<?php
namespace Civi\CompilePlugin;


use Composer\Composer;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Pool;
use Composer\Package\PackageInterface;
use Composer\Repository\PlatformRepository;
use Composer\Semver\Constraint\Constraint;

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

    public function load(Composer $composer) {
        $this->tasks = [];
        $this->packageWeights = $this->pickWeights(array_merge(
          $composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages(),
          [$composer->getPackage()]
        ));

        $this->loadPackage($composer, $composer->getPackage(), realpath('.'));
        // I'm not a huge fan of using 'realpath()' here, but other tasks (using `getInstallPath()`)
        // are effectively using `realpath()`, so we should be consistent.

        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        foreach ($localRepo->getCanonicalPackages() as $package) {
            $this->loadPackage($composer, $package, $composer->getInstallationManager()->getInstallPath($package));
        }
    }

    /**
     * Get the list of installed packages (sorted topologically).
     *
     * @param PackageInterface[] $allPackages
     *   All installed packages, including the root.
     * @return array
     *   List of installed packages (sorted topologically).
     *
     *   Upstream packages with no dependencies come earlier than downstream packages that require them.
     *
     *   Ex: ['very-much/upstream' => 0, 'some-what/midstream' => 1, 'hear-now/downstream' => 2]
     */
    protected function pickWeights($allPackages) {
        // Given that $allPackages starts in some known order, the remaining steps
        // should be deterministic/stable over multiple iterations.
        usort($allPackages, function($a, $b) {
            return strnatcmp($a->getName(), $b->getName());
        });

        // Valid names for the installed packages, inclusive of aliases.
        // Array(string $packageName => string $samePackageName)
        $validPackages = [];
        foreach ($allPackages as $package) {
            /** @var PackageInterface $package */
            $validPackages[$package->getName()] = $package->getName();
            foreach ($package->getProvides() as $link) {
                $validPackages[$link->getTarget()] = $link->getTarget();
            }
            foreach ($package->getReplaces() as $link) {
                $validPackages[$link->getTarget()] = $link->getTarget();
            }
        }

        // Any unrecognized/virtualized packages (e.g. PECL) that we should ignore.
        // Array(string $package => string $samePackageName)
        $ignoredPackages = [];
        foreach ($allPackages as $package) {
            /** @var PackageInterface $package */
            foreach ($package->getRequires() as $link) {
                if (!isset($validPackages[$link->getTarget()])) {
                    $ignoredPackages[$link->getTarget()] = $link->getTarget();
                }
            }
        }

        // Unsorted list of packages that need to be visited.
        // Array(string $packageName => PackageInterface $package).
        $todoPackages = [];
        foreach ($allPackages as $package) {
            $todoPackages[$package->getName()] = $package;
        }

        // The topologically sorted packages, from least-dependent to most-dependent.
        // Array(string $packageName => string $samePackageName)
        $sortedPackages = [];

        // A package is "ripe" when all its requirements are met.
        $isRipe = function(PackageInterface $pkg) use (&$sortedPackages, &$ignoredPackages) {
            foreach ($pkg->getRequires() as $link) {
                if (!isset($sortedPackages[$link->getTarget()]) && !isset($ignoredPackages[$link->getTarget()])) {
                    // printf("[%s] is not ripe due to [%s]\n", $pkg->getName(), $link->getTarget());
                    return FALSE;
                }
            }
            // printf("[%s] is ripe\n", $pkg->getName());
            return TRUE;
        };

        // A package is "consumed" when we move it from $todoPackages to $sortedPackages.
        $consumePackage = function(PackageInterface $pkg) use (&$sortedPackages, &$todoPackages, &$ignoredPackages) {
            $sortedPackages[$pkg->getName()] = $pkg->getName();
            unset($todoPackages[$pkg->getName()]);

            foreach ($pkg->getProvides() as $link) {
                $ignoredPackages[$link->getTarget()] = $link->getTarget();
            }
            foreach ($pkg->getReplaces() as $link) {
                $ignoredPackages[$link->getTarget()] = $link->getTarget();
            }
        };

        // Main loop
        while (!empty($todoPackages)) {
            $ripePackages = array_filter($todoPackages, $isRipe);
            if (empty($ripePackages)) {
                throw new \RuntimeException("Error: Failed to find next installable package.");
            }
            foreach ($ripePackages as $package) {
                $consumePackage($package);
            }
        }

        $result = array_flip(array_values($sortedPackages));
        // printf("sorted: %s\n", implode(' ', $sortedPackages));
        return $result;
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
            $task->packageWeight = $this->packageWeights[$package->getName()];
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