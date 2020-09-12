# Composer Compile Plugin: Working with tasks (for library developers)

## Command Line Interface

If you are specifically doing development for the compiled assets, then these subcommands may be helpful:

```bash
## Run compilation tasks
composer compile [-v[v]] [--dry-run] [--all] [filterExpr...]

## Watch key files and automatically re-run compilation tasks
composer compile:watch [-v[v]] [--dry-run] [--interval=ms]

## List the compilation tasks
composer compile:list [-v[v]] [--json]
```

For further details, see the built-in `--help` screen.

## Example: Shell-based task

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

## Example: PHP-based task

For the next example, we declare a PHP-based task to compile some SCSS.

```json
{
  "name": "foo/bar",
  "require": {
    "civicrm/composer-compile-plugin": "@dev",
    "scssphp/scssphp": "1.2.0",
    "padaliyajay/php-autoprefixer": "~1.2"
  },
  "autoload": {"psr-4": {"ScssExample\\": "src"}},
  "extra": {
    "compile": [{"php-method": "\\ScssExample\\ScssExample::make"}]
  }
}
```

The method goes in `src/ScssExample.php`:

```php
namespace ScssExample;
class ScssExample
{
  public static function make(array $task)
  {
    $scssCompiler = new \ScssPhp\ScssPhp\Compiler();
    $scss = 'div { .foo { hyphens: auto; } }';
    $css = $scssCompiler->compile($scss);
    $autoprefixer = new \Padaliyajay\PHPAutoprefixer\Autoprefixer($css);
    file_put_contents("build.css", $autoprefixer->compile());
  }
}
```

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
