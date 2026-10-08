<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupGeofenceOut;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class GeofencesOutReport extends DeviceHistoryReport
{
    const TYPE_ID = 94;

    protected $disableFields = ['speed_limit', 'stops', 'zones_instead'];
    protected $validation = ['geofences' => 'required'];

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.geofence_out');
    }

    protected function getActionsList()
    {
        return [
            Distance::class,
            Drivers::class,
            Duration::class,

            GroupGeofenceOut::class,
        ];
    }

    protected function isEmptyResult($data)
    {
        return empty($data['groups']) || empty($data['groups']->all());
    }

    protected function getTable($data)
    {
        $fields = [
            'start_at',
            'end_at',
            'duration',
            'distance',
            'drivers',
            'group_geofence',
        ];

        if ($this->show_addresses) {
            $fields[] = 'location';
        }

        $rows = [];

        /** @var Group $group */
        foreach ($data['groups']->all() as $group) {
            $rows[] = $this->getDataFromGroup($group, $fields);
        }

        return $rows;
    }

    protected function getTotals(Group $group, array $only = [])
    {
        return [];
    }
}