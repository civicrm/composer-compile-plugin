<?php

namespace Civi\CompilePlugin;

class Task
{
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
     * NOTE: This option was added in early drafts as a pressure-relief valve
     * in case some control was needed over ordering. It's now hidden, though,
     * because I think it's better to wait for some feedback re:use-cases before
     * committing to this model.
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
     * (Required) Callback.
     *
     * Note: Event listeners may examine the task definition and fill-in
     * the callback.
     *
     * @var callable
     */
    public $callback;

    /**
     * (Optional) Whether to display output on the console
     *
     * Options:
     * - 'always': Display output in real time
     * - 'error': Buffer output. If an error arises, then display output
     * - 'never': Do not show output
     *
     * @var string
     */
    public $passthru;

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
    public function validate()
    {
        $missing = [];
        foreach (['naturalWeight', 'packageWeight', 'packageName', 'pwd', 'definition', 'callback'] as $requiredField) {
            if ($this->{$requiredField} === null || $this->{$requiredField} === '') {
                $missing[] = $requiredField;
            }
        }
        if ($missing) {
            throw new \RuntimeException("Compilation task is missing field(s): " . implode(",", $missing));
        }
        if (!is_callable($this->callback)) {
            throw new \RuntimeException("Compilation task has invalid callback: {$this->callback}");
        }
        return $this;
    }
}
