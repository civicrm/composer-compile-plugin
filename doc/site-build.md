# Composer Compile Plugin: Managing the root package (for site-builders)

For PHP site-builders, the compilation process is a seamless part of the regular download (eg `composer install`).

By default, the plugin will prompt before compiling any new packages:

![Screenshot](/doc/img/composer-require.png)

You may set a configuration option to suppress this prompt.

## Mode

The "compilation mode" gives a broad preference:

* `none`: Do not compile anything automatically.
* `all`: Automatically run all compilation tasks.
* `whitelist`: Automatically compile anything on the whitelist, and reject everything else.
* `prompt` (*default*): Automatically compile anything on the whitelist, and prompt for everything else.

The option may be via environment variable, e.g.

```bash
export COMPOSER_COMPILE=all
export COMPOSER_COMPILE=none
export COMPOSER_COMPILE=whitelist
```

It may also be stored persistently in `composer.json`,  as in:

```bash
composer config extra.compile-mode all
```

or

```js
// composer.json
{
  "extra": {
    "compile-mode": "all"
  }
}
```

## Whitelist

If the mode is `prompt` or `whitelist`, then any whitelisted packages
will run compilation steps automatically:

```js
// composer.json
{
  "extra": {
    "compile-whitelist": [
      "vendor1/package1",
      "vendor2/package2",
      "vendor3/*"
    ]
  }
}
```
