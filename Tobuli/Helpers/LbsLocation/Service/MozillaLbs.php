<?php

namespace Tobuli\Helpers\LbsLocation\Service;

/**
 * @link https://ichnaea.readthedocs.io/en/latest/api/geolocate.html
 */
class MozillaLbs extends AbstractStandardLbs
{
    protected string $serviceUrl = 'https://location.services.mozilla.com/v1/geolocate';
}