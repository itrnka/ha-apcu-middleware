# APCu cache driver middleware for *ha* framework

APCu cache middleware for ha framework. This is a proxy to PHP APCu extension. Driver implements cache interface `ha\Middleware\Cache\Cache` from ha framework. So it can be used as a cache driver.


## Installation

Installation is available via composer:

```bash
composer require itrnka/ha-apcu-middleware
```

## Requirements

This package is based on [*ha* framework](https://github.com/itrnka/ha-framework). Composer installs *ha* framework if it is not already installed. [*APCu*](http://php.net/manual/en/book.apcu.php) php module is also required.

## Configuration

Required configuration keys:

- `name`: by ha framework requirements
- `keyPrefix`: *string* prefix for cache keys in your application
- `defaultTTL`: *int* default TTL value (must be `>= 0`)

Add your configuration to the configuration file in *ha* framework according to this example:

> Note: only single instance can be used in your application, class has pseudo singleton protection.

```php
$cfg['middleware'] = [

    // ...

    // APCu cahce
    [
        ha\Middleware\Cache\APCu\APCu::class,
        [
            'name' => 'apc',
            'keyPrefix' => 'someUniqueKeyForAppInYourMachine',
            'defaultTTL' => 0,
        ]
    ],

    // ...

];
```

Then the driver will be available as follows:

```php
// middleware instance
$apc = main()->middleware->apc;

// example call:
$value = main()->middleware->apc->get('myValue', null);
```
