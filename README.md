# Composer Compile Plugin

[![Build Status](https://travis-ci.com/civicrm/composer-compile-plugin.svg?branch=master)](https://travis-ci.com/civicrm/composer-compile-plugin)

The "Compile" plugin allows any package to define free-form "compilation" tasks, such as:

* Converting SCSS to CSS
* Generating PHP wrappers based on an XML schema

This README focuses on how to use the plugin.  For deeper discussion about design and implementation of the plugin, see
[doc/evaluation.md](doc/evaluation.md) and [doc/develop.md](doc/develop.md).

## Command Line Interface

The "Compile" plugin integrates into common `composer` lifecycle actions (`composer install`, `composer require`,
`composer update`).  When you install a package which needs some compilation, it will prompt for permission before
executing.  A typical console user does not need to work with any other CLI commands.

If you are specifically doing development on the compiled assets, then these subcommands may be helpful:

```bash
## Run compilation tasks
composer compile [-v[v]] [--dry-run] [filterExpr...]

## Watch key files and automatically re-run compilation tasks
composer compile:watch [-v[v]] [--dry-run] [--interval=ms]

## List the compilation tasks
composer compile:list [-v[v]]
```

For further details, see the built-in `--help` screen.

## Configuration

Suppose you publish a package, `foo/bar`, which includes a handful of JS files and CSS files. You want to ensure that
an aggregated file is available. This example would produce two aggregate files, `all.js` and `all.css`.

```json
{
  "name": "foo/bar",
  "require": {
    "civicrm/composer-compile-plugin": "~1.0"
  },
  "extra": {
    "compile": [
      {"command": "cat js/{one,two,three}.js > all.js"},
      {"command": "cat css/{one,two,three}.css > all.css"}
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

There are several more options for defining compilation tasks. See [doc/tasks.md](doc/tasks.md).
