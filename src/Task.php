<?php

namespace Civi\CompilePlugin;


class Task
{
    const DEFAULT_TIMEOUT = 600;

    /**
     * (Optional) Printable title for this compilation task.
     *
     * @var string
     *   Ex: 'Compile <comment>foobar.txt</comment>'
     */
    public $title;

    /**
     * (Optional) The developer's chosen ordering key.
     *
     * This ordering takes precedence over all other orderings. For example,
     * if your ecosystem had a policy that all `XML=>PHP` compilations run
     * before all `SCSS=>CSS` compilations, then you would use different weights
     * for `XML=>PHP` (eg -5) and `SCSS=>CSS` (eg +5).
     *
     * @var int
     */
    public $weight;

    /**
     * (System-Generated) The topological order of the package which defines this
     * task.
     *
     * @var int
     */
    public $packageWeight;

    /**
     * (System-Generated) Within a given package, the written ordering (from JSON)
     * determines natural weight.
     *
     * @var int
     */
    public $naturalWeight;

    /**
     * (System-Generated) The name of the package which defined this task.
     *
     * @var string
     */
    public $packageName;

    /**
     * (Required) Bash expression to execute.
     *
     * @var string
     */
    public $command;

    /**
     * (Optional) Maximum time the task may run (seconds).
     *
     * @var int
     */
    public $timeout;

    /**
     * (Required) The folder in which to execute the task.
     *
     * @var string
     */
    public $pwd;

    /**
     * @var array
     */
    public $watches = [];

    /**
     * (Optional) Whether the task should be executed.
     *
     * @var bool
     */
    public $active;

    /**
     * (Required) The raw task definition
     *
     * @var array
     */
    public $definition;

    /**
     * Ensure that any required fields are defined.
     * @return static
     */
    public function validateRequiredFields() {
        $missing = [];
        foreach (['naturalWeight', 'packageWeight', 'packageName', 'pwd', 'definition'] as $requiredField) {
            if ($this->{$requiredField} === NULL || $this->{$requiredField} === '') {
                $missing[] = $requiredField;
            }
        }
        if ($missing) {
            throw new \RuntimeException("Compilation task is missing field(s): " . implode(",", $missing));
        }
        return $this;
    }

    /**
     * @return static
     */
    public function resolveDefaults() {
        if ($this->active === NULL) {
            $this->active = TRUE;
        }
        if ($this->weight === NULL) {
            $this->weight = 0;
        }
        if ($this->timeout === NULL) {
            $this->timeout = self::DEFAULT_TIMEOUT;
        }
        if ($this->title === NULL || $this->title === '') {
            $this->title = sprintf('Task <comment>#%d</comment> from <comment>%s</comment>',
              $this->naturalWeight, $this->packageName);
        }
        return $this;
    }

}