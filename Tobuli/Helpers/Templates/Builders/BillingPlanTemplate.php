<?php

namespace Tobuli\Helpers\Templates\Builders;

class BillingPlanTemplate extends TemplateBuilder
{
    protected function variables($item): array
    {
        return [
            '[title]'           => $item['title'],
            '[price]'           => $item['price'],
            '[objects]'         => $item['objects'],
            '[duration_type]'   => $item['duration_type'],
            '[duration_value]'  => $item['duration_value'],
        ];
    }

    protected function placeholders(): array
    {
        return [
            '[title]'               => 'Title',
            '[price]'               => 'Price',
            '[objects]'             => 'Objects',
            '[duration_type]'       => 'Duration type',
            '[duration_value]'      => 'Duration value',
            '[submit_url]'          => 'Purchase URL placeholder',
            '[begin.permission]'    => 'Opening tag of permission item styling',
            '[title.permission]'    => 'Permission title',
            '[end.permission]'      => 'Closing tag of permission item styling',
        ];
    }

    public function buildTemplate($template, $data = null)
    {
        $result = parent::buildTemplate($template, $data);
        $result['body'] = strtr($result['body'], ['[submit_url]' => $data['submit_url']]);

        $permContainer = $this->extractPermissionContainer($result);

        if ($permContainer === null) {
            return $result;
        }

        foreach ($data['permissions'] ?? [] as $permission => $modes) {
            $result['body'] .= strtr($permContainer, [
                '[title.permission]' => trans('front.' . $permission),
            ]);
        }

        return $result;
    }

    private function extractPermissionContainer(array &$result): ?string
    {
        $permIndexBegin = strpos($result['body'], '[begin.permission]');
        $permIndexEnd = strpos($result['body'], '[end.permission]');

        if ($permIndexBegin === false || $permIndexEnd === false) {
            return null;
        }

        $body = $result['body'];
        $bodyStart = substr($body, 0, $permIndexBegin);
        $bodyEnd = substr($body, $permIndexEnd + 16);

        $permIndexBegin += 18; // begin from the end of tag

        $container = substr($result['body'], $permIndexBegin, $permIndexEnd - $permIndexBegin);

        $result['body'] = trim($bodyStart . $bodyEnd);

        return $container;
    }
}