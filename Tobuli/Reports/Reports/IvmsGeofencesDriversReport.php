<?php

namespace Tobuli\Reports\Reports;

use Tobuli\Entities\UserDriver;
use Tobuli\Helpers\Formatter\Facades\Formatter;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\GroupGeofenceIn;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;
use Tobuli\Reports\RfidFormatter;

class IvmsGeofencesDriversReport extends DeviceHistoryReport
{
    use RfidFormatter;

    const TYPE_ID = 90;

    protected $disableFields = ['speed_limit', 'stops', 'show_addresses', 'zones_instead'];
    protected $validation = ['geofences' => 'required'];

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
        return 'IVMS_GeofenceMovementReport_' . date('Ymd');
    }

    public function title()
    {
        return trans('front.ivms_geofencing_report');
    }

    protected function getActionsList()
    {
        return [
            Drivers::class,

            GroupGeofenceIn::class,
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

        if ($this->isEmptyResult($data)) {
            return null;
        }

        /** @var Group $group */
        foreach ($data['groups']->all() as $group) {
            $row = $this->getDataFromGroup($group, ['group_geofence']);

            $startPosition = $group->getStartPosition();
            $endPosition = $group->getEndPosition();

            $row['start_at'] = $startPosition ? Formatter::time()->convert($startPosition->time, 'm/d/Y H:i') : null;
            $row['end_at'] = $endPosition ? Formatter::time()->convert($group->getEndPosition()->time, 'm/d/Y H:i') : null;
            $row['plate_number'] = $device->plate_number;
            $row['entry_latitude'] = $startPosition->latitude;
            $row['entry_longitude'] = $startPosition->longitude;

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

    private function formatCoordinates($position): string
    {
        if (!$position || empty($position->latitude) || empty($position->longitude)) {
            return '( )';
        }

        return '(' . $position->latitude . ' ' . $position->longitude . ')';
    }

    protected function toCSVData($file)
    {
        fputcsv($file, [
            'Geofence Name',
            'Geofence Enter Time',
            'Geofence Exit Time',
            'Vehicle No',
            'Driver Name',
            'Driver Employee ID',
            'Geofence Lat',
            'Geofence Long',
        ]);

        foreach ($this->getItems() as $item) {
            fputcsv($file, [
                $item['group_geofence'],
                $item['start_at'],
                $item['end_at'],
                $item['plate_number'],
                $item['driver_name'],
                $item['driver_rfid'],
                $item['entry_latitude'],
                $item['entry_longitude'],
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