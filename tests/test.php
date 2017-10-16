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

var_dump($c->stats());
var_dump($c->users());
var_dump($c->realms());
var_dump($c->functions());
var_dump($c->info());