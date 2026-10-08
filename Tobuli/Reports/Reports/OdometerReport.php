<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\Odometer;
use Tobuli\Reports\DeviceHistoryReport;

class OdometerReport extends DeviceHistoryReport
{
    const TYPE_ID = 62;

    protected $disableFields = ['geofences', 'speed_limit', 'stops', 'show_addresses', 'zones_instead'];

    /** @var bool|int */
    private $extendStart = false;

    public function __construct()
    {
        parent::__construct();

        $this->formats[] = 'csv';
    }

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.odometer');
    }

    protected function getActionsList()
    {
        return [
            Duration::class,
            Distance::class,
            Odometer::class,
        ];
    }

    public function getInputParameters(): array
    {
        $params = parent::getInputParameters();

        $params[] = \Field::select('extend_period', trans('validation.attributes.extend_period_if_not_found'), 0)
            ->setOptions([
                0 => trans('global.no'),
                1 => trans('global.yes'),
            ]);

        return $params;
    }

    protected function generateDevice($device)
    {
        $this->extendStart = false;

        $data = $this->getDeviceHistoryData($device);

        if ($this->isEmptyResult($data) && !empty($this->parameters['extend_period'])) {
            $this->extendStart = 0;

            $data = $this->getDeviceHistoryData($device);
        }

        if ($this->isEmptyResult($data)) {
            return null;
        }

        $odometer_start = trans('front.not_available');
        $odometer_end   = trans('front.not_available');
        $odometer = $data['root']->stats()->has('odometer') ? $data['root']->stats()->get('odometer') : null;

        if ($odometer) {
            $startPosition = $data['root']->getStartPosition();
            if ($startPosition && isset($startPosition->odometer)) {
                $odometer->set($startPosition->odometer);
                $odometer_start = round($odometer->format());
            }

            $endPosition = $data['root']->getEndPosition();
            if ($endPosition && isset($endPosition->odometer)) {
                $odometer->set($endPosition->odometer);
                $odometer_end = round($odometer->format());
            }
        }

        if ($this->extendStart !== false && !is_numeric($odometer_end)) {
            $odometer_end = $odometer_start;
        }

        if (is_numeric($odometer_end) && is_numeric($odometer_start)) {
            $distance = $odometer_end - $odometer_start;
        } else {
            $distance = $data['root']->stats()->get('distance')->format();
        }

        return [
            'meta' => $this->getDeviceMeta($device) + $this->getHistoryMeta($data['root']),
            'totals' => [
                'distance' => $distance,
                'odometer_start' => $odometer_start,
                'odometer_end' => $odometer_end,
            ]
        ];
    }

    protected function toCSVData($file)
    {
        $headers = [];

        foreach ($this->metas() as $meta) {
            $headers[] = $meta['title'];
        }

        $headers[] = trans('front.start');
        $headers[] = trans('front.end');
        $headers[] = trans('global.distance');

        fputcsv($file, $headers);

        foreach ($this->getItems() as $item) {
            $values = [];

            foreach ($item['meta'] as $meta) {
                $values[] = $meta['value'];
            }

            if (isset($item['error'])) {
                array_push($values, $item['error'], '', '');
            } else {
                $values[] = $item['totals']['odometer_start'];
                $values[] = $item['totals']['odometer_end'];
                $values[] = $item['totals']['distance'];
            }

            fputcsv($file, $values);
        }
    }

    protected function extendStart()
    {
        return $this->extendStart;
    }
}