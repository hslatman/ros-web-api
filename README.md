# ros-web-api

A Realm Object Server Web API Client written in PHP.

## Installation

This is still a work in progress!

## Usage

Currently only GET type requests and User creation are supported. See _tests/test.php_ for more examples.

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use SlatmanROSWebAPI\ROSClient;

$config = [
    'host'      => 'http://localhost',
    'port'      => 9080,
    'username'  => 'someone@example.com',
    'password'  => 'some_random_password'
];

$client = new ROSClient($config);
$client->authenticate();

$realms = $client->getRealms();
```

## Notes

This implementation only allows password authentication at this time.

The current version was tested against Realm Object Server Professional Edition v1.8.3.

The team at Realm is working on creating a new version of the Realm Object Server which may provide more useful management functions out of the box.

## TODO

* Implement POST calls, e.g. for creating new Users
* Improve error handling
* Improve authentication handling
* Improve refresh token handling
* Add test framework
* Provide entities for result objects
* Implement the API calls for logs
* (Optional) handle JSON paging using limit and offset
* Improve (API) documentation