# Composer Compile Plugin

[![Build Status](https://travis-ci.com/civicrm/composer-compile-plugin.svg?branch=master)](https://travis-ci.com/civicrm/composer-compile-plugin)

The "Compile" plugin enables developers of PHP libraries to define free-form "compilation" tasks, such as:

* Converting SCSS to CSS
* Generating PHP wrappers based on an XML schema

For PHP site-builders who use these libraries, the compilation process is a seamless part of the regular download (`composer install`).

This model is framework-agnostic and portable to many different environments.  It includes a permission mechanism to
address historical concerns about `composer` hooks and untrusted libraries.  It allows libraries to be managed in a
"clean" fashion, amenable to patching/forking and without comitting build-artifacts.

## More information

* [doc/site-build.md](doc/site-build.md): Managing the root package (for site-builders)
* [doc/tasks.md](doc/tasks.md): Working with tasks (for library developers)
* [doc/evaluation.md](doc/evaluation.md): Evaluate and compare against similar options
* [doc/develop.md](doc/develop.md): How to work with `composer-compile-plugin.git` (for plugin-development)
