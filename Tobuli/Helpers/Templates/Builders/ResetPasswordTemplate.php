<?php

namespace Tobuli\Helpers\Templates\Builders;

class ResetPasswordTemplate extends TemplateBuilder
{
    /**
     * @param string $token
     * @return array
     */
    protected function variables($token): array
    {
        $url = route('password.reset', $token, true);

        return [
            '[url]' => $url,
            '[hyper_link]' => "<a href='$url'>$url</a>",
        ];
    }

    protected function placeholders()
    {
        return [
            '[url]' => 'Password reset url link',
            '[hyper_link]' => 'Password reset hyper link',
        ];
    }
}