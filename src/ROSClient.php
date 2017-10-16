<?php

namespace SlatmanROSWebAPI;

use Httpful\Mime;
use Httpful\Request;
use Httpful\Response;


class ROSClient
{
    const VERSION               = "0.0.1";
    const USER_AGENT_SUFFIX     = "ros-web-api-php-client/";

    const ENDPOINT_AUTH         = "auth";

    const ENDPOINT_BASE         = "api/";

    const ENDPOINT_REALMS       = "realms";
    const ENDPOINT_USERS        = "users";
    const ENDPOINT_STATS        = "stats";
    const ENDPOINT_INFO         = "info";
    const ENDPOINT_FUNCTIONS    = "fx";
    const ENDPOINT_LOGS         = "logs/config";
    const ENDPOINT_LOGS_LINES   = "logs/lines/level"; // e.g. all, warn, info; logs are sent via websocket

    const DEFAULT_OFFSET        = 0;
    const DEFAULT_LIMIT         = 10;

    const DEFAULT_PORT          = 9080;
    const DEFAULT_HOST          = 'http://localhost';

    /**
     * @var array $config
     */
    private $config;
    
    public function __construct(array $config = []) {
        $this->config = array_merge(
            [
                'endpoint_base'     => self::ENDPOINT_BASE,
                'port'              => self::DEFAULT_PORT,
                'host'              => self::DEFAULT_HOST,
            ],
            $config
        );
    }

    private function createRequestURL($path) {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $url = $host.':'.$port.'/'.self::ENDPOINT_BASE.$path;

        return $url;
    }

    private function createAuthURL() {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $url = $host.':'.$port.'/'.self::ENDPOINT_AUTH;

        return $url;
    }

    public function authenticate() {
        $url = $this->createAuthURL();

        $payload = [
            'data'          => $this->config['username'],
            'user_info'     => [
                'password'  => $this->config['password'],
            ],
            'provider'      => 'password',
            'app_id'        => 'io.realm.Dashboard'
        ];

        /** @var Response $response */
        $response = Request::post($url, $payload)->sendsType(Mime::JSON)->send();

        if ($response->code === 200) {
            // Response was succesful! Let's parse it!
            $body = $response->body;
            $result = json_decode($body);

            $refresh_token = $result->refresh_token;
            $token = $refresh_token->token;
            $token_data = $refresh_token->token_data;
            $access = $token_data->access;
            $app_id = $token_data->app_id;
            $expires = $token_data->expires;
            $identity = $token_data->identity;
            $is_admin = $token_data->is_admin;


            var_dump($refresh_token);
            var_dump($token);
            var_dump($token_data);
            var_dump($access);
            var_dump($app_id, $expires, $identity, $is_admin);
            var_dump($result);

        } else {
            // Some error occurred! Dump the response for now...
            var_dump($response);
        }

    }

    public function users() {
        $url = $this->createRequestURL(self::ENDPOINT_USERS);

        /** @var Response $response */
        $response = Request::get($url)->send();
    }

    public function info() {
        $url = $this->createRequestURL(self::ENDPOINT_INFO);

        /** @var Response $response */
        $response = Request::get($url)->send();
    }

    public function realms() {
        $url = $this->createRequestURL(self::ENDPOINT_REALMS);

        /** @var Response $response */
        $response = Request::get($url)->send();
    }

    public function stats() {
        $url = $this->createRequestURL(self::ENDPOINT_STATS);

        /** @var Response $response */
        $response = Request::get($url)->send();
    }

    public function functions() {
        $url = $this->createRequestURL(self::ENDPOINT_FUNCTIONS);

        /** @var Response $response */
        $response = Request::get($url)->send();
    }

    // TODO: add logs and log lines


}
