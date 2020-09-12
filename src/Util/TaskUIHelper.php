<?php
namespace Civi\CompilePlugin\Util;

use Civi\CompilePlugin\Task;

class TaskUIHelper
{

    /**
     * Make a bulleted list to summarize the tasks.
     *
     * @param Task[] $tasks
     * @return string
     */
    public static function formatTaskSummary($tasks)
    {
        $tallies = [];
        foreach ($tasks as $task) {
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
     * Make a table displaying a list of tasks.
     *
     * @param Task[] $tasks
     * @param string[] $fields
     *   List of fields/columns to display.
     *   Some mix of: 'active', 'id', 'packageName', 'title', 'action'
     * @return string
     */
    public static function formatTaskTable($tasks, $fields)
    {
        $availableHeaders = ['active' => '', 'id' => 'ID', 'packageName' => 'Package', 'title' => 'Title', 'action' => 'Action'];

        $header = [];
        foreach ($fields as $field) {
            $header[] = $availableHeaders[$field];
        }

        $rows = [];
        $descAction = function ($task) {
            if ($task->callback === [ShellSubscriber::CLASS, 'runTask']) {
                return '<info>(shell)</info> ' . $task->definition['shell'];
            } elseif (is_array($task->callback)) {
                return '<info>(php-method)</info> ' . $task->callback[0] . '::' . $task->callback[1];
            } elseif (is_string($task->callback)) {
                return '<info>(php-method)</info> ' . $task->callback;
            } else {
                return '<error>(UNRECOGNIZED)</error>';
            }
        };
        foreach ($tasks as $task) {
            /** @var Task $task */
            $row = [];
            foreach ($fields as $field) {
                switch ($field) {
                    case 'active':
                        $row[] = $task->active ? '+' : '-';
                        break;

                    case 'action':
                        $row[] = $descAction($task);
                        break;

                    default:
                        $row[] = $task->{$field};
                        break;
                }
            }
            $rows[] = $row;
        }

        return TableHelper::formatTable($header, $rows);
    }
}
