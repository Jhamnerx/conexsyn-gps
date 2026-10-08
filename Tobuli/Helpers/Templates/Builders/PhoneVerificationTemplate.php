<?php

namespace Tobuli\Helpers\Templates\Builders;

class PhoneVerificationTemplate extends TemplateBuilder
{
    protected function variables($token): array
    {
        return [
            '[link]' => route('verification.phone.verify', $token),
        ];
    }

    protected function placeholders(): array
    {
        return [
            '[link]'  => 'Verification link',
        ];
    }
}