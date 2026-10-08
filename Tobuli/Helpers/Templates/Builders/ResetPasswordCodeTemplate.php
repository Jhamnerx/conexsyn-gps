<?php

namespace Tobuli\Helpers\Templates\Builders;

class ResetPasswordCodeTemplate extends TemplateBuilder
{
    protected function variables($item): array
    {
        return [
            '[code]' => $item['code'],
        ];
    }

    protected function placeholders(): array
    {
        return [
            '[code]' => 'Code',
        ];
    }
}