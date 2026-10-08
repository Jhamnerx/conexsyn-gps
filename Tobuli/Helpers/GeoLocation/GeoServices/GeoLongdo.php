<?php
namespace Tobuli\Helpers\GeoLocation\GeoServices;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Tobuli\Helpers\GeoLocation\GeoSettings;
use Tobuli\Helpers\GeoLocation\Location;

class GeoLongdo extends AbstractGeoService
{
    private GuzzleClient $client;
    private array $requestOptions = [];

    public function __construct(GeoSettings $settings)
    {
        parent::__construct($settings);

        $this->client = new GuzzleClient([
            RequestOptions::TIMEOUT  => 5,
            RequestOptions::VERIFY   => false,
        ]);

        $this->requestOptions = [
            'locale' => config('tobuli.languages.'.config('app.locale').'.iso', 'en'),
            'key'      => $this->settings->getApiKey(),
        ];
    }

    public function byAddress($address)
    {
        $address = $this->request('https://search.longdo.com/mapsearch/json/search', ['keyword' => $address]);

        return $address ? $this->locationObject($address) : null;
    }

    public function listByAddress($address)
    {
        $addresses = $this->request(
            'https://search.longdo.com/mapsearch/json/suggest',
            [
                'keyword' => $address,
                'limit' => 5,
            ]
        );

        if ( ! $addresses) {
            return [];
        }

        $locations = [];

        foreach ($addresses as $address) {
            $locations[] = $this->byAddress($address);
        }

        return $locations;
    }

    public function byCoordinates($lat, $lng)
    {
        $address = $this->request('https://api.longdo.com/map/services/address', [
            'lat' => $lat,
            'lon' => $lng
        ]);

        return $address ? $this->locationObject($address, $lat, $lng) : null;
    }

    private function request($url, $options)
    {
        try {
            $response = $this->client->get($url, [
                RequestOptions::QUERY => array_merge($options, $this->requestOptions)
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 500;
            $responseBody = null;
        }

        if ($statusCode !== 200 || is_null($responseBody)) {
            throw new \Exception(Arr::get($responseBody, 'error_message') ?: 'Geocoder API error.');
        }

        if (empty($responseBody)) {
            return null;
        }

        if (strpos($url, 'json/suggest') !== false) {
            if (! isset($responseBody['data'])) {
                return null;
            }

            return array_column($responseBody['data'], 'w');
        }

        if (strpos($url, 'json/search') !== false) {
            return $responseBody['data'][0] ?? null;
        }

        return $responseBody;
    }

    private function locationObject($address, $lat = null, $lng = null)
    {
        return new Location([
            'lat'           => $address['lat'] ?? $lat,
            'lng'           => $address['lon'] ?? $lng,
            'address'       => $this->getAddress($address),
            'country'       => Arr::get($address, 'country'),
            'state'         => Arr::get($address, 'province'),
            'county'        => Arr::get($address, 'district'),
            'city'          => Arr::get($address, 'subdistrict'),
            'zip'           => Arr::get($address, 'postcode'),
        ]);
    }

    private function getAddress($addressData)
    {
        if (! is_array($addressData)) {
            return $addressData;
        }

        if (! empty($addressData['address'])) {
            if (! empty($addressData['name'])) {
                return $addressData['name'].', '.$addressData['address'];
            }

            return $addressData['address'];
        }

        if (! empty($addressData['name'])) {
            return $addressData['name'];
        }

        $addressParts = [
            $addressData['road'] ?? null,
            $addressData['subdistrict'] ?? null,
            $addressData['district'] ?? null,
            $addressData['province'] ?? null,
            $addressData['postcode'] ?? null,
            $addressData['country'] ?? null,
        ];
        $addressParts = array_filter($addressParts);

        return implode(', ', $addressParts);
    }
}
