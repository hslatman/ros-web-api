<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use SlatmanROSWebAPI\ROSClient;

$config = [
    'host'      => 'http://localhost',
    'port'      => 19080,
    'username'  => 'someone@example.com',
    'password'  => 'some_random_password'
];

$c = new ROSClient($config);

$c->authenticate();

var_dump($c->getStats());
var_dump($c->getUsers());
var_dump($c->getRealms());
var_dump($c->getFunctions());
var_dump($c->getInfo());


var_dump($c->createUser('test-api-identity-2', '1234'));