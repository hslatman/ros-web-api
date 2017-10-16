<?php

namespace SlatmanROSWebAPI;

use Httpful\Http;
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

    /**
     * @var bool $isAuthenticated
     */
    private $isAuthenticated    = false;

    /**
     * @var bool $isAdmin
     */
    private $isAdmin            = false;

    /**
     * @var string $identity
     */
    private $identity           = '';

    /**
     * @var int $expires
     */
    private $expires            = -1;

    /**
     * @var string $appId
     */
    private $appId              = '';

    /**
     * @var string $token
     */
    private $token              = '';

    /**
     * @var array $access
     */
    private $access             = [];

    /**
     * @var object $refreshToken
     */
    private $refreshToken       = null;
    
    public function __construct(array $config = []) {
        $this->config = array_merge(
            [
                'endpoint_base'     => self::ENDPOINT_BASE,
                'port'              => self::DEFAULT_PORT,
                'host'              => self::DEFAULT_HOST,
                'app_id'            => 'io.realm.Dashboard'
            ],
            $config
        );
    }

    private function createRequestURL($path) {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $url = $host.':'.$port.'/'.$this->config['endpoint_base'].$path;

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
            'app_id'        => $this->config['app_id']
        ];

        /** @var Response $response */
        $response = Request::post($url, $payload)->sendsType(Mime::JSON)->send();

        if ($response->code === 200) {
            // Response was succesful! Let's parse it!
            $body = $response->body;
            $result = json_decode($body);

            $refresh_token      = $result->refresh_token;
            $token_data         = $refresh_token->token_data;

            /** @var string $token */
            $token              = $refresh_token->token;
            /** @var array $access */
            $access             = $token_data->access;
            /** @var int $expires */
            $expires            = $token_data->expires;
            /** @var string $identity */
            $identity           = $token_data->identity;
            /** @var bool $is_admin */
            $is_admin           = $token_data->is_admin;
            /** @var string app_id */
            $app_id             = $token_data->app_id;


            // Store the references
            $this->isAuthenticated  = true;
            $this->isAdmin          = $is_admin;
            $this->expires          = $expires;
            $this->identity         = $identity;
            $this->appId            = $app_id;

            // Store some information about the token
            $this->token            = $token;
            $this->access           = $access;
            $this->refreshToken     = $refresh_token;

        } else {
            // Some error occurred! Dump the response for now...
            var_dump($response);
        }

    }

    private function prepareAuthenticatedRequest() {

        $authenticatedTemplate = Request::init()
            ->method(Http::GET)
            ->expects(Mime::JSON)
            ->addHeader('Authorization', $this->token)
        ;

        // Set it as a template
        Request::ini($authenticatedTemplate);
    }

    public function users() {
        $url = $this->createRequestURL(self::ENDPOINT_USERS);

        $this->prepareAuthenticatedRequest();

        /** @var Response $response */
        $response = Request::get($url)->send();
    }

    public function info() {
        $url = $this->createRequestURL(self::ENDPOINT_INFO);

        $this->prepareAuthenticatedRequest();

        /** @var Response $response */
        $response = Request::get($url)->send();
    }

    public function realms() {
        $url = $this->createRequestURL(self::ENDPOINT_REALMS);

        $this->prepareAuthenticatedRequest();

        /** @var Response $response */
        $response = Request::get($url)->send();

        var_dump($response);
    }

    public function stats() {
        $url = $this->createRequestURL(self::ENDPOINT_STATS);

        $this->prepareAuthenticatedRequest();

        /** @var Response $response */
        $response = Request::get($url)->send();
    }

    public function functions() {
        $url = $this->createRequestURL(self::ENDPOINT_FUNCTIONS);

        $this->prepareAuthenticatedRequest();

        /** @var Response $response */
        $response = Request::get($url)->send();
    }

    // TODO: add logs and log lines


}
