<?php

namespace Tobuli\Helpers\LbsLocation\Service;

interface LbsInterface
{
    /**
     * @throws \Exception
     */
    public function getLocation(array $data): array;
}