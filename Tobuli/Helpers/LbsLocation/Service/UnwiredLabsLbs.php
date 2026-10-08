<?php

namespace Tobuli\Helpers\LbsLocation\Service;

use GuzzleHttp\RequestOptions;
use RuntimeException;
use Tobuli\Helpers\LbsLocation\Service\Exception\AuthException;
use Tobuli\Helpers\LbsLocation\Service\Exception\RequestLimitException;

/**
 * @link https://unwiredlabs.com/api
 */
class UnwiredLabsLbs extends AbstractCustomLbs
{
    protected string $serviceUrl = 'https://us1.unwiredlabs.com/v2/process.php';

    protected function getRequestBody(array $data): array
    {
        $body = [
            'token' => $this->apiKey,
            'address' => 0,
        ];

        $body['radio'] = $data['radioType'];
        $body['ipf'] = (int)$data['considerIp'];
        $this->append($data, 'homeMobileCountryCode', $body, 'mcc');
        $this->append($data, 'homeMobileNetworkCode', $body, 'mnc');

        if (isset($data['cellTowers'])) {
            $body['cells'] = [];

            foreach ($data['cellTowers'] as $tower) {
                $cell = [];

                $this->append($tower, 'radioType', $cell, 'radio');
                $this->append($tower, 'cellId', $cell, 'cid');
                $this->append($tower, 'locationAreaCode', $cell, 'lac');
                $this->append($tower, 'mobileCountryCode', $cell, 'mcc');
                $this->append($tower, 'mobileNetworkCode', $cell, 'mnc');
                $this->append($tower, 'signalStrength', $cell, 'signal');

                $body['cells'][] = $cell;
            }
        }

        if (isset($data['wifiAccessPoints'])) {
            $body['wifi'] = [];

            foreach ($data['wifiAccessPoints'] as $wap) {
                $wifi = [];

                $this->append($wap, 'macAddress', $wifi, 'bssid');
                $this->append($wap, 'signalStrength', $wifi, 'signal');
                $this->append($wap, 'channel', $wifi, 'channel');

                $body['wifi'][] = $wifi;
            }
        }

        return $body;
    }

    protected function request(array $data): array
    {
        $response = $this->client->post($this->serviceUrl, [
            RequestOptions::JSON => $data,
            RequestOptions::VERSION => 1.1,
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

        if (!isset($body['status'])) {
            throw new RuntimeException(json_encode($body));
        }

        $status = $body['status'];

        if ($status === 'ok') {
            return $body;
        }

        $msg = $body['message'] ?? '';

        if (strpos($msg, 'Invalid token') !== false) {
            throw new AuthException($msg);
        }

        if (strpos($msg, 'Token balance over') !== false) {
            throw new RequestLimitException($msg);
        }

        throw new RuntimeException($msg);
    }

    protected function formatResponse(array $data): array
    {
        if (!isset($data['lat']) || !isset($data['lon'])) {
            throw new RuntimeException(json_encode($data));
        }

        return [
            'lat' => $data['lat'],
            'lng' => $data['lon'],
            'accuracy' => $data['accuracy'] ?? null,
        ];
    }
}