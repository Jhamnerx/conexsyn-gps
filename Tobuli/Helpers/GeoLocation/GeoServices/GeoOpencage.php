<?php

namespace Tobuli\Helpers\GeoLocation\GeoServices;

use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client as GuzzleClient;
use Language;
use Illuminate\Support\Arr;
use Tobuli\Helpers\GeoLocation\GeoSettings;
use Tobuli\Helpers\GeoLocation\Location;


class GeoOpencage extends AbstractGeoService
{
    private $url;
    private $requestOptions = [];

    private GuzzleClient $client;


    public function __construct(GeoSettings $settings)
    {
        parent::__construct($settings);

        $this->url = 'https://api.opencagedata.com/geocode/v1/json';
        $this->requestOptions = [
            'language' => Language::iso(),
            'key'      => $this->settings->getApiKey(),
        ];

        $this->client = new GuzzleClient([
            RequestOptions::TIMEOUT => 5,
            RequestOptions::VERIFY  => false,
        ]);
    }


    public function byAddress($address)
    {
        $address = $this->request(['q' => $address]);

        return $address ? $this->locationObject($address[0]) : null;
    }

    public function listByAddress($address)
    {
        if ( ! $addresses = $this->request(['q' => $address])) {
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
        $address = $this->request(['q' => $lat . ',' . $lng]);

        return $address ? $this->locationObject($address[0]) : null;
    }

    private function request($options)
    {
        $response = $this->client->get($this->url, [
            RequestOptions::QUERY => array_merge($options, $this->requestOptions)
        ]);

        $response_body = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() != 200) {
            throw new \Exception(Arr::get($response_body, 'status.message') ?: 'Geocoder API error.');
        }

        return $response_body['results'];
    }

    private function locationObject($address)
    {
        return new Location([
            'place_id'      => md5(Arr::get($address, 'formatted')),
            'lat'           => Arr::get($address, 'geometry.lat'),
            'lng'           => Arr::get($address, 'geometry.lng'),
            'address'       => Arr::get($address, 'formatted'),
            'type'          => Arr::get($address, 'components._type'),
            'country'       => Arr::get($address, 'components.country'),
            'country_code'  => Arr::get($address, 'components.country_code'),
            'county'        => Arr::get($address, 'components.county'),
            'state'         => Arr::get($address, 'components.state'),
            'city'          => Arr::get($address, 'components.city'),
            'road'          => Arr::get($address, 'components.road'),
            'house'         => Arr::get($address, 'components.house_number'),
            'zip'           => Arr::get($address, 'components.postcode'),
        ]);
    }
}
