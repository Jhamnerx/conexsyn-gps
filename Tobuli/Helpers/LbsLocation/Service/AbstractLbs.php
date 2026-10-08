<?php

namespace Tobuli\Helpers\LbsLocation\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use RuntimeException;

abstract class AbstractLbs implements LbsInterface
{
    protected Client $client;
    protected string $apiKey;
    protected string $serviceUrl;

    public function __construct(array $settings)
    {
        $this->apiKey = $settings['api_key'];
        $this->client = new Client([
            RequestOptions::TIMEOUT => 3,
            RequestOptions::CONNECT_TIMEOUT => 3,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getLocation(array $data): array
    {
        $data = $this->getRequestBody($data);

        $response = $this->request($data);

        return $this->formatResponse($response);
    }

    /**
     * @link https://github.com/traccar/traccar/blob/master/src/main/java/org/traccar/model/Network.java
     * For max speed everything adjusted according to traccar data models
     */
    abstract protected function getRequestBody(array $data): array;

    abstract protected function request(array $data): array;

    /**
     * @param mixed $response
     */
    protected function validateResponse($response): array
    {
        if (!is_array($response)) {
            throw new RuntimeException(is_scalar($response) ? (string)$response : json_encode($response));
        }

        return $response;
    }

    abstract protected function formatResponse(array $data): array;
}