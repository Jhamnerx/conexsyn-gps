<?php

namespace Tobuli\Reports\Reports;

use Tobuli\Entities\UserDriver;
use Tobuli\Helpers\Formatter\Facades\Formatter;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\GroupDailySplit;
use Tobuli\History\Actions\GroupDriver;
use Tobuli\Reports\DeviceReport;
use Tobuli\Reports\RfidFormatter;

class IvmsDriversReport extends DeviceReport
{
    use RfidFormatter;

    const TYPE_ID = 88;

    protected $disableFields = ['devices', 'geofences', 'show_addresses', 'zones_instead'];
    protected $deviceless = true;

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
        return 'IVMS_DriverListReport_' . date('Ymd');
    }

    public function title()
    {
        return trans('front.ivms_driver_list');
    }

    protected function getActionsList()
    {
        return [
            Drivers::class,
            GroupDriver::class,
            GroupDailySplit::class,
        ];
    }

    protected function defaultMetas()
    {
        return [];
    }

    protected function generate()
    {
        $drivers = $this->user->drivers()->with('device')->get();

        /** @var UserDriver $driver */
        foreach ($drivers as $driver) {
            $this->items[] = [
                'driver_name' => $driver->name,
                'driver_rfid' => $this->formatRfid($driver->rfid),
                'object_owner' => ($driver->device && $this->user->can('view', $driver->device))
                    ? $driver->device->object_owner
                    : null,
                'date' => Formatter::time()->convert($driver->updated_at, 'm/d/Y H:i'),
            ];
        }
    }

    protected function toCSVData($file)
    {
        fputcsv($file, [
            'Driver Name',
            'Driver Employee ID',
            'Site Name / Transporter Name',
            'Date',
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