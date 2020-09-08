<?php

namespace Civi\CompilePlugin;


class Task
{
    const DEFAULT_TIMEOUT = 600;

    /**
     * @var string
     *   Ex: 'Compile <comment>foobar.txt</comment>'
     */
    public $title;

    /**
     * @var int
     */
    public $weight;

    /**
     * @var int
     */
    public $naturalWeight;

    /**
     * @var string
     */
    public $packageName;

    /**
     * @var string
     */
    public $command;

    /**
     * @var int
     */
    public $timeout;

    /**
     * @var string
     */
    public $pwd;

    /**
     * @var array
     */
    public $watches = [];

    /**
     * Ensure that any required fields are defined.
     * @return static
     */
    public function validateRequiredFields() {
        $missing = [];
        foreach (['naturalWeight', 'packageName', 'pwd'] as $requiredField) {
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