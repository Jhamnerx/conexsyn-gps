<?php

namespace Tobuli\Services\Auth\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Tobuli\Services\Auth\AzureAuth;

class AzureClient
{
    private $config;
    private $client;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client(['base_uri' => AzureAuth::BASE_URL]);
    }

    /**
     * @throws GuzzleException
     */
    public function v2GetAccessTokenRequest()
    {
        return $this->request('POST', $this->config['tenant_id'] . AzureAuth::ROUTE_OATH_V2 . 'token', [
            'grant_type' => 'client_credentials',
            'scope' => 'https://graph.microsoft.com/.default',
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function v1GetAccessTokenWithAuthCode(string $authCode)
    {
        return $this->request('POST', $this->config['tenant_id'] . AzureAuth::ROUTE_OATH_V1 . 'token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'resource' => $this->config['resource'],
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function v1RefreshToken(string $refreshToken)
    {
        return $this->request('POST', $this->config['tenant_id'] . AzureAuth::ROUTE_OATH_V1 . 'token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'resource' => $this->config['resource'],
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function request(string $method, string $url, array $formParams)
    {
        $response = $this->client->request($method, $url, [
            'form_params' => [
                'client_id' => $this->config['client']['id'] ?? '',
                'client_secret' => $this->config['client']['secret'] ?? '',
            ] + $formParams
        ]);

        return json_decode($response->getBody()->getContents());
    }
}