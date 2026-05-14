# Genelet PHP

Genelet PHP is a small PHP web framework for JSON-described CRUD-style web applications. A Genelet app keeps its runtime contract in `conf/config.json` and per-component `component.json` files, then supplies generated or hand-written `Filter` and `Model` classes for each component.

The framework is intentionally legacy-friendly: it uses Composer and PDO, keeps numeric framework error strings stable, and does not require Laravel, Symfony, or a modern full-stack PHP framework.

## How It Works

Requests are routed by path:

```text
<Script>/<role>/<tag>/<component>
<Script>/<role>/<tag>/<component>/<id>
```

`config.json` defines the project namespace, script path, roles, chartags, authentication providers, templates, and database connection settings. Each component's `component.json` defines tables, keys, available actions, validation, aliases, groups, fields, foreign-key helpers, and optional next-page model calls.

For a component named `question` in project namespace `Jenny`, the application provides:

```text
Jenny\Question\Filter
Jenny\Question\Model
```

At request time the controller parses the request, creates the filter and model, fills `ARGS`, executes the requested action, and returns JSON or renders a template depending on the chartag.

## Repository Layout

- `src/` - framework runtime.
- `tests/` - PHPUnit regression tests.
- `samples/project-php` - sample application using this package through a local Composer path repository.
- `views/` - minimal framework views used by tests and defaults.
- `conf/test.conf` - test configuration.
- `scripts/` - Docker-based test harnesses.

## Installation

```bash
git clone git@github.com:genelet/php.git
cd php
composer install
```

## Test

Run the local PHPUnit suite:

```bash
composer test
```

The default tests expect a MySQL database named `test` with user `genelet_test` and a blank password.

Without local PHP, Composer, or MySQL, run the isolated Docker harness:

```bash
./scripts/test-docker.sh
```

The script builds a PHP 8.3 test image, starts a disposable MySQL 8 container, installs Composer dependencies, lints `src/` and `tests/`, and runs PHPUnit.

Run the sample application test harness:

```bash
./scripts/test-sample-project-php.sh
```

## Using Genelet

1. Create a PHP app with `conf/config.json` defining `Project`, `Script`, `Template`, `Pubrole`, `Chartags`, `Roles`, and optional DB/auth settings.
2. For each component, create a `component.json` with `actions`, `current_table` or `current_tables`, and `current_key`.
3. Provide `<Project>\<Component>\Filter` and `<Project>\<Component>\Model` classes.
4. Bootstrap `Genelet\Controller` from an entrypoint such as `www/app.php`.
5. Configure Composer autoloading for the app namespace and require `genelet/php`.

Generated apps can use `no_db` for actions that do not need database work and `no_method` for actions handled entirely by filter/template behavior. JSON chartags return response bodies; HTML-like chartags render templates such as:

```text
<Template>/<role>/<component>/<action>.<tag>
```

## Compatibility Notes

Genelet keeps the legacy generated-app surface stable:

- Public framework class names and public methods remain stable.
- Numeric framework error codes and messages are preserved.
- Cookie behavior, including CLI test cookie mirroring through `$_COOKIE["SET_COOKIE"]`, is preserved.
- `ARGS`, `LISTS`, `OTHER`, existing config keys, and nextpage marker names remain part of the runtime contract.
