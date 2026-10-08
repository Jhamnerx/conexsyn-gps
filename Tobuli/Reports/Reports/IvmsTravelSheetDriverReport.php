<?php

namespace Tobuli\Reports\Reports;

use Tobuli\Entities\Device;
use Tobuli\Entities\UserDriver;
use Tobuli\Helpers\Formatter\Facades\Formatter;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\EngineHours;
use Tobuli\History\Actions\GroupDrive;
use Tobuli\History\DeviceHistory;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;
use Tobuli\Reports\RfidFormatter;

class IvmsTravelSheetDriverReport extends DeviceHistoryReport
{
    use RfidFormatter;

    const TYPE_ID = 89;

    protected $disableFields = ['geofences', 'show_addresses', 'zones_instead'];

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
        return 'IVMS_TripDetailsReport_' . date('Ymd');
    }

    public function title()
    {
        return trans('front.ivms_trip_details');
    }

    protected function getActionsList()
    {
        return [
            DriveStop::class,
            Distance::class,
            Drivers::class,
            EngineHours::class,

            GroupDrive::class,
        ];
    }

    protected function beforeGenerate()
    {
        $this->stop_seconds = IvmsDriversEventsReport::MAX_DRIVE_PAUSE;
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

        if ($this->isEmptyResult($data)) {
            return null;
        }

        /** @var Group[] $groups */
        $groups = $data['groups']->all();

        if ($firstGroup = array_shift($groups)) {
            $firstExtendedGroup = $this->getExtendedDrive($firstGroup, $device);
            array_unshift($groups, $firstExtendedGroup ?: $firstGroup);
        }

        foreach ($groups as $group) {
            $row = ['trip_id' => $this->getTripId($group, $device)];

            $row += $this->getDataFromGroup($group, ['engine_idle']);

            $startPosition = $group->getStartPosition();
            $endPosition = $group->getEndPosition();

            $row['start_at'] = $startPosition ? Formatter::time()->convert($group->getStartPosition()->time, 'm/d/Y H:i') : null;
            $row['end_at'] = $endPosition ? Formatter::time()->convert($group->getEndPosition()->time, 'm/d/Y H:i') : null;
            $row['plate_number'] = $device->plate_number;
            $row['distance'] = round($group->getStat('distance')->value(), 2);
            $row['drive_duration'] = Formatter::duration()->human($group->getStat('drive_duration')->value(), 'hh:mm:ss');
            $row['stop_duration'] = Formatter::duration()->human($group->getStat('stop_duration')->value(), 'hh:mm:ss');
            $row['engine_idle'] = Formatter::duration()->human($group->getStat('engine_idle')->value(), 'hh:mm:ss');
            $row['start_coordinates'] = $this->formatCoordinates($startPosition);
            $row['end_coordinates'] = $this->formatCoordinates($endPosition);

            $drivers = $group->stats()->has('drivers') ? $group->stats()->get('drivers')->value() : null;

            if (empty($drivers)) {
                $row['driver_name'] = 'Unknown';
                $row['driver_rfid'] = '';

                $this->items[] = $row;

                continue;
            }

            $drivers = runCacheEntity(UserDriver::class, $drivers);

            /** @var UserDriver $driver */
            foreach ($drivers as $driver) {
                $row['driver_name'] = $driver->name;
                $row['driver_rfid'] = $this->formatRfid($driver->rfid);

                $this->items[] = $row;
            }
        }
    }

    private function getExtendedDrive(Group $drive, Device $device): ?Group
    {
        if (!$drive->getEndPosition()) {
            return null;
        }

        $history = new DeviceHistory($device);
        $history->setConfig([
            'stop_seconds'  => $this->stop_seconds,
            'stop_speed'    => $device->min_moving_speed,
        ]);

        $from = date('Y-m-d H:i:s', strtotime($this->date_from) - (24 * 60 * 60));

        $history->setRange($from, $drive->getEndPosition()->time);
        $history->registerActions($this->getActionsList());

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

    private function getTripId(Group $group, Device $device): ?string
    {
        $startPosition = $group->getStartPosition();

        if (!$startPosition) {
            return null;
        }

        return Formatter::time()->convert($startPosition->time, 'mdYHis') . '-' . $device->id;
    }

    private function formatCoordinates($position): string
    {
        if (!$position || empty($position->latitude) || empty($position->longitude)) {
            return '';
        }

        return $position->latitude . ',' . $position->longitude;
    }

    protected function toCSVData($file)
    {
        fputcsv($file, [
            'Trip ID',
            'Trip Start Date',
            'Trip End Date',
            'Distance in Kms',
            'Trip Driving Time',
            'Trip Stop Time',
            'Trip Idle Time',
            'Vehicle No',
            'Driver Name',
            'Driver Employee ID',
            'Trip Start Coordinates',
            'Trip End Coordinates',
        ]);

        foreach ($this->getItems() as $item) {
            fputcsv($file, [
                $item['trip_id'],
                $item['start_at'],
                $item['end_at'],
                $item['distance'],
                $item['drive_duration'],
                $item['stop_duration'],
                $item['engine_idle'],
                $item['plate_number'],
                $item['driver_name'],
                $item['driver_rfid'],
                $item['start_coordinates'],
                $item['end_coordinates'],
            ]);
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