<?php
namespace Civi\CompilePlugin\Util;

use Symfony\Component\Console\Output\OutputInterface;

class TableHelper
{

    /**
     * @param OutputInterface $output
     * @param array $header
     *   Ex: ['Column A', 'Column B']
     * @param array $rows
     *   Ex: [[10,20,30], [11,21,31]]
     */
    public static function showTable(OutputInterface $output, $header, $rows)
    {
        // Hard dice: we don't know what version of Symfony Console is around.

        $stripMeta = function ($s) {
            return preg_replace(';</?(info|comment|error)>;', '', $s);
        };

        $colCount = count($header);
        $colWidths = [];
        for ($col = 0; $col < $colCount; $col++) {
            $colWidths[$col] = strlen($stripMeta($header[$col]));
            foreach ($rows as $row) {
                $colWidths[$col] = max($colWidths[$col], strlen($stripMeta($row[$col])));
            }
        }

        $mkRow = function ($row) use ($colWidths, $stripMeta) {
            $buf = '';
            foreach ($row as $col => $cell) {
                $buf .= '| ';
                $buf .= $cell;
                $buf .= str_repeat(' ', $colWidths[$col] - strlen($stripMeta($cell)));
                $buf .= ' ';
            }
            $buf .= '|';
            return $buf;
        };

        $bold = function ($c) {
            return "<info>$c</info>";
        };

        $hrPattern = '+';
        for ($col = 0; $col < $colCount; $col++) {
            $hrPattern .= str_repeat('-', 2 + $colWidths[$col]) . '+';
        }

        $output->writeln($hrPattern);
        $output->writeln($mkRow(array_map($bold, $header)));
        $output->writeln($hrPattern);
        foreach ($rows as $row) {
            $output->writeln($mkRow($row));
        }
        $output->writeln($hrPattern);
    }
}
