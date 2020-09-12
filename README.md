# Composer Compile Plugin

[![Build Status](https://travis-ci.com/civicrm/composer-compile-plugin.svg?branch=master)](https://travis-ci.com/civicrm/composer-compile-plugin)

The "Compile" plugin enables developers of PHP libraries to define free-form "compilation" tasks, such as:

* Converting SCSS to CSS
* Generating PHP wrappers based on an XML schema

For PHP site-builders who use these libraries, the compilation process is a seamless part of the regular download (`composer install`).

This model is framework-agnostic and portable to many different environments.  It includes a permission mechanism to
address historical concerns about `composer` hooks and untrusted libraries.  It allows libraries to be managed in a
"clean" fashion, amenable to patching/forking and without comitting build-artifacts.

## Command Line Interface

The "Compile" plugin integrates into common `composer` lifecycle actions (`composer install`, `composer require`,
`composer update`).  When you install a package which needs some compilation, it will prompt for permission before
executing.  A typical console user does not need to work with any other CLI commands.

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

## More information

* [doc/evaluation.md](doc/evaluation.md): Evaluate and compare against similar options
* [doc/library.md](doc/library.md): Configuration options for upstream developers (library packages)
* [doc/root.md](doc/root.md): Configuration options for site-builders (root packages)
* [doc/develop.md](doc/develop.md): How to work with `composer-compile-plugin.git`
