<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\EngineHours;
use Tobuli\History\Actions\GroupDailySplit;
use Tobuli\History\Actions\GroupDriver;
use Tobuli\History\Actions\Harsh;
use Tobuli\History\Actions\OverspeedStatic;
use Tobuli\History\Actions\Rpm;
use Tobuli\History\Actions\Speed;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class DriverMaxRpmReport extends DeviceHistoryReport
{
    const TYPE_ID = 82;

    private const DATA_KEYS = [
        'drivers',
        'start_at',
        'end_at',
        'distance',
        'drive_duration',
        'stop_duration',
        'engine_hours',
        'engine_idle',
        'rpm_max',
        'speed_max',
        'speed_avg',
        'overspeed_count',
        'harsh_breaking_count',
        'harsh_acceleration_count',
        'harsh_turning_count',
    ];

    private Group $total;

    public function __construct()
    {
        $this->total = new Group('totals');

        parent::__construct();
    }

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.driver_max_rpm');
    }

    protected function getActionsList()
    {
        return [
            Drivers::class,
            DriveStop::class,
            Distance::class,
            Duration::class,
            EngineHours::class,
            Rpm::class,
            Speed::class,
            OverspeedStatic::class,
            Harsh::class,
            GroupDriver::class,
            GroupDailySplit::class,
        ];
    }

    protected function generate()
    {
        $this->group = new Group('report');

        $this->getDevicesQuery()->chunk(1000, function ($devices) {
            foreach ($devices as $device) {
                $this->generateDevice($device);
            }
        });
    }

    protected function generateDevice($device)
    {
        $data = $this->getDeviceHistoryData($device);

        if ($this->isEmptyResult($data)) {
            return;
        }

        $meta = $this->getDeviceMeta($device);

        /** @var Group $group */
        foreach ($data['groups']->all() as $group) {
            $item = $this->getDataFromGroup($group, self::DATA_KEYS);

            $item['meta'] = $meta + $this->getHistoryMeta($group);

            $this->items[] = $item;

            $this->total->applyArray($group->stats()->all());
        }
    }

    protected function afterGenerate()
    {
        if ($this->total->hasStat('drivers')) {
            $this->totals = $this->getDataFromGroup($this->total, self::DATA_KEYS);
        }
    }
}