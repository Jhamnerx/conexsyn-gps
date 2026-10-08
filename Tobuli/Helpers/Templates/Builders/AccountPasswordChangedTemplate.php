<?php

namespace Tobuli\Helpers\Templates\Builders;

class AccountPasswordChangedTemplate extends TemplateBuilder
{
    protected function variables($item): array
    {
        return [
            '[email]'    => $item['email'],
            '[password]' => $item['password'],
            '[phone_number]' => $item['phone_number'] ?? null,
        ];
    }

    protected function placeholders(): array
    {
        return [
            '[email]'    => 'User email',
            '[password]' => 'User password',
            '[phone_number]' => 'User phone number',
        ];
    }
}