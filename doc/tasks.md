# Composer Compile Plugin: Working with tasks (for library developers)

## Command-line interface

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

## Task specification

In `composer.json`, the `extra.compile` section may list multiple *tasks*. Each task may have these properties:

| Field | Type | Default | Description |
| -- | -- | -- | -- |
| `run` | `string` or `string[]` | | List of commands to execute. Ex: `@sh sed 's/no/yes/g' < pessimist.txt > optimist.txt` |
| `active` | `bool` | `true` | Whether this task should be executed |
| `title` | `string` | `my/pkg#pos` | Printable title display on the console. May be decorated with `<info>` and `<comment>` tags. |
| `watch-files` | `string[]` | `[]` | List of files or directories which are used as input to this task. |

The `run` property contains a list of steps to execute. Each step uses a prefix to indicate type. Here are some examples:

| Prefix | Description | Example |
| -- | -- | -- |
| `@sh` | Run a shell (bash) statement | `@sh sed 's/no/yes/g' < pessimist.txt > optimist.txt` |
| `@php` | Call the PHP command interpreter | `@php <...interpreter-args...>` |
| `@php-eval` | Evaluate a single line of PHP | `@php-eval echo "Hello world";` |
| `@php-method` | Call a PHP class / method | `@php-method MyClass::myMethod` |
| `@php-script` | Run a PHP script/file | `@php-script my-file.php <arguments...>` |
| `@composer` | Call a `composer` subcommand | `@composer dump-autoload` |
| `@putenv` | Set an environment variable to a static value | `@putenv VARIABLE=value` |
| `@export` | Export information about composer (such as package locations). | `@export BOOTSTRAP={{pkg:twbs/bootstrap}} BOOTSTRAP_SASS={{pkg:twbs/bootstrap-sass}}` |

NOTE: `@php-eval`,`@php-method`, and `@php-script` will automatically employ the composer autoloader. `@php` will not.

NOTE: Prior to v0.8, the `run` property did not exist - instead, there were separate fields for certain types of tasks. These fields are deprecated:

| Field | Type | Description |
| -- | -- | -- |
| `php-method` | `string` or `string[]` | PHP class+method. Multiple items may be given. Ex: `\MyModule\Compile::doCompilationStuff` |
| `shell` | `string` or `string[]` | Bash statement to execute. Multiple items may be given Ex: `cat file1.txt file2.txt > file3.txt` |

NOTE: It is valid define new/unrecognized/bespoke fields. To avoid unintended conflicts in the future, bespoke fields should use a prefix.

NOTE: Tasks are ordered based on these guidelines:

* Dependencies run first.
* Tasks run in the order listed.

## Examples

Let's consider a few examples. We'll start with a *simple* task (i.e. with the fewest moving parts), and then build up to more *robust* tasks (i.e. more useful/maintainable in more projects).

### Example: Shell-based task

Suppose you publish a library (package), `foo/bar`, which includes a handful of JS files and CSS files. You want to ensure that
an aggregated file is available. This example would produce two aggregate files, `all.js` and `all.css`.

```json
{
  "name": "foo/bar",
  "require": {
    "civicrm/composer-compile-plugin": "~0.14"
  },
  "extra": {
    "compile": [
      {"run": "@sh cd js; cat one.js two.js three.js > all.js"},
      {"run": "@sh cd css; cat one.css two.css three.css > all.css"}
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

### Example: Compile SCSS via custom PHP script

For the next example, we declare a PHP-based task to compile some SCSS.

```json
{
  "name": "foo/bar",
  "require": {
    "civicrm/composer-compile-plugin": "~0.14",
    "scssphp/scssphp": "1.2.0"
  },
  "extra": {
    "compile": [{"run": "@php-script scripts/compile-scss.php"}]
  }
}
```

The file `scripts/compile-scss.php` does the actual work:

```php
<?php
Civi\CompilePlugin\Util\Script::assertTask();

$scssCompiler = new \ScssPhp\ScssPhp\Compiler();
$scss = 'div { .foo { hyphens: auto; } }';
$css = $scssCompiler->compile($scss);
file_put_contents("build.css", $css);
```

> TIP: If you're publishing a library, it may be hard to guarantee that the script-file remains private/sequestered when deployed by
> downstream projects.  The call to `Civi\CompilePlugin\Util\Script::assertTask()` ensures that the script only runs as intended.

### Example: Compile SCSS via custom PHP method

This is very similar to the previous example, but (instead of *script file*) we use a PHP class/method.
It's slightly more verbose, but it's also easier to unit-test and re-use the method.

```json
{
  "name": "foo/bar",
  "require": {
    "civicrm/composer-compile-plugin": "~0.14",
    "scssphp/scssphp": "1.2.0"
  },
  "autoload": {"psr-4": {"ScssExample\\": "src"}},
  "extra": {
    "compile": [{"run": "@php-method \\ScssExample\\ScssExample::make"}]
  }
}
```

The method goes in `src/ScssExample.php`:

```php
<?php
namespace ScssExample;
class ScssExample
{
  public static function make(array $task)
  {
    $scssCompiler = new \ScssPhp\ScssPhp\Compiler();
    $scss = 'div { .foo { hyphens: auto; } }';
    $css = $scssCompiler->compile($scss);
    file_put_contents("build.css", $css);
  }
}
```

### Example: Compile SCSS via reusable PHP method

It would be silly to write a similar PHP script or method for *every* SCSS file. Instead, we might prefer a reusable method.

For example, the sister package [CiviCRM Composer Compile Library](https://github.com/civicrm/composer-compile-lib) provides a handful of reusable methods, including `\CCL\Tasks::scss`. It can be used like so:

```json
{
 "require": {
    "civicrm/composer-compile-lib": "~1.0"
  },
  "extra": {
    "compile": [
      {
        "run": "@php-method \\CCL\\Tasks::scss",
        "scss-files": {"dist/output.css": "my-scss-dir/input.scss" },
        "scss-imports": ["my-scss-dir"],
        "watch-files": ["my-scss-dir"]
      }
    ]
  }
}
```

The upshot -- this method bakes-in several common requirements -- e.g. it compiles SCSS=>CSS and *also* runs php-autoprefixer and *also* generates a minified file.

The convenience comes with a cost -- less control. Maybe you disagree with its opinions, or maybe you need another step, or maybe it has a bug. Fortunately, it's open-source. So you can make your own `scss()` method by copying or wrapping-around it, or you can send patches upstream.

### Example: Include Files

If the metadata about the compilation tasks looks a bit long, then you may use an include file.

```json
{
  "name": "foo/bar",
  "require": {
    "civicrm/composer-compile-plugin": "~0.14"
  },
  "extra": {
    "compile-includes": ["module-a/.composer-compile.json", "module-b/.composer-compile.json"]
  }
}
```

Then, in each file, you may define a `compile` directive like before, e.g.:

```json
{
  "compile": [
    {"run": "@sh cat js/{one,two,three}.js > all.js"}
  ]
}
```

Note: The command will run in the same folder as the JSON file.
