<?php

namespace Tobuli\Helpers\LbsLocation\Service;

/**
 * @link https://combain.com/api
 */
class CombainLbs extends AbstractStandardLbs
{
    protected string $serviceUrl = 'https://apiv2.combain.com';
    protected bool $verifySsl = true;
}