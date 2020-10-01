<?php
namespace Civi\CompilePlugin\Util;

class TsHelper
{

    const SKIP = ';^(\.|\.\.|\.git|\.svn)$;';

    use ComposerIoTrait;

    /**
     * Determine if $inputs are newer or older than $outputs.
     *
     * @param string|string[] $outputs
     *   List of files or folders.
     * @param string|string[] $inputs
     *   List of files or folders.
     * @return bool
     *   TRUE if all mtime($outputs) are >= than all mtime($inputs).
     *   FALSE if any mtime($outputs) are < than any mtime($inputs).
     */
    public function isFresh($outputs, $inputs)
    {
        // Enqueue a list of files from a subdir.
        $addFiles = function (&$list, $dir) {
            if ($dh = opendir($dir)) {
                while (true) {
                    $file = readdir($dh);
                    if ($file === false) {
                        break;
                    } elseif (preg_match(self::SKIP, $file)) {
                        // skip
                    } else {
                        $list[] = "$dir/$file";
                    }
                }
                closedir($dh);
            }
        };

        // We generally expect there to be more inputs than outputs.
        // Therefore, we do the full-scan on outputs; the scan on inputs
        // can short-circuit.

        $inputs = (array)$inputs;
        $outputs = (array)$outputs;
        $maxOutputTs = null;
        while (count($outputs) > 0) {
            $output = array_shift($outputs);
            if (!file_exists($output)) {
                // Missing output, not fresh...
                return false;
            }
            $outputTs = filemtime($output);
            if ($maxOutputTs === null || $outputTs > $maxOutputTs) {
                $maxOutputTs = $outputTs;
            }
            if (is_dir($output)) {
                $addFiles($outputs, $output);
            }
        }

        while (count($inputs) > 0) {
            $input = array_shift($inputs);
            if (file_exists($input)) {
                $inputTs = filemtime($input);
            } else {
                if ($this->io) {
                    $this->io->write("<warning>Task depends on non-existent input</warning>: " . $input);
                }
                $inputTs = time();
            }

            if ($maxOutputTs < $inputTs) {
                return false;
            }
            if (is_dir($input)) {
                $addFiles($inputs, $input);
            }
        }

        return true;
    }


    public function clear()
    {
        clearstatcache();
    }
}
