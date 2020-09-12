# Composer Compile Plugin: Managing the root package (for site-builders)

For PHP site-builders, the compilation process is a seamless part of the regular download (eg `composer install`).

By default, the plugin will prompt before compiling any new packages:

![Screenshot](/doc/img/composer-require.png)

You may set configuration options to suppress this prompt; these are described below.

## Mode

The "compilation mode" gives a broad preference. It may be set as an environment-variable:

* `COMPOSER_COMPILE=none`: Do not compile anything automatically.
* `COMPOSER_COMPILE=all`: Automatically run all compilation tasks.
* `COMPOSER_COMPILE=whitelist`: Automatically compile anything on the whitelist, and reject everything else.
* `COMPOSER_COMPILE=prompt` (*default*): Automatically compile anything on the whitelist, and prompt for everything else.

Alternatively, the same option may be added persistently to `composer.json`,  e.g.:

```bash
composer config extra.compile-mode all
```

or

```js
// FILE: composer.json
{
  "extra": {
    "compile-mode": "all"
  }
}
```

If both the environment variable (`COMPOSER_COMPILE`) and the JSON option (`extra.compile-mode`) are set, then
the environment-variable takes precedence.

## Whitelist

If the mode is `prompt` or `whitelist`, then any whitelisted packages will run compilation steps automatically:

```js
// FILE: composer.json
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
