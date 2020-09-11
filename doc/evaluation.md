# Composer Compile Plugin: Evaluation

## Comparison

There are other ways to prepare compiled material for composer-based project. We can compare:

* __Compile Plugin__: Load this plugin. Add the `extra.compile` tasks in the package.
* __Post-Install Scripts__: Add an inert script to the package. Using out-of-band materials (documentation/templates),
  prompt each consumer to add it to the [composer post-install scripts](https://getcomposer.org/doc/articles/scripts.md).
* __CI Release Pipeline__: Configure a continuous-integration system (Github/Gitlab/Jenkins/etc) to prepare compiled releases for a package.
  Ensure that the package-feed provides these releases.

With the ofllowing trade-offs:

| __Criterion__ | __Compile Plugin__ | __Post-Install Scripts__ | __CI Release Pipeline__ |
| -- | -- | -- | -- |
| _How do you declare a new compilation task?_                   | Add once to upstream project | Add to upstream project *and every downstream project* | Add once to upstream project |
| _Can you run the pipeline locally?_                            | Yes | Yes | No |
| _Can you run the pipeline with forks or patches?_              | Yes | Yes | Requires reproducing CI server |
| _Can you use PHP tooling (eg `scssphp`) in the pipeline?_      | Yes | Yes | Yes |
| _Can you use non-PHP tooling (eg `gulp`, `docker`) in the pipeline?_ | Requires docs/installation | Requires docs/installation | Yes |
