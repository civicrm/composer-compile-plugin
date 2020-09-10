<?php
namespace Civi\CompilePlugin;

use Composer\Package\PackageInterface;

class PackageSorter
{

    /**
     * Get the list of installed packages (sorted topologically).
     *
     * @param PackageInterface[] $installedPackages
     *   All installed packages, including the root.
     * @return array
     *   List of installed packages (sorted topologically).
     *
     *   Upstream packages with no dependencies come earlier than downstream packages that require them.
     *
     *   Ex: [0 => 'very-much/upstream', 1 => 'some-what/midstream', 2 => 'hear-now/downstream']
     */
    public static function sortPackages($installedPackages)
    {
        // We do our own topological sort. It doesn't seem particularly easy
        // to ask composer for this list -- the code-paths for 'composer require'
        // and 'composer update' have to address a lot of issues (like version-selection)
        // that don't matter. We're using whatever packages are already here.

        // Given that $allPackages starts in some known order, the remaining steps
        // should be deterministic/stable over multiple iterations.
        usort($installedPackages, function ($a, $b) {
            return strnatcmp($a->getName(), $b->getName());
        });

        // Valid names for the installed packages, inclusive of aliases.
        // Array(string $packageName => bool)
        $validNames = [];
        foreach ($installedPackages as $package) {
            /** @var PackageInterface $package */
            $validNames[$package->getName()] = true;
            foreach ($package->getProvides() as $link) {
                $validNames[$link->getTarget()] = true;
            }
            foreach ($package->getReplaces() as $link) {
                $validNames[$link->getTarget()] = true;
            }
        }

        // Any unrecognized/virtualized packages (e.g. PECL) that we should ignore.
        // Array(string $package => bool)
        $ignoredNames = [];
        foreach ($installedPackages as $package) {
            /** @var PackageInterface $package */
            foreach ($package->getRequires() as $link) {
                if (!isset($validNames[$link->getTarget()])) {
                    $ignoredNames[$link->getTarget()] = true;
                }
            }
        }

        // Unsorted list of packages that need to be visited.
        // Array(string $packageName => PackageInterface $package).
        $todoPackages = [];
        foreach ($installedPackages as $package) {
            $todoPackages[$package->getName()] = $package;
        }

        // The topologically sorted packages, from least-dependent to most-dependent.
        // Array(string $packageName => PackageInterface $package)
        $sortedPackages = [];

        // A package is "ripe" when all its requirements are met.
        $isRipe = function (PackageInterface $pkg) use (&$sortedPackages, &$ignoredNames) {
            foreach ($pkg->getRequires() as $link) {
                if (!isset($sortedPackages[$link->getTarget()]) && !isset($ignoredNames[$link->getTarget()])) {
                    // printf("[%s] is not ripe due to [%s]\n", $pkg->getName(), $link->getTarget());
                    return false;
                }
            }
            // printf("[%s] is ripe\n", $pkg->getName());
            return true;
        };

        // A package is "consumed" when we move it from $todoPackages to $sortedPackages.
        $consumePackage = function (PackageInterface $pkg) use (&$sortedPackages, &$todoPackages, &$ignoredNames) {
            $sortedPackages[$pkg->getName()] = $pkg;
            unset($todoPackages[$pkg->getName()]);

            foreach ($pkg->getProvides() as $link) {
                $ignoredNames[$link->getTarget()] = true;
            }
            foreach ($pkg->getReplaces() as $link) {
                $ignoredNames[$link->getTarget()] = true;
            }
        };

        // Main loop: Progressively move ripe packages from $todoPackages to $sortedPackages.
        while (!empty($todoPackages)) {
            $ripePackages = array_filter($todoPackages, $isRipe);
            if (empty($ripePackages)) {
                throw new \RuntimeException("Error: Failed to find next installable package.");
            }
            foreach ($ripePackages as $package) {
                $consumePackage($package);
            }
        }

        return array_keys($sortedPackages);
    }
}
