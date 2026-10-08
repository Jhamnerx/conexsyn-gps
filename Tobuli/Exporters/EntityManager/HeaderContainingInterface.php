<?php

namespace Tobuli\Exporters\EntityManager;

interface HeaderContainingInterface
{
    public function setAttributeHeaders(array $attributeHeaders);

    public function getHeaders(array $attributes): array;
}