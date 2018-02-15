# logger

## Ajaxy - PSR-3 Dynamic Logging for PHP

This library implements the [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)

## Installation

Install the latest version with

```bash
$ composer require ajaxy/logger
```

## Basic Usage

```php
<?php

use Ajaxy\Logger\Logger;
use Ajaxy\Logger\Handler\Stream;

// create a log channel
$log = new Logger('name');
$log->pushHandler(new Stream('path/to/dir/', Logger::WARNING));

// dynamic logging
$log->debug('Foo');
$log->error('Bar');

// this will save the logging to anything.log
$log->{anything}('Bar');
```

## About

### Requirements

- Ajaxy\Logger works with PHP 5.6 or above.

### Submitting bugs and feature requests

Bugs and feature request are tracked on [GitHub](https://github.com/n-for-all/logger/issues)

### Author

Naji Amer - <icu090@gmail.com> - <http://ajaxy.org><br />

### License

Ajaxy\Logger is licensed under the MIT License - see the `LICENSE` file for details
