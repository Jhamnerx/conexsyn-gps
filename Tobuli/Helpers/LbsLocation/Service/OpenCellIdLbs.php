<?php

namespace Tobuli\Helpers\LbsLocation\Service;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use RuntimeException;
use Tobuli\Helpers\LbsLocation\Service\Exception\AuthException;
use Tobuli\Helpers\LbsLocation\Service\Exception\RequestLimitException;

/**
 * @link https://wiki.opencellid.org/wiki/API#Getting_cell_position
 */
class OpenCellIdLbs extends AbstractCustomLbs
{
    protected string $serviceUrl = 'http://opencellid.org/cell/get?';

    protected function getRequestBody(array $data): array
    {
        if (empty($data['cellTowers'])) {
            throw new InvalidArgumentException('Mandatory parameters missing');
        }

        $body = [
            'format' => 'json',
            'key' => $this->apiKey,
        ];

        $body['radio'] = $data['radioType'];

        foreach ($data['cellTowers'] as $tower) {
            $this->append($tower, 'radioType', $body, 'radio');
            $this->append($tower, 'mobileCountryCode', $body, 'mcc');
            $this->append($tower, 'mobileNetworkCode', $body, 'mnc');
            $this->append($tower, 'locationAreaCode', $body, 'lac');
            $this->append($tower, 'cellId', $body, 'cellid');

            break;
        }

        return $body;
    }

    protected function request(array $data): array
    {
        $response = $this->client->get($this->serviceUrl, [
            RequestOptions::QUERY => $data,
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'charset' => 'UTF-8',
            ],
        ])->getBody()->getContents();

        $response = json_decode($response, true);

        return $this->validateResponse($response);
    }

    protected function validateResponse($response): array
    {
        $body = parent::validateResponse($response);

        if (!isset($body['error'])) {
            return $body;
        }

        $code = $body['code'] ?? 0;
        $error = $body['error'];

        if ($code === 2) {
            throw new AuthException($error);
        }

        if ($code === 4 || $code === 7) {
            throw new RequestLimitException($error);
        }

        throw new RuntimeException($error);
    }

    protected function formatResponse(array $data): array
    {
        if (!isset($data['lat']) || !isset($data['lon'])) {
            throw new RuntimeException(json_encode($data));
        }

        return [
            'lat' => $data['lat'],
            'lng' => $data['lon'],
            'accuracy' => $data['range'] ?? null,
        ];
    }
}