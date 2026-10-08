<?php

namespace Tobuli\Helpers\Templates\Builders;

class RegistrationTemplate extends TemplateBuilder
{
    /**
     * @param $item
     * @return array
     */
    protected function variables($item)
    {
        return [
            '[email]'    => $item['email'],
            '[password]' => $item['password'],
            '[phone_number]' => $item['phone_number'] ?? null,
        ];
    }

    /**
     * @return array
     */
    protected function placeholders()
    {
        return [
            '[email]'    => 'User email',
            '[password]' => 'User password',
            '[phone_number]' => 'User phone number',
        ];
    }
}