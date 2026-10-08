<?php namespace Tobuli\Reports\Reports;

use Formatter;
use Illuminate\Database\QueryException;
use Tobuli\Reports\DeviceReport;
use Tobuli\Services\DeviceAnonymizerService;

class ObjectHistoryReport extends DeviceReport
{
    const TYPE_ID = 25;

    protected $validation = ['devices' => 'same_protocol'];

    /** @var DeviceAnonymizerService  */
    protected $anonymizer;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.object_history');
    }

    protected $excludedFields = [
        'latitude', 'longitude', 'altitude', 'totaldistance',
        'in1', 'in2', 'in3', 'out1', 'out2',
        'status', 'index', 'sequence', 'distance', 'motion',
        'valid', 'enginehours', 'accuracy', 'alarm type', 'versionfw', 'sat'
    ];

    protected function processPosition($position, & $parameters, $sensors)
    {
        foreach($position->parameters as $key => $value)
        {
            if (empty($key))
                continue;

            if (in_array($key, $parameters))
                continue;

            if (in_array(strtolower($key), $this->excludedFields))
                continue;

            $parameters[] = $key;
        }

        // Filter out excluded fields from parameters
        $filteredParameters = array_filter($position->parameters, function($value, $key) {
            return !in_array(strtolower($key), $this->excludedFields);
        }, ARRAY_FILTER_USE_BOTH);

        return [
            'server_time'=> Formatter::time()->human($position->server_time),
            'time'       => Formatter::time()->human($position->time),
            'speed'      => Formatter::speed()->human($position->speed),
            'location'   => $this->getLocation($position, $this->getAddress($position)),
            'parameters' => $filteredParameters,
            'sensors'    => $sensors->mapWithKeys(function($sensor) use ($position) {
                return [$sensor->id => $sensor->getValueFormated($position, false)];
            })
        ];
    }

    protected function generateDevice($device)
    {
        $parameters = [];
        $rows = [];
        $sensors = $device->sensors->filter(function($sensor){
            return $sensor['add_to_history'];
        });

        $this->anonymizer = new DeviceAnonymizerService($device);

        try {
            $device->positions()
                ->orderliness('asc')
                ->whereBetween('time', [$this->date_from, $this->date_to])
                ->chunk(2000,
                    function ($positions) use (& $rows, & $parameters, $sensors) {
                        foreach ($positions as $position) {
                            $rows[] = $this->processPosition($position, $parameters, $sensors);
                        }
                    });
        } catch (QueryException $e) {}

        if (empty($rows))
            return [
                'meta'  => $this->getDeviceMeta($device),
                'error' => trans('front.nothing_found_request'),
                'parameters' => $parameters,
                'sensors'    => $sensors
            ];

        return [
            'meta'       => $this->getDeviceMeta($device),
            'table'      => [
                'rows' => $rows,
            ],
            'parameters' => $parameters,
            'sensors'    => $sensors
        ];
    }
}