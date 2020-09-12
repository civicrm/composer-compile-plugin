# Composer Compile Plugin: Library Configuration

Suppose you publish a library (package), `foo/bar`, which includes a handful of JS files and CSS files. You want to ensure that
an aggregated file is available. This example would produce two aggregate files, `all.js` and `all.css`.

```json
{
  "name": "foo/bar",
  "require": {
    "civicrm/composer-compile-plugin": "~1.0"
  },
  "extra": {
    "compile": [
      {"shell": "cat js/{one,two,three}.js > all.js"},
      {"shell": "cat css/{one,two,three}.css > all.css"}
    ]
  }
}
```

Observe that:

* There are two compilation tasks.
* Both of them are based on shell commands.
* The files `all.js` and `all.css` are auto-generated.
* It does not matter if `foo/bar` is a root-project.
* Compiled files should not be committed to the origin/git project.

<!--
For the next example, we seek to build a custom variant of Bootstrap.

```json
{
  "name": "foo/bar",
  "require": {
    "civicrm/composer-compile-plugin": "~1.0",
    "scssphp/scssphp": "~1.2",
    "twbs/bootstrap": "~4.5.2"
  },
  "autoload": {
    "psr-4": {
      "MyTheme\\": "src/"
    }
  },
  "extra": {
    "compile": [
      {
        "title": "Compile <comment>*.css</comment => <comment>*.scss</comment>"
        "callback": "\MyTheme\Compile::compileCss",
        "watch": ["scss/*"]
      }
    ]
  }
}
```
-->


## Task Specification

The `extra.compile` section may list multiple *tasks*. Each task must define one of the following primary elements:

| Field | Type | Description |
| -- | -- | -- |
| `php-method` | `string` | PHP class and method. Ex: `\MyModule\Compile::doCompilationStuff` |
| `shell` | `string` | Bash statement to execute. Ex: `cat file1.txt file2.txt > file3.txt` |

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
