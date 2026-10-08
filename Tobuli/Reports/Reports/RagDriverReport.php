<?php

namespace Tobuli\Reports\Reports;

use Illuminate\Support\Arr;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupDriver;
use Tobuli\History\Actions\Harsh;
use Tobuli\History\Actions\OverspeedStatic;
use Tobuli\History\Actions\Speed;
use Tobuli\Reports\DeviceHistoryReport;

class RagDriverReport extends DeviceHistoryReport
{
    const TYPE_ID = 86;

    protected $disableFields = ['geofences', 'stops', 'show_addresses', 'zones_instead'];

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.rag').' / '.trans('front.driver');;
    }

    protected function getActionsList()
    {
        return [
            Duration::class,
            Distance::class,
            Speed::class,
            OverspeedStatic::class,
            Harsh::class,
            Drivers::class,

            GroupDriver::class,
        ];
    }

    protected function generate()
    {
        $this->getDevicesQuery()->chunk(1000, function ($devices) {
            foreach ($devices as $device) {
                $this->generateDevice($device);
            }
        });
    }

    protected function generateDevice($device)
    {
        $data = $this->getDeviceHistoryData($device);

        $meta = $this->getDeviceMeta($device) + $this->getHistoryMeta($data['root']);

        foreach ($data['groups']->all() as $group) {
            $driverId = Arr::first($group->stats()->get('drivers')->get());

            if ($driverId === null) {
                continue;
            }

            $row = $this->getDataFromGroup($group, ['drivers']);
            $row['meta'] = $meta;

            $distance = $group->stats()->get('distance')->get();
            $count    = $group->stats()->get('overspeed_count')->get();
            $ha       = $group->stats()->get('harsh_acceleration_count')->get();
            $hb       = $group->stats()->get('harsh_breaking_count')->get();
            $ht       = $group->stats()->get('harsh_turning_count')->get();

            $row['distance'] = round($distance, 2);
            $row['count_overspeed'] = $count;
            $row['ha'] = $ha;
            $row['hb'] = $hb;
            $row['ht'] = $ht;

            $row['score_overspeed'] = ($count > 0 && $distance > 0) ? float($count / $distance * 100) : 0;
            $row['score_harsh_a']   = ($ha > 0 && $distance > 0) ? float($ha / $distance * 100) : 0;
            $row['score_harsh_b']   = ($hb > 0 && $distance > 0) ? float($hb / $distance * 100) : 0;
            $row['score_harsh_t']   = ($ht > 0 && $distance > 0) ? float($ht / $distance * 100) : 0;
            $row['rag']             = max(
                0,
                100 - $row['score_overspeed'] - $row['score_harsh_a'] - $row['score_harsh_b'] - $row['score_harsh_t']
            );

            $this->items[$driverId][] = $row;
        }
    }
}