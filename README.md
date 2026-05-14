# php
PHP version of the web development framework Genelet

# Installation
```
git clone git@github.com:genelet/php.git
```
Genelet uses _composer_ to install dependencies. Go to the newly downloaded directory _php_ and run:
```
cd php
composer install
```
which will install all the dependencies.

# Unit Tests

Genelet uses _phpunit_ to run unit tests. Set up a database named _test_ with accessing account user *genelet_test* and blank password, run:
```
composer test
```
which will run all the tests in the directory _tests_. Make sure they all passed.

Without local PHP, Composer, or MySQL, run the isolated Docker harness instead:
```
./scripts/test-docker.sh
```
The script builds a PHP 8.3 test image, starts a disposable MySQL 8 container, installs Composer dependencies, lints `src/` and `tests/`, and runs PHPUnit.

# Sample Application

The former `genelet/project-php` sample application is available in `samples/project-php`. It uses this package through a local Composer path repository and has its own Docker/MySQL test harness:
```
./scripts/test-sample-project-php.sh
```

# Using Genelet 

Please go to the [main website](http://www.genelet.com) to learn how to use the framework.
