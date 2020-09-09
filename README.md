# Composer Compile Plugin

The "Compile" plugin allows any package to define "compilation" tasks, such as:

* Converting SCSS to CSS
* Generating PHP wrappers based on an XML schema

## Example

Here is a basic example - the package `foo/bar` specifies that 3 JS files and 3 CSS files should be combined:

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

## Events

If you are developing another Composer enhancement on top of `composer-compile-plugin`, these events may prove useful:

* `pre-compile-list`: Fires before parsing each package's task-list. This allows other plugins to inspect
  and modify the raw `taskDefinitions` (JSON data).
* `post-compile-list`: Fires after parsing each package's task-list. This allows  other plugins to inspect
  and modify the parsed `Task`.
* `pre-compile-task`: Fires before executing a specific `task`.
* `post-compile-task`: Fires after executing a specific `task`.

## Comparison

You may consider this an alternative to:

* Adding [composer post-install scripts](https://getcomposer.org/doc/articles/scripts.md) in the root project. 
* Configuring a continuous-integration system (Github/Gitlab/Jenkins/etc) to prepare compiled releases for a package.

| __Criterion__ | __Compile Plugin__ | __Post-Install Scripts__ | __CI Release Pipeline__ |
| -- | -- | -- | -- |
| _How do you declare a new compilation task?_         | Add once to upstream project | Add to upstream project *and every downstream project* | Add once to upstream project |
| _Can you run the pipeline locally?_                  | Yes | Yes | No |
| _Can you run the pipeline with forks or patches?_    | Yes | Yes | Requires reproducing CI server |
| _Can you use PHP tooling in the pipeline?_           | Yes | Yes | Yes |
| _Can you use non-PHP tooling in the pipeline?_       | Requires docs/coordination | Requires docs/coordination | Yes |
