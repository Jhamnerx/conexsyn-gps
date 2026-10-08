<?php

namespace Tobuli\Helpers\Templates\Builders;

class DevicePlanTemplate extends TemplateBuilder
{
    protected function variables($item): array
    {
        return [
            '[title]'           => $item['title'],
            '[price]'           => $item['price'],
            '[duration_type]'   => $item['duration_type'],
            '[duration_value]'  => $item['duration_value'],
            '[active]'          => $item['active'],
            '[description]'     => $item['description'],
            '[submit_url]'      => $item['submit_url'],
        ];
    }

    protected function placeholders(): array
    {
        return [
            '[title]'           => 'Title',
            '[price]'           => 'Price',
            '[duration_type]'   => 'Duration type',
            '[duration_value]'  => 'Duration value',
            '[active]'          => 'Active',
            '[description]'     => 'Description',
            '[submit_url]'      => 'Purchase URL placeholder',
        ];
    }
}