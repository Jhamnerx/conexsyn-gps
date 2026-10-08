<?php

namespace Tobuli\Reports\Reports;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Tobuli\Entities\Device;
use Tobuli\Entities\UserDriver;
use Tobuli\Helpers\Formatter\Facades\Formatter;
use Tobuli\History\Actions\AppendOverspeeding;
use Tobuli\History\Actions\AppendSpeedLimitGeofenceMulti;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupDrive;
use Tobuli\History\Actions\GroupDriverSplit;
use Tobuli\History\Actions\GroupEngineStatus;
use Tobuli\History\Actions\GroupHarsh;
use Tobuli\History\Actions\GroupOverspeed;
use Tobuli\History\Actions\Harsh;
use Tobuli\History\Actions\Overspeed;
use Tobuli\History\Actions\Speed;
use Tobuli\History\DeviceHistory;
use Tobuli\History\Group;
use Tobuli\History\GroupContainer;
use Tobuli\Reports\DeviceHistoryReport;
use Tobuli\Reports\RfidFormatter;

class IvmsDriversEventsReport extends DeviceHistoryReport
{
    use RfidFormatter;

    public const MAX_DRIVE_PAUSE = 300;

    const TYPE_ID = 92;

    protected $disableFields = ['stops', 'show_addresses', 'zones_instead'];
    protected $validation = ['geofences' => 'required'];

    private string $dayShiftStart;
    private string $dayShiftEnd;

    /**
     * @var Group[]
     */
    private array $drives = [];
    private Device $device;

    public function __construct()
    {
        parent::__construct();

        $this->formats[] = 'csv';
    }

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function getFilename()
    {
        return 'IVMS_EventReport_' . date('Ymd');
    }

    public function title()
    {
        return trans('front.ivms_events_report');
    }

    protected function getActionsList()
    {
        return [
            AppendSpeedLimitGeofenceMulti::class,
            AppendOverspeeding::class,
            Distance::class,
            Drivers::class,
            Duration::class,
            Harsh::class,
            Overspeed::class,
            Speed::class,
            GroupEngineStatus::class,
            GroupHarsh::class,
            GroupOverspeed::class,
            GroupDrive::class,
            GroupDriverSplit::class,
        ];
    }

    protected function beforeGenerate()
    {
        $this->stop_seconds = self::MAX_DRIVE_PAUSE;

        $this->dayShiftStart = '05:00';
        $this->dayShiftEnd = '23:00';
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
        $this->device = $device;

        $data = $this->getDeviceHistoryData($device);

        if ($this->isEmptyResult($data)) {
            return null;
        }

        $this->setDrives($data['groups']);

        /** @var Group $group */
        foreach ($data['groups']->all() as $group) {
            $row = $this->getDataFromGroup($group, [
                'drivers',
                'location_start',
                'location_end',
            ]);

            $row['type'] = $group->getKey();
            $row['plate_number'] = $device->plate_number;
            $row['duration'] = $group->hasStat('duration') ? $group->getStat('duration')->value() : 0;
            $row['distance'] = $group->hasStat('distance') ? $group->getStat('distance')->value() : 0;

            $startPosition = $group->getStartPosition();
            $endPosition = $group->getEndPosition();

            $row['start_at'] = $startPosition ? Formatter::time()->convert($startPosition->time, 'm/d/Y H:i') : null;
            $row['end_at'] = $endPosition ? Formatter::time()->convert($endPosition->time, 'm/d/Y H:i') : null;
            $row['date'] = $row['end_at'];
            $row['start_time'] = $startPosition ? Carbon::parse($row['start_at'])->format('H:i') : null;
            $row['end_time'] = $endPosition ? Carbon::parse($row['end_at'])->format('H:i') : null;

            $row['start_latitude'] = $startPosition->latitude ?? null;
            $row['start_longitude'] = $startPosition->longitude ?? null;
            $row['end_latitude'] = $endPosition->latitude ?? null;
            $row['end_longitude'] = $endPosition->longitude ?? null;
            $row['end_position'] = $endPosition;

            $row['trip_id'] = $this->getTripId($group, $device);

            $drivers = $group->stats()->has('drivers') ? $group->stats()->get('drivers')->value() : null;

            if (empty($drivers)) {
                $row['driver_id'] = 0;
                $row['driver_name'] = 'Unknown';
                $row['driver_rfid'] = '';
            } else {
                /** @var UserDriver $driver */
                $driver = runCacheEntity(UserDriver::class, Arr::first($drivers))->first();

                $row['driver_id'] = $driver->id;
                $row['driver_name'] = $driver->name;
                $row['driver_rfid'] = $this->formatRfid($driver->rfid);
            }

            switch ($group->getKey()) {
                case 'overspeed':
                    $this->checkOverspeed($group, $row);
                    break;
                case 'harsh_acceleration':
                case 'harsh_breaking':
                case 'harsh_turning':
                    $this->checkSimple($group, $row);
                    break;
                case 'engine_on';
                    $this->checkDailyWork($group, $row);
                    $this->checkDailyDrive($group, $row);
                    break;
                case 'engine_off';
                    $row['trip_id'] = '';
                    $this->checkDailyRest($group, $row);
                    $this->checkWeeklyRest($group, $row);
                    break;
                case 'drive';
                    $this->checkContinuousDriving($group, $row);
                    break;
            }
        }
    }

    private function checkOverspeed(Group $group, array $row): void
    {
        if (!$group->hasStat('speed_max') || !$group->hasStat('overspeed_limit')) {
            return;
        }

        $row['threshold'] = $group->getStat('overspeed_limit')->value();
        $row['max_value'] = $group->getStat('speed_max')->value();

        if ($row['threshold'] === null || $row['max_value'] === null) {
            return;
        }

        $row['event_key'] = $this->getEventKey($group, $this->device);

        $this->items[] = $row;
    }

    private function checkSimple(Group $group, array $row): void
    {
        $row['event_key'] = $this->getEventKey($group, $this->device);

        $this->items[] = $row;
    }

    private function checkDailyWork(Group $group, array $row): void
    {
        $minDuration = $this->isOnlyDayShift($row['start_time'], $row['end_time'])
            ? 15 * 60 * 60
            : 14 * 60 * 60;

        $this->checkDurationEvent($group, $row, $minDuration, 'daily_work');
    }

    private function checkDailyDrive(Group $group, array $row): void
    {
        $minDuration = $this->isOnlyDayShift($row['start_time'], $row['end_time'])
            ? 13 * 60 * 60
            : 12 * 60 * 60;

        $this->checkDurationEvent($group, $row, $minDuration, 'daily_drive');
    }

    private function checkDailyRest(Group $group, array $row): void
    {
        $minDuration = $this->isOnlyDayShift($row['start_time'], $row['end_time'])
            ? 10 * 60 * 60
            : 8 * 60 * 60;

        $this->checkDurationEvent($group, $row, $minDuration, 'daily_rest');
    }

    private function checkWeeklyRest(Group $group, array $row): void
    {
        $minDuration = 24 * 60 * 60;

        $this->checkDurationEvent($group, $row, $minDuration, 'weekly_rest');
    }

    private function checkContinuousDriving(Group $group, array $row): void
    {
        $minDuration = $this->isOnlyDayShift($row['start_time'], $row['end_time'])
            ? 4 * 60 * 60
            : 2 * 60 * 60;

        $this->checkDurationEvent($group, $row, $minDuration, 'continuous_driving');
    }

    private function checkDurationEvent(Group $group, array $row, int $minDuration, string $type): void
    {
        $duration = $group->getStat('duration')->value();

        if ($duration < $minDuration) {
            return;
        }

        $row['type'] = $type;
        $row['threshold'] = Formatter::duration()->human($minDuration, 'hh:mm:ss');
        $row['max_value'] = Formatter::duration()->human($duration, 'hh:mm:ss');
        $row['event_key'] = $this->getEventKey($group, $this->device, $this->buildEventPrefix($type));

        $this->items[] = $row;
    }

    private function isOnlyDayShift(string $startTime, string $endTime): bool
    {
        return $startTime >= $this->dayShiftStart && $endTime >= $this->dayShiftStart
            && $startTime < $this->dayShiftEnd && $endTime < $this->dayShiftEnd
            && $startTime < $endTime;
    }

    private function getTripId(Group $group, Device $device): ?string
    {
        $tripDrive = null;

        foreach ($this->drives as $drive) {
            if ($drive->getStartPosition()->time > $group->getStartPosition()->time) {
                break;
            }

            $tripDrive = $drive;
        }

        if (!$tripDrive) {
            return null;
        }

        return Formatter::time()->convert($tripDrive->getStartPosition()->time, 'mdYHis') . '-' . $device->id;
    }

    private function getEventKey(Group $group, Device $device, ?string $prefix = null): string
    {
        $startPosition = $group->getStartPosition();

        if (!$prefix) {
            $prefix = $this->buildEventPrefix($group->getKey());
        }

        return implode("-", [
            $prefix,
            $startPosition ? Formatter::time()->convert($startPosition->time, 'mdYHis') : null,
            $device->id,
            $startPosition ? $this->idToString($startPosition->id) : null
        ]);
    }

    private function idToString(int $id): string
    {
        $chars = '123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($chars);

        $result = '';
        while ($id > 0) {
            $remainder = $id % $base;
            $result = $chars[$remainder] . $result;
            $id = intdiv($id, $base);
        }

        return $result;
    }


    private function buildEventPrefix(string $type): string
    {
        return strtoupper(implode('', array_map(
            fn ($word) => $word[0] ?? '',
            explode('_', $type)
        )));
    }

    private function setDrives(GroupContainer $groups): void
    {
        $this->drives = [];

        if ($group = $this->getPrehistoryDrive($this->device)) {
            $this->drives[] = $group;
        }

        foreach ($groups->all() as $group) {
            if ($group->getKey() === 'drive' && $group->getStartPosition()) {
                $this->drives[] = $group;
            }
        }
    }

    private function getPrehistoryDrive(Device $device): ?Group
    {
        $history = new DeviceHistory($device);
        $history->setConfig([
            'stop_seconds'  => $this->stop_seconds,
            'stop_speed'    => $device->min_moving_speed,
        ]);

        $from = date('Y-m-d H:i:s', strtotime($this->date_from) - (24 * 60 * 60));
        $to = date('Y-m-d H:i:s', strtotime($this->date_from) + $this->stop_seconds);

        $history->setRange($from, $to);
        $history->registerActions([GroupDrive::class]);

        /** @var Group[] $groups */
        $groups = $history->get()['groups']->all();

        $group = end($groups);

        if ($group === false) {
            return null;
        }

        if (!$group->getStartPosition()) {
            return null;
        }

        $endPosition = $group->getEndPosition();

        if (!$endPosition) {
            return null;
        }

        if ($endPosition->time < $this->date_from) {
            return null;
        }

        return $group;
    }

    protected function toCSVData($file)
    {
        fputcsv($file, [
            'Trip ID',
            'Date',
            'Event Type',
            'Vehicle No',
            'Driver Name',
            'Driver Employee ID',
            'Event Key',
            'Event Start Location',
            'Event End Location',
            'Event Start Date',
            'Event End Date',
            'Event Duration',
            'Event Distance (KMs)',
            'Event Threshold',
            'Event Start Coordinates',
            'Event End Coordinates',
            'Event Max Value',
        ]);

        foreach ($this->getItems() as $item) {
            fputcsv($file, [
                $item['trip_id'],
                $item['date'],
                $item['type'],
                $item['plate_number'],
                $item['driver_name'],
                $item['driver_rfid'],
                $item['event_key'],
                $item['location_start'],
                $item['location_end'],
                $item['start_at'],
                $item['end_at'],
                $item['duration'],
                $item['distance'],
                $item['threshold'],
                $item['start_coordinates'],
                $item['end_coordinates'],
                $item['max_value'],
            ]);
        }
    }

    protected function afterGenerate()
    {
        foreach ($this->items as &$item) {
            if (!isset($item['max_value'])) {
                $item['max_value'] = null;
            }

            if (!isset($item['threshold'])) {
                $item['threshold'] = null;
            }

            $item['duration'] = Formatter::duration()->human($item['duration'], 'hh:mm:ss');;
            $item['distance'] = round($item['distance'], 2);

            $item['start_coordinates'] = "{$item['start_latitude']},{$item['start_longitude']}";
            $item['end_coordinates'] = "{$item['end_latitude']},{$item['end_longitude']}";
        }
    }

    protected function getTotals(Group $group, array $only = [])
    {
        return [];
    }

    public static function isAvailable(): bool
    {
        return config('addon.reports_geofleet_in');
    }
}