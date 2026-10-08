<?php

namespace Tobuli\Helpers\LbsLocation\Service;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use RuntimeException;
use Tobuli\Helpers\LbsLocation\Service\Exception\AuthException;
use Tobuli\Helpers\LbsLocation\Service\Exception\RequestLimitException;

abstract class AbstractStandardLbs extends AbstractLbs
{
    protected bool $verifySsl = false;

    protected function getRequestBody(array $data): array
    {
        return $data;
    }

    protected function request(array $data): array
    {
        try {
            $response = $this->client->post($this->serviceUrl . '?key=' . $this->apiKey, [
                RequestOptions::JSON => $data,
                RequestOptions::VERIFY => $this->verifySsl,
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

        $response = json_decode($response->getBody()->getContents(), true);

        return $this->validateResponse($response);
    }

    protected function validateResponse($response): array
    {
        $body = parent::validateResponse($response);

        if (!isset($body['error'])) {
            return $body;
        }

        $error = $body['error'];

        $statusCode = $error['code'];
        $message = $error['message'] ?? json_encode($error);

        if ($statusCode == 403) {
            throw new RequestLimitException($message);
        }

        if ($statusCode == 400) {
            throw new AuthException($message);
        }

        throw new RuntimeException($message);
    }

    protected function formatResponse(array $data): array
    {
        if (!isset($data['location']['lat']) || !isset($data['location']['lng'])) {
            throw new RuntimeException(json_encode($data));
        }

        return [
            'lat' => $data['location']['lat'],
            'lng' => $data['location']['lng'],
            'accuracy' => $data['accuracy'] ?? null,
        ];
    }
}