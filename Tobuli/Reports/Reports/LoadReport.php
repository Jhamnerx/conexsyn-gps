<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\GroupLoad;
use Tobuli\History\Actions\LoadCount;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class LoadReport extends DeviceHistoryReport
{
    const TYPE_ID = 37;

    protected $disableFields = ['geofences', 'speed_limit', 'stops'];
    protected $validation = ['min_load_duration' => 'required|in:0,60,120,180,240,300,600,900,1200,1800,3600,7200,18000'];

    public function getInputParameters(): array
    {
        return [
            \Field::number('min_detect_change', trans('validation.attributes.min_detect_change') . ' (%)', 25),
            \Field::select('min_load_duration', trans('validation.attributes.min_load_duration'), '300')
                ->setOptions([
                    '0'     => trans('front.instant'),
                    '60'    => 1 . ' ' . trans('front.minute'),
                    '120'   => 2 . ' ' . trans('front.minutes'),
                    '180'   => 3 . ' ' . trans('front.minutes'),
                    '240'   => 4 . ' ' . trans('front.minutes'),
                    '300'   => 5 . ' ' . trans('front.minutes'),
                    '600'   => 10 . ' ' . trans('front.minutes'),
                    '900'   => 15 . ' ' . trans('front.minutes'),
                    '1200'  => 20 . ' ' . trans('front.minutes'),
                    '1800'  => 30 . ' ' . trans('front.minutes'),
                    '3600'  => 1 . ' ' . trans('front.hour'),
                    '7200'  => 2 . ' ' . trans('front.hours'),
                    '18000' => 5 . ' ' . trans('front.hours'),
                ])
        ];
    }

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.loading_unloading');
    }

    protected function getActionsList()
    {
        LoadCount::$loadStates = [1, 0];
        GroupLoad::$loadStates = [1, 0];

        return [
            GroupLoad::class,
            LoadCount::class,
        ];
    }

    protected function getTable($data)
    {
        $rows = [];

        /** @var Group $group */
        foreach ($data['groups']->all() as $group)
        {
            $row = $this->getDataFromGroup($group, [
                'previous_load',
                'current_load',
                'difference',
                'location',
            ]);

            $row['time'] = $group->getEndAt();
            $row['state'] = $group->getEndPosition()->loadChange['state'];

            $rows[] = $row;
        }

        return [
            'rows'   => $rows,
            'totals' => [],
        ];
    }

    protected function getDeviceMeta($device)
    {
        $metas = parent::getDeviceMeta($device);

        $metas['sensor'] = [
            'title' => trans('front.sensor'),
            'value' => $device->getLoadSensor()->name ?? '',
        ];

        return $metas;
    }

    protected function getTotals(Group $group, array $only = [])
    {
        return parent::getTotals($group, ['loading_count', 'unloading_count']);
    }

    protected function isEmptyResult($data)
    {
        return empty($data['groups']) || empty($data['groups']->all());
    }
}