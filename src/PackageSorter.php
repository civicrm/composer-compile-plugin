<?php
namespace Civi\CompilePlugin;

use Composer\Package\PackageInterface;
use Composer\Util\PackageSorter as ComposerPackageSorter;

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
     *   Ex: [0 => 'very-much/upstream', 1 => 'some-what/midstream', 2 => 'here-now/downstream']
     */
    public static function sortPackages($installedPackages)
    {
        // We only want the order and the package names.
        return array_map(function ($package ) {
            return $package->getName();
        },
            ComposerPackageSorter::sortPackages($installedPackages)
        );
    }
}
