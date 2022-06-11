# LoggerWP - Logger for WordPress Based on PSR-3

[![Total Downloads](https://img.shields.io/packagist/dt/veronalabs/logger-wp.svg)](https://packagist.org/packages/veronalabs/logger-wp)
[![Latest Stable Version](https://img.shields.io/packagist/v/veronalabs/logger-wp.svg)](https://packagist.org/packages/veronalabs/logger-wp)

LoggerWP sends your logs to wp-content directory.

This library implements the [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)
interface that you can type-hint against in your own libraries to keep a maximum of interoperability.

> ⚠️ This package is currently in beta. Breaking changes may occur until version 1.0 is tagged.

## Features

* Protect the log files by .htaccess and hash the file name
* Support custom channel name
* Support custom log directory name
* Support admin log viewer (TODO)
* Support PHP errors handler (TODO)
* Support logger exception handler (TODO)

## Installation

Install the latest version with

```bash
$ composer require veronalabs/logger-wp
```

## Basic Usage

```php
<?php

use LoggerWp\Logger;

// create a log channel
$logger = new Logger([
    'channel'  => 'wpsms', // default dev
    'dir_name' => 'wpsms-logs', // wp-content/uploads/wpsms-logs
]);

$logger->warning('Foo');
$logger->warning('Foo with context', [
    'name' => 'Sarah',
    'age'  => '23',
]);

$logger->setChannel('api');
$logger->error('Twilio encountered issue!');
```

## About

### Requirements

- LoggerWP `^1.0` works with PHP 7.4 or above.

### Submitting bugs and feature requests

Bugs and feature request are tracked on [GitHub](https://github.com/veronalabs/logger-wp/issues)

### License

LoggerWP is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
