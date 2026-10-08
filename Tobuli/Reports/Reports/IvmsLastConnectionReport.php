<?php

namespace Tobuli\Reports\Reports;

use Formatter;
use Illuminate\Support\Carbon;
use Tobuli\Reports\DeviceReport;

class IvmsLastConnectionReport extends DeviceReport
{
    const TYPE_ID = 91;

    protected $disableFields = ['geofences', 'speed_limit', 'stops'];

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
        return 'IVMS_VehicleCommunicationListReport_' . date('Ymd');
    }

    public function title()
    {
        return trans('front.ivms_vehicle_communication');
    }

    public function getSkipBlankResults()
    {
        return true;
    }

    public function getDevicesQuery()
    {
        return parent::getDevicesQuery()->with('traccar');
    }

    protected function generateDevice($device)
    {
        $date = $device->traccar->engine_on_at;

        $item = ['plate_number' => $device->plate_number];

        if ($date) {
            $item['days'] = Carbon::now()->diffInDays($date);
            $item['date'] = Formatter::date()->convert($date) . ' 00:00';
            $item['time'] = Formatter::time()->convert($date, 'm/d/Y H:i');
        } else {
            $item['days'] = null;
            $item['date'] = null;
            $item['time'] = null;
        }

        $item['object_owner'] = $device->object_owner;

        $this->items[] = $item;
    }

    protected function toCSVData($file)
    {
        fputcsv($file, [
            'Vehicle No',
            'Days since last communication',
            'Date',
            'Last communication Date',
            'Site Name / Transporter Name',
        ]);

        foreach ($this->getItems() as $item) {
            fputcsv($file, $item);
        }
    }

    public static function isAvailable(): bool
    {
        return config('addon.reports_geofleet_in');
    }
}