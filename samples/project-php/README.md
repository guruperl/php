# Genelet PHP Sample Project

This is the former `genelet/project-php` application merged into the framework repository as a full sample app.

The sample uses the framework in `../..` as the source of truth through a Composer path repository. It intentionally does not include the old GraphQL helper or `webonyx/graphql-php` dependency.

## Layout

- `conf/` - application config and MySQL seed SQL
- `src/` - Tabilet application models, filters, and test beacons
- `tests/` - sample application tests
- `views/` - server-rendered templates
- `www/` - PHP entrypoints, Vue components, and browser client assets

## Test With Docker

From the framework repository root:

```sh
./scripts/test-sample-project-php.sh
```

The script builds the shared PHP test image, starts a disposable MySQL 8 container, imports `conf/01_init.sql` and `conf/02_sample_app.sql`, installs the sample Composer dependencies, lints the sample PHP files, and runs PHPUnit.

## Local Development

If PHP, Composer, and MySQL are installed locally:

```sh
cd samples/project-php
composer install
composer test
```

The default `conf/config.json` uses database `demo2020` with user `genelet_sample`. For Docker tests, the config is overridden with `GENELET_SAMPLE_DB_DSN`, `GENELET_SAMPLE_DB_USER`, and `GENELET_SAMPLE_DB_PASSWORD`.

Runtime paths in `conf/config.json` are relative to this sample directory and are normalized by `Tabilet\Application`. Config values can also be set to `${ENV_NAME}` placeholders; the sample `Application` expands them at startup.
