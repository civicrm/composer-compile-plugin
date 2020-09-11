# Composer Compile Plugin: Task Reference

## Task Specification

The `extra.compile` section may list multiple *tasks*. Each task must define one of the following primary elements:

| Field | Type | Description |
| -- | -- | -- |
| `callback` | `string` | PHP class and method. Ex: `\MyModule\Compile::doCompilationStuff` |
| `command` | `string` | Bash statement to execute. Ex: `cat file1.txt file2.txt > file3.txt` |

Additionally, there are several optional fields which may modify how the task operates:

| Field | Type | Default | Description |
| -- | -- | -- | -- |
| `active` | `bool` | `true` | Whether this task should be executed |
| `title` | `string` | `my/pkg#pos` | Printable title display on the console. May be decorated with `<info>` and `<comment>` tags. |
| `passthru` | `string` | `error` | Should the console output be displayed? Values may be `always`, `never`, and `error`. |
| `watches` | `string[]` | `[]` | List of files or directories which are used as input to this task. |

## Task Ordering

The ordering of tasks aims to meet these intuitions:

* Dependencies run first.
* Tasks run in the order listed.
