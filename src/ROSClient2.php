<?php

namespace SlatmanROSWebAPI;

use Httpful\Http;
use Httpful\Mime;
use Httpful\Request;
use Httpful\Response;


class ROSClient2
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
     * @var string $salt
     */
    private $salt               = '';

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
            //$result = json_decode($body);

            $refresh_token      = $body->refresh_token;
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
            /** @var string $app_id */
            $app_id             = $token_data->app_id;
            /** @var string $salt */
            $salt               = $token_data->salt;


            // Store the references
            $this->isAuthenticated  = true;
            $this->isAdmin          = $is_admin;
            $this->expires          = $expires;
            $this->identity         = $identity;
            $this->appId            = $app_id;
            $this->salt             = $salt;

            // Store some information about the token
            $this->token            = $token;
            $this->access           = $access;
            $this->refreshToken     = $refresh_token;

        } else {
            // Some error occurred! Dump the response for now...
            var_dump($response);
        }

    }

    private function prepareAuthenticatedGetRequest() {

        $authenticatedTemplate = Request::init()
            ->method(Http::GET)
            ->expects(Mime::HTML)
            ->addHeader('Authorization', $this->token)
        ;

        // Set it as a template
        Request::ini($authenticatedTemplate);
    }

    private function prepareAuthenticatedPostRequest() {

        $authenticatedTemplate = Request::init()
            ->method(Http::POST)
            ->expects(Mime::JSON)
            ->sendsType(Mime::JSON)
            ->addHeader('Authorization', $this->token)
        ;

        // Set it as a template
        Request::ini($authenticatedTemplate);
    }

    private function hasValidResponseCode(Response $response) {
        return $response->code >= 200 && $response->code < 300;
    }

    public function getUsers() {
        $url = $this->createRequestURL(self::ENDPOINT_USERS);

        $this->prepareAuthenticatedGetRequest();

        /** @var Response $response */
        $response = Request::get($url)->send();

        if ($this->hasValidResponseCode($response)) {
            $result = $response->body;

            return $result;
        } else {
            var_dump($response);
        }

        return false;
    }

    public function createUser($providerIdentity, $password, $isAdmin = false) {
        $url = $this->createRequestURL(self::ENDPOINT_USERS);

        $this->prepareAuthenticatedPostRequest();

        $payload = [
            'isAdmin'           => $isAdmin,
            'accounts'          => [[
                'provider'      => 'password',
                'provider_id'   => $providerIdentity,
                'data'          => [
                    'password'  => $password
                ]
            ]]
        ];

        /** @var Response $response */
        $response = Request::post($url, $payload)->send();

        if ($this->hasValidResponseCode($response)) {
            $result = $response->body;
            // example response: {"accounts":[{"provider":"password","provider_id":"value of $providerIdentity"}],"id":"32 character identifier","isAdmin":false}
            // $accounts can be used to login to an identity; an identity could have more than one provider
            //$accounts = $result->accounts;
            $id = $result->id;

            return $id;
        } else {

            // example error:  ["raw_body"]=>
            //string(161) "{"status":400,"type":"https://realm.io/docs/object-server/problems/existing-account","title":"The account cannot be registered as it exists already.","code":613}"
            var_dump($response);
        }

        return null;
    }

    public function createAdminUser($providerIdentity, $password) {
        return $this->createUser($providerIdentity, $password, $isAdmin = true);
    }

    public function getInfo() {
        $url = $this->createRequestURL(self::ENDPOINT_INFO);

        $this->prepareAuthenticatedGetRequest();

        /** @var Response $response */
        $response = Request::get($url)->send();

        if ($this->hasValidResponseCode($response)) {
            $result = $response->body;

            return $result;
        } else {
            var_dump($response);
        }

        return false;
    }

    public function getRealms() {
        $url = $this->createRequestURL(self::ENDPOINT_REALMS);

        $this->prepareAuthenticatedGetRequest();

        /** @var Response $response */
        $response = Request::get($url)->send();

        if ($this->hasValidResponseCode($response)) {
            $result = $response->body;

            return $result;
        } else {
            var_dump($response);
        }

        return false;
    }

    public function getStats() {
        $url = $this->createRequestURL(self::ENDPOINT_STATS);

        $this->prepareAuthenticatedGetRequest();

        /** @var Response $response */
        $response = Request::get($url)->send();

        var_dump($response);

        die;

        if ($this->hasValidResponseCode($response)) {
            $result = $response->body;

            return $result;
        } else {
            var_dump($response);
        }

        return false;
    }

    public function getFunctions() {
        $url = $this->createRequestURL(self::ENDPOINT_FUNCTIONS);

        $this->prepareAuthenticatedGetRequest();

        /** @var Response $response */
        $response = Request::get($url)->send();

        if ($this->hasValidResponseCode($response)) {
            $result = $response->body;

            return $result;
        } else {
            var_dump($response);
        }

        return false;
    }

    // TODO: add logs and log lines; these are implemented partly through websockets

}
