<?php

namespace Tobuli\Helpers\GeoLocation\GeoServices;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Language;
use Illuminate\Support\Arr;
use Tobuli\Helpers\GeoLocation\GeoSettings;

class GeoPickpoint extends GeoNominatim
{
    public function __construct(GeoSettings $settings)
    {
        parent::__construct($settings);

        $this->url = 'https://api.pickpoint.io/v1/';
        $this->requestOptions = [
            'key'             => $this->settings->getApiKey(),
            'format'          => 'json',
            'accept-language' => Language::iso(),
            'addressdetails'  => 1,
        ];
    }

    public function byAddress($address)
    {
        $addresses = $this->request('forward', ['q' => $address]);

        return $addresses ? $this->locationObject($addresses[0]) : null;
    }

    public function listByAddress($address)
    {
        if ( ! $addresses = $this->request('forward', ['q' => $address])) {
            return [];
        }

        $locations = [];

        foreach ($addresses as $address) {
            $locations[] = $this->locationObject($address);
        }

        return $locations;
    }


    public function byCoordinates($lat, $lng)
    {
        $address = $this->request('reverse', ['lat' => $lat, 'lon' => $lng]);

        return $address ? $this->locationObject($address) : null;
    }

    protected function request($method, $options)
    {
        $url = trim($this->url, '/') . '/' . $method;

        try {
            $response = $this->client->get($url, [
                RequestOptions::QUERY => array_merge($options, $this->requestOptions)
            ]);
        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 500;
            $this->throwException($statusCode);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            $this->throwException($statusCode);
        }

        $responseBody = json_decode($response->getBody()->getContents(), true);

        if (empty($responseBody)) {
            $this->throwException(404);
        }

        if (array_key_exists('error', $responseBody)) {
            throw new \Exception(Arr::get($responseBody, 'error'));
        }

        return (is_array($responseBody) && !empty($responseBody)) ? $responseBody : null;
    }


    protected function locationObject($address)
    {
        $location = parent::locationObject($address);

        $location->address = $location->buildDisplayName([
            'road',
            'house',
            'zip',
            'city',
            'county',
            'state',
            'country',
        ]);

        return $location;
    }
}