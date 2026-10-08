<?php

namespace Tobuli\Helpers\LbsLocation\Service;

/**
 * @link https://developers.google.com/maps/documentation/geolocation/overview
 */
class GoogleLbs extends AbstractStandardLbs
{
    protected string $serviceUrl = 'https://www.googleapis.com/geolocation/v1/geolocate';
}