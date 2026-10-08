<?php namespace Tobuli\Helpers\Dashboard\Blocks;

class DeviceActivityBlock extends Block
{
    protected function getName()
    {
        return 'device_activity';
    }

    protected function getContent()
    {
        $all = $this->user->devices()->count();

        if (empty($all))
            return null;

        $online = $this->user->devices()->online()->count();
        $offline = $all - $online;

        return [
            'statuses' => [
                [
                    'label' => trans('global.online'),
                    'data'  => round($this->calcPercentage($all, $online), 1),
                    'color' => '#16A34A',
                ],
                [
                    'label' => trans('global.offline'),
                    'data'  => round($this->calcPercentage($all, $offline), 1),
                    'color' => '#94A3B8',
                ],
            ]
        ];
    }

    private function calcPercentage($all, $part)
    {
        if (empty($all))
            return 0;

        return (($part) / $all) * 100;
    }
}