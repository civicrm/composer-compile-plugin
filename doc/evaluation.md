# Composer Compile Plugin: Evaluation

## TLDR

The compile plugin allows any `composer` package to define post-install hooks.

## Background

This plugin was created to support CiviCRM.  CiviCRM is an unusual member of the PHP/composer ecosystem.  The
application is designed to be installable on top of other PHP applications (such as Drupal, WordPress, Backdrop, and
Joomla).  The application is extensible (with 4000 reported extensions).  CiviCRM itself uses `composer`, but the
environments on which it is deployed may be quite diverse.  Some deployers (esp when working with D7/WP/BD) are not
able to use `composer` to manage packages; others (esp when working with D8/D9) do use `composer`, but their
skill-levels are quite varied, and their configurations are also quite varied (due to contrib-templates,
custom-development, and/or version-changes).

In this context, the well-known, pre-existing solutions are challenging to adopt.

## Pre-existing Approaches

The `composer` ecosystem has several precedents for compiling materials.  In our review, most techniques fall into one
of these buckets:

* __Committed Build Artifacts__: During development, prepare build-artifacts locally and commit them to the VCS
  for the package.
* __Composer Scripts/Hooks__: Hook into the package-installation process and perform compilation while running
  `composer install` (etc). Notably, scripts *must* be defined in the root-package.
* __External Release Pipelines__: Use some external system to execute compilation steps and then publish final build
  artifacts.  This could be a release script (bash, gulp, Robo, etc) that the publisher runs manually, or it could be
  built into a continuous-integration system (Jenkins, Gitlab, Github Actions, etc).

Most techniques have some strengths, e.g.

* For many developers, it's very easy to get started with committed build artifacts - they can build on their
  existing local system without any extra specifications, dependencies, or study.
* The composer hooks run during the deployment process, so they can output materials that are tuned to
  the deployment.
* The external release pipelines may incorporate many kinds of tooling (e.g. PHP- and NodeJS- and Ruby-
  and Python-based tools).

But they also have weaknesses - which are palpable in Civi's context:

* Because we have contributors and advanced consumers at different organizations who work with forks and patches:
    * The external release pipeline would be a significant impediment - requiring additional skills, dependencies, workflow-steps, etc.
    * The committed artifacts would make the VCS noisy and conflict-prone.
* Because we have consumers at different organizations with diverse or evolving root-projects:
    * The scripts/hooks would require additional skills and on-going attention to the configuration.
    * The external release pipeline and the committed artifacts would preclude intentional variations (e.g. compiling
      SCSS=>CSS with different color schemes, depending on the deployment).

## Compile Plugin Approach

As the author of a library, one may define compilation tasks in `composer.json`.  You can expect these to run
automatically.

When a site-builder installs the package via `composer`, the plugin will run the tasks.  By default, the plugin takes a
security precaution and prompts the site-builder for permission before any tasks are executed.  Decisions may be stored
to avoid repetitive prompts.

This is a semi-automated mode which balances a debate over security and usability:

* Some `composer` consumers are uncomfortable with directly editing codes in `composer.json`, and they implicitly trust
  any packages they install.  Manual configuration is a problem for them.
* Some security-conscious `composer` consumers wish to download code -- and then review the code *before* anything
  executes.  A fully automated mode would be a problem for these users.

Of course, if you have a strong preference or specialized workflow, then this behavior can be tuned -- either
by setting an environment variable (`COMPOSER_COMPILE`) or adding options to the top-level `composer.json`.

## Comparison

| __Criterion__ | __Compile Plugin__ | __Composer Post-Install Scripts__ | __CI Release Pipeline__ |
| -- | -- | -- | -- |
| _How do you declare a new compilation task?_                   | Add once to upstream project | Add to upstream project *and every downstream project* | Add once to upstream project |
| _Can you run the pipeline locally?_                            | Yes | Yes | No |
| _Can you run the pipeline with forks or patches?_              | Yes | Yes | Requires reproducing CI server |
| _Can you use PHP tooling (eg `scssphp`) in the pipeline?_      | Yes | Yes | Yes |
| _Can you use non-PHP tooling (eg `gulp`, `docker`) in the pipeline?_ | Requires docs/installation | Requires docs/installation | Yes |
