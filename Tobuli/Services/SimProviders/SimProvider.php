<?php

namespace Tobuli\Services\SimProviders;

use Illuminate\Support\Str;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client as GuzzleClient;

abstract class SimProvider implements SimProviderInterface
{
    protected $url;
    protected $client;
    protected $isJsonResponse;
    protected $basicAuth;
    protected $headers = [];

    public function __construct()
    {
        $options = [
            RequestOptions::TIMEOUT => 5,
            RequestOptions::VERIFY  => false,
        ];

        if (! empty($this->basicAuth)) {
            $options[RequestOptions::AUTH] = $this->basicAuth;
        }

        $this->client = new GuzzleClient($options);
    }

    public function getName()
    {
        return str_replace('Provider', '', (new \ReflectionClass($this))->getShortName());
    }

    protected function request($path, $params = [], $method = 'get')
    {
        $path = Str::finish($this->url, '/') . $path;

        $data = [];

        if ($this->headers) {
            $data[RequestOptions::HEADERS] = $this->headers;
        }

        if ($params) {
            switch ($method) {
                case 'get':
                    $data[RequestOptions::QUERY] = $params;
                    break;
                case 'post':
                case 'patch':
                    if (($this->headers['Content-Type'] ?? null) == 'application/json')
                        $data[RequestOptions::BODY] = json_encode($params);
                    else
                        $data[RequestOptions::FORM_PARAMS] = $params;
                    break;
            }
        }

        $response = $this->client->request(strtoupper($method), $path, $data);

        if ($this->isJsonResponse) {
            return json_decode($response->getBody()->getContents(), true);
        }

        return $response->getBody()->getContents();
    }
}
