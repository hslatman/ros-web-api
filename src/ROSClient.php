<?php

namespace SlatmanROSWebAPI;

use Httpful\Request;


class ROSClient
{
    const VERSION = "0.0.1";
    const USER_AGENT_SUFFIX = "ros-web-api-php-client/";
    
    public function __construct(array $config = []) {
	    $response = Request::get('google.com')->send();

	    var_dump($response);
    }
}
