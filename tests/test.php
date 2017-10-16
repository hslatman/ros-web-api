<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use SlatmanROSWebAPI\ROSClient;

$config = [
    'host'  => 'http://localhost',
    'port'  => 19080
];

$c = new ROSClient($config);

$c->realms();
