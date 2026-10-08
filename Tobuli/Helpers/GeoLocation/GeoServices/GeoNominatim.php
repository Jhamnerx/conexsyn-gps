<?php

namespace Tobuli\Helpers\GeoLocation\GeoServices;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Language;
use Tobuli\Helpers\GeoLocation\Exceptions\GeocoderServerException;
use Tobuli\Helpers\GeoLocation\GeoSettings;
use Tobuli\Helpers\GeoLocation\Location;

class GeoNominatim extends AbstractGeoService
{
    protected string $url;
    protected GuzzleClient $client;
    protected array $requestOptions = [];

    public function __construct(GeoSettings $settings)
    {
        parent::__construct($settings);

        $this->url = $this->settings->getApiUrl();
        $this->client = new GuzzleClient([
            RequestOptions::TIMEOUT  => 5,
            RequestOptions::VERIFY   => false,
            RequestOptions::HEADERS  => [
                'User-Agent'    => 'GPSWOX application',
                'Referer'       => url('/'),
            ],
        ]);

        $this->requestOptions = [
            'format'          => 'json',
            'accept-language' => Language::iso(),
            'addressdetails'  => 1,
        ];
    }


    public function byAddress($address)
    {
        $addresses = $this->request('search', ['q' => $address]);

        return $addresses ? $this->locationObject($addresses[0]) : null;
    }

    public function listByAddress($address)
    {
        if ( ! $addresses = $this->request('search', ['q' => $address])) {
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
        $url = trim($this->url, '/') . '/' . $method . '.php';

        try {
            $response = $this->client->get($url, $this->getRequestOptions($options));
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

    protected function getRequestOptions(array $params): array
    {
        return [
            RequestOptions::QUERY => array_merge($params, $this->requestOptions)
        ];
    }

    protected function locationObject($address)
    {
        return new Location([
            'place_id'      => Arr::get($address, 'place_id'),
            'lat'           => Arr::get($address, 'lat'),
            'lng'           => Arr::get($address, 'lon'),
            'address'       => Arr::get($address, 'display_name'),
            'type'          => Arr::get($address, 'osm_type'),
            'country'       => $this->getFirst($address['address'], ['country']),
            'country_code'  => $this->getFirst($address['address'], ['country_code']),
            'county'        => $this->getFirst($address['address'], ['county']),
            'state'         => $this->getFirst($address['address'], ['state', 'region']),
            'city'          => $this->getFirst($address['address'], ['city', 'town', 'village', 'municipality', 'city_district']),
            'road'          => $this->getFirst($address['address'], ['road']),
            'house'         => $this->getFirst($address['address'], ['house_number', 'house_name']),
            'zip'           => $this->getFirst($address['address'], ['postcode']),
        ]);
    }

    protected function getFirst(array $source, array $fields)
    {
        foreach ($fields as $field) {
            $value = Arr::get($source, $field);

            if (!empty($value))
                return $value;
        }

        return null;
    }

    protected function throwException($status_code)
    {
        switch ($status_code) {
            case 429:
                throw new \Exception('Geocoder API request limit exceeded.');
            case 401:
                throw new \Exception('Geocoder API Key is invalid or inactive');
            case 404:
                throw new \Exception('Unable to geocode');
            default:
                throw new GeocoderServerException('Geocoder API error. Code: ' . $status_code);
        }
    }
}