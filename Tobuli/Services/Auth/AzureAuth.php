<?php

namespace Tobuli\Services\Auth;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Tobuli\Services\Auth\Util\AzureClient;

class AzureAuth extends AbstractAuth
{
    const BASE_URL = 'https://login.microsoftonline.com/';
    const ROUTE_OATH_V2 = '/oauth2/v2.0/';
    const ROUTE_OATH_V1 = '/oauth2/';

    const AUTH_ERRORS = [
        'AADSTS50105' => [
            'http_code' => '403',
            'msg' => 'User is not authorized within Azure AD to access this application.',
        ],
        'AADSTS90072' => [
            'http_code' => '403',
            'msg' => 'The logged on User is not in the allowed Tenant. Log in with a User in the allowed Tenant.',
        ],
    ];

    protected $rules = [
        'tenant_id' => 'required',
        'client_id' => 'required',
        'client_secret' => 'required',
    ];

    private $client;

    public function __construct()
    {
        parent::__construct();

        $this->client = new AzureClient($this->config);
    }

    public function getConfig()
    {
        $config = config('azure');
        $dbConfig = parent::getConfig();

        if (!empty($dbConfig['client_id'])) {
            $config['client']['id'] = $dbConfig['client_id'];
        }

        if (!empty($dbConfig['client_secret'])) {
            $config['client']['secret'] = $dbConfig['client_secret'];
        }

        if (!empty($dbConfig['tenant_id'])) {
            $config['tenant_id'] = $dbConfig['tenant_id'];
        }

        return $config;
    }

    public function getAuthUrl()
    {
        return self::BASE_URL . $this->config['tenant_id'] . self::ROUTE_OATH_V2 . 'authorize'
            . '?response_type=code'
            . '&client_id=' . $this->config['client']['id']
            . '&domain_hint=' . urlencode($this->config['domain_hint'])
            . '&scope=' . urldecode($this->config['scope']);
    }

    public function getLogoutUrl()
    {
        return self::BASE_URL . 'common' . self::ROUTE_OATH_V1 . 'logout';
    }

    public function prepareLogout(Authenticatable $authenticatable)
    {
        $request = \request();

        if (!$request instanceof Request) {
            return;
        }

        if (!$request->hasSession()) {
            return;
        }

        $request->session()->pull('_azure_access_token');
        $request->session()->pull('_azure_refresh_token');
        $request->session()->pull('_azure_expires_at');
    }

    public function checkConfigErrors(array $config): array
    {
        try {
            $response = $this->client->v2GetAccessTokenRequest();
        } catch (GuzzleException $e) {
            return [$e->getMessage()];
        }

        return isset($response->access_token) ? [] : ['Something went wrong'];
    }

    public function getFailedAuthError(Request $request)
    {
        if (!$request->isMethod('get')) {
            return null;
        }

        $errorDescription = trim(substr($request->query('error_description', 'SOMETHING_ELSE'), 0, 11));

        return self::AUTH_ERRORS[$errorDescription] ?? null;
    }

    public function getClient(): AzureClient
    {
        return $this->client;
    }
}