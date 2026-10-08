<?php

namespace Tobuli\Reports\Reports;

use Tobuli\Entities\UserDriver;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupGeofenceIn;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class GeofencesInOutDriversReport extends DeviceHistoryReport
{
    const TYPE_ID = 98;

    protected $disableFields = ['speed_limit', 'stops'];
    protected $validation = ['geofences' => 'required'];

    protected $maxDriversPerGroup = 0;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.geofence_in_out') . ' ' . trans('front.drivers');
    }

    public function getMaxDriversPerGroup()
    {
        return $this->maxDriversPerGroup;
    }

    protected function getActionsList()
    {
        return [
            Duration::class,
            Distance::class,
            Drivers::class,

            GroupGeofenceIn::class,
        ];
    }

    protected function isEmptyResult($data)
    {
        return empty($data['groups']) || empty($data['groups']->all());
    }

    protected function getTable($data)
    {
        $rows = [];

        foreach ($data['groups']->all() as $group)
        {
            $drivers = $this->getDriversList($group);

            $this->maxDriversPerGroup = max($this->maxDriversPerGroup, count($drivers));

            $rows[] = array_merge($this->getDataFromGroup($group, [
                'start_at',
                'end_at',
                'duration',
                'distance',
                'location',
                'group_geofence'
            ]), $drivers);
        }

        return [
            'rows'   => $rows,
            'totals' => $this->getDataFromGroup($data['groups']->merge(), [
                'duration',
                'distance',
            ]),
        ];
    }

    protected function getDriversList($group)
    {
        if (!$group->stats()->has('drivers'))
            return [];

        $index = 1;

        $drivers = [];

        foreach ($group->stats()->get('drivers')->get() as $driver_id) {
            $drivers['driver_' . $index++] = runCacheEntity(UserDriver::class, $driver_id)
                ->implode('name_with_rfid', ', ');
        }

        return $drivers;
    }

    protected function getTotals(Group $group, array $only = [])
    {
        return [];
    }
}