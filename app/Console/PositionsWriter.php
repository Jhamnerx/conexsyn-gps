<?php

namespace App\Console;

use App\Events\DeviceEngineChanged;
use App\Events\DevicePositionChanged;
use App\Events\PositionResultRetrieved;
use App\Services\Integrations\IntegrationPositionRecorder;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag as Bugsnag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceSensor;
use Tobuli\Entities\TraccarPosition as Position;
use Tobuli\Entities\UserDriver;
use Tobuli\Helpers\Alerts\Check\Checker;
use Tobuli\Helpers\LbsLocation\LbsManager;
use Tobuli\Helpers\LbsLocation\Service\LbsInterface;
use Tobuli\Services\EventWriteService;

class PositionsWriter
{
    const MIN_DISTANCE = 0.02;
    const PARKED_MIN_DURATION = 120;

    /**
     * @var Device
     */
    protected $device;

    protected $events = [];

    /**
     * @var Position[]
     */
    protected $positions = [];

    protected $position = null;

    protected $prevPosition = null;

    protected $drivers = [];

    protected $alertChecker = null;

    protected $debug;

    protected $beaconsPositionWriter;

    protected $max_speed = null;
    protected $min_time_gap = null;
    protected $prev_position_device_object = null;
    protected $apply_network_data = null;
    protected $overwrite_invalid = null;
    protected $resetDriverOnUnknownRfid = null;

    protected $eventWriteService = null;
    protected TaskStatusChanger $taskStatusChanger;
    protected ?LbsInterface $lbsService = null;
    protected ?IntegrationPositionRecorder $integrationRecorder = null;

    protected ?string $recalculationDate = null;
    protected float $odometerDistance = 0;
    protected array $virtualOdometers = [];

    public function __construct($device, $debug = false)
    {
        $this->device = $device;

        $this->debug = $debug;

        $this->beaconsPositionWriter = new PositionsBeaconsWriter($device, $debug);
        $this->stack = new PositionsStack();

        $this->device->load(['timezone']);

        $this->max_speed = $device->max_speed ?: config('tobuli.max_speed');
        $this->min_time_gap = config('tobuli.min_time_gap');
        $this->prev_position_device_object = config('tobuli.prev_position_device_object');
        $this->apply_network_data = config('tobuli.apply_network_data');
        $this->overwrite_invalid = config('tobuli.overwrite_invalid');
        $this->resetDriverOnUnknownRfid = settings('plugins.reset_driver_on_unknown_rfid.status');

        $this->eventWriteService = new EventWriteService();
        $this->taskStatusChanger = new TaskStatusChanger($device, $debug);
        $this->positionsIpLog = new PositionsIpLogger(config('tobuli.positions_ip_log'));

        $this->forwards = new PositionsForward($this->device, $this->debug);

        if ((bool)config('addon.lbs') && $this->device->lbs) {
            $this->setLbsService();
        }
    }

    protected function line($text = '')
    {
        if (! $this->debug)
            return;

        echo $text . PHP_EOL;
    }

    public function runList($imei)
    {
        $key = 'positions.' . $imei;

        if ($this->debug) {
            $this->line('IMEI: ' . $imei);
            $this->line('Keys: ' . $this->stack->oneCount($key));
            $this->line('Database ID: ' . $this->device->traccar->database_id);
        }

        $p = 0;
        $n = 0;
        $start = microtime(true);

        foreach ($this->stack->getKeyDataList($key) as $data) {
            $s = microtime(true);

            $data = $this->normalizeData($data);

            $n += microtime(true) - $s;

            if (! $data)
                continue;

            $this->proccess($data);
            $this->resetPrevPosition();

            $p += microtime(true) - $s;
        }

        if ($this->debug) {
            $this->line('Keys Process only ' . ($p));
            $this->line('Keys Normalize Time ' . ($n));
            $this->line('Keys Getting Time ' . (microtime(true) - $start - $p));
            $this->line('Keys Process Time ' . (microtime(true) - $start));
        }

        $this->write();
    }

    protected function normalizeData($data)
    {
        if (! empty($data['deviceId']))
            $data['imei'] = $data['deviceId'];

        if (! empty($data['uniqueId']))
            $data['imei'] = $data['uniqueId'];

        if (empty($data['imei']))
            return false;

        $data = array_merge([
            'altitude'  => 0,
            'course'    => null,
            'latitude'  => null,
            'longitude' => null,
            'speed'     => 0,
            'distance'  => 0,
            'valid'     => 1,
            'protocol'  => null,

            'ack'         => empty($data['fixTime']),
            'attributes'  => [],
            'server_time' => date('Y-m-d H:i:s'),
        ], $data);

        $data['speed'] = floatval($data['speed']) * 1.852;
        $data['altitude'] = floatval($data['altitude']);

        if ($data['ack']) {
            if (! empty($data['deviceTime'])) {
                $data['device_time'] = date('Y-m-d H:i:s', $data['deviceTime'] / 1000);
            } else {
                $data['device_time'] = null;
            }
        } else {
            $data['device_time'] = date('Y-m-d H:i:s', $data['fixTime'] / 1000);
        }

        if (is_null($data['device_time'])) {
            $data['device_time'] = $this->device->getDeviceTime() ?? date('Y-m-d H:i:s');
        }

        if (in_array($data['protocol'], ['gt06', 'gt062', 'h02'])) {
            $current = strtotime($data['device_time']);

            //2007-01-01
            if ($current < 1167609600)
                $data['device_time'] = date('Y-m-d H:i:s', $current + 619315200);
        }

        $data['time'] = $data['device_time'];

        if ($this->device->timezone) {
            $data['time'] = date('Y-m-d H:i:s', strtotime($this->device->timezone->zone, strtotime($data['time'])));
        }

        if ($data['time'] == $this->device->getTime() && time() - strtotime($this->device->getServerTime()) > 60)
            $data['ack'] = true;

        if ($this->isSkipableOsmand($data)) {
            $this->line('Osmand skipable');
            return false;
        }

        //Outdated check for 90 days
        if (time() - strtotime($data['time']) > 7776000) {
            $this->line('Bad date - outdated: ' . $data['time']);
            return false;
        }

        //Future check for 1 day
        if (strtotime($data['time']) - time() > 86400) {
            $this->line('Bad date - future: ' . $data['time']);
            return false;
        }

        if ($overwrite = $this->getProtocolConfig($data['protocol'], 'overwrite'))
            $data['protocol'] = $overwrite;

        if ($this->getProtocolConfig($data['protocol'], 'bypass_invalid'))
            $data['valid'] = 1;

        $this->checkLbs($data);

        $parameters = [];
        foreach ((is_array($data['attributes']) ? $data['attributes'] : []) as $key => $value) {
            $key = preg_replace('/[^a-zA-Z0-9_-]/s', '', $key);
            $key = strtolower($key);
            $parameters[$key] = is_string($value)
                ? str_replace('&', '', $value)
                : $value;
        }
        $parameters['valid'] = $data['valid'];
        $parameters[Position::VIRTUAL_ENGINE_HOURS_KEY] = 0;

        if ($this->apply_network_data && $networkData = Arr::get($data, 'network.cellTowers.0')) {
            $parameters = array_merge($parameters, $networkData);
        }

        $gsmSignal = Arr::get($data, 'network.cellTowers.0.signalStrength');
        if (!is_null($gsmSignal))
            $parameters['gsmsignal'] = $gsmSignal;

        $accuracy = Arr::get($data, 'accuracy');
        if (!is_null($accuracy))
            $parameters['accuracy'] = $accuracy;

        if ($this->getProtocolConfig($data['protocol'], 'mergeable') && $prevPosition = $this->getPrevPosition($data['time'])) {
            $excepts = $this->getProtocolConfig($data['protocol'], 'expects') ?? [];
            $excepts = array_merge(['alarm', 'result', 'sat'], $excepts);

            $prevParameters = Arr::except($prevPosition->parameters, $excepts);
            $parameters = array_merge($prevParameters, $parameters);
        }

        if (! empty($parameters['ip'])) {
            $this->positionsIpLog->add($data['imei'], $parameters['ip']);
            unset($parameters['ip']);
        }

        $data['parameters'] = $parameters;

        $params = empty($this->device->parameters) ? [] : json_decode($this->device->parameters, true);
        $params = empty($params) ? [] : array_flip($params);
        $params = array_map(function ($val) {
            return strtolower($val);
        }, $params);

        $merge = array_keys(array_merge($parameters, $params));
        if (count($params) != count($merge)) {
            $this->device->parameters = json_encode($merge);
        }

        return $data;
    }

    protected function checkLbs(array &$data): void
    {
        if (!$this->lbsService) {
            return;
        }

        if (empty($data['network'])) {
            return;
        }

        try {
            $response = $this->lbsService->getLocation($data['network']);
        } catch (\Exception $e) {
            $this->line('LBS get location: ' . $e->getMessage());

            return;
        }

        $data['latitude'] = $response['lat'];
        $data['longitude'] = $response['lng'];
        $data['accuracy'] = $response['accuracy'];
        $data['valid'] = 1;
    }

    protected function isHistory($time = null)
    {
        if (is_null($time) && $this->position)
            $time = $this->position->time;

        return strtotime($time) < strtotime($this->device->getTime());
    }

    protected function isChanged($current, $previous)
    {
        if (empty($previous))
            return true;

        if (round($current->speed, 1) != round($previous->speed, 1))
            return true;

        if ($current->distance > self::MIN_DISTANCE)
            return true;

        if ((strtotime($current->time) - strtotime($previous->time)) >= $this->min_time_gap)
            return true;

        $escape = [
            'distance',
            'totaldistance',
            'sequence',
            'power',
            'index',
            'axisx',
            'axisy',
            'axisz',
            Position::VIRTUAL_ENGINE_HOURS_KEY
        ];

        $currentParameters  = Arr::except($current->parameters, $escape);
        $previousParameters = Arr::except($previous->parameters, $escape);

        if ($currentParameters != $previousParameters)
            return true;

        return false;
    }

    protected function getPrevPosition($time = null)
    {
        if (! is_null($this->prevPosition))
            return $this->prevPosition;

        if (is_null($time) && $this->position)
            $time = $this->position->time;

        if (empty($time))
            return $this->getLastPosition();

        $_time = strtotime($time);

        if ($this->positions) {
            foreach ($this->positions as $position) {
                $positionTime = strtotime($position->time);

                if ($positionTime > $_time)
                    break;

                $this->prevPosition = $position;

                if ($positionTime < $_time)
                    continue;

                break;
            }
        }

        if ($this->prevPosition && $this->isHistory($time)) {
            $prevTime = strtotime($this->prevPosition->time);
            $timeDiff = $_time - $prevTime;

            if ($timeDiff > 300 || $timeDiff < 0) {
                $this->line('Getting history prev with time ' . $time);

                $storedPosition = $this->getPrevHistoryPosition($time);

                if ($storedPosition && strtotime($storedPosition->time) > $prevTime)
                    $this->prevPosition = $storedPosition;
            }
        }

        if ($this->prev_position_device_object && is_null($this->prevPosition) && ! $this->isHistory($time))
            $this->prevPosition = $this->getLastPosition();

        if (is_null($this->prevPosition)) {
            $this->line('Getting history prev with null');

            $this->prevPosition = $this->getPrevHistoryPosition($time);
        }

        return $this->prevPosition;
    }

    protected function getPrevValidPosition($time = null)
    {
        if (is_null($time) && $this->position)
            $time = $this->position->time;

        $prevPosition = $this->getPrevPosition($time);

        if ($prevPosition && $prevPosition->isValid())
            return $prevPosition;

        if (!$this->isHistory() && $current = $this->getLastPosition()) {

            if (filter_var($current->getParameter('valid'), FILTER_VALIDATE_BOOLEAN))
                return $current;

            $distance = getDistance(
                $this->position->latitude,
                $this->position->longitude,
                $current->latitude,
                $current->longitude
            );

            if ($distance < 0.1)
                return $current;
        }

        if (empty($this->device->traccar->lastValidLatitude) && empty($this->device->traccar->lastValidLongitude))
            return null;

        $this->line('Getting previous valid');

        return $this->getPrevHistoryPosition($time, true);
    }

    protected function getPrevHistoryPosition($time, $onlyValid = false)
    {
        try {
            return $this->device->positions()
                ->orderliness()
                ->where('time', '<=', $time)
                ->when($onlyValid, function ($query) {
                    $query->where('valid', '>', 0);
                })
                ->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getLastPosition()
    {
        if (! $this->device->traccar)
            return null;

        if (empty($this->device->traccar->lastValidLatitude) && empty($this->device->traccar->lastValidLongitude))
            return null;

        $position = new Position([
            'server_time' => $this->device->traccar->server_time,
            'device_time' => $this->device->traccar->device_time,
            'time'        => $this->device->traccar->time,
            'latitude'    => $this->device->traccar->lastValidLatitude,
            'longitude'   => $this->device->traccar->lastValidLongitude,
            'speed'       => $this->device->traccar->speed,
            'course'      => $this->device->traccar->course,
            'altitude'    => $this->device->traccar->altitude,
            'protocol'    => $this->device->traccar->protocol,
            'other'       => $this->device->traccar->other,
        ]);

        $position->id    = $this->device->traccar->latestPosition_id;
        $position->valid = 1;
        //$position->valid = filter_var($position->getParameter('valid'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        return $position;
    }

    protected function proccess($data)
    {
        if ($result = $data['parameters']['result'] ?? null) {
            event(new PositionResultRetrieved($this->device, is_scalar($result) ? $result : json_encode($result)));
        }

        if (! $this->device->traccar)
            return;

        $this->position = new Position($data);
        $this->position->ack = $data['ack'];

        $prevPosition = $this->getPrevPosition();
        $this->position->setParameter('totaldistance', $prevPosition ? $prevPosition->getParameter('totaldistance', 0) : 0);
        $this->position->setParameter('valid', $this->position->isValid() ? 'true' : 'false');
        $this->position->setParameter(Position::VIRTUAL_ENGINE_HOURS_KEY, $this->getVirtualEngineHours());

        if ($this->position->ack && $this->isHistory($this->position->time))
            return;

        $lastValidPosition = $this->getPrevValidPosition();

        if (!$this->isValidPositionLatLng($this->position)) {
            if ($lastValidPosition) {
                $this->position->latitude = $lastValidPosition->latitude;
                $this->position->longitude = $lastValidPosition->longitude;
            } else {
                $this->position->valid = 0;
            }
        }

        if ($this->position->speed > $this->max_speed)
            $this->position->speed = $lastValidPosition ? $lastValidPosition->speed : $this->max_speed;

        //if (is_null($this->position->course) && $lastValidPosition)
        //    $this->position->course = $lastValidPosition->course;

        if (empty($this->position->course) && $lastValidPosition)
            $this->position->course = getCourse(
                $this->position->latitude,
                $this->position->longitude,
                $lastValidPosition->latitude,
                $lastValidPosition->longitude
            );


        if ($this->position->valid && $lastValidPosition) {
            $this->position->distance = getDistance(
                $this->position->latitude,
                $this->position->longitude,
                $lastValidPosition->latitude,
                $lastValidPosition->longitude
            );

            $skipProtocols = ['upro'];

            if (
                $this->device->valid_by_avg_speed &&
                ! in_array($this->position->protocol, $skipProtocols) &&
                $this->position->distance > 10 &&
                $this->getLastPosition() && $this->getLastPosition()->id > 50
            ) {
                $time = strtotime($this->position->time) - strtotime($lastValidPosition->time);

                if ($time > 0) {
                    $avg_speed = $this->position->distance / ($time / 3600);

                    if ($avg_speed > $this->max_speed) {
                        $this->position->valid = 0;
                    }
                } else {
                    $this->position->valid = 0;
                }
            }
        }

        //tmp
        if (! $this->position->isValid()) {
            $this->position->distance = 0;

            if ($this->overwrite_invalid && $lastValidPosition) {
                $this->position->latitude = $lastValidPosition->latitude;
                $this->position->longitude = $lastValidPosition->longitude;
            }
        }

        $distance = round($this->position->distance * 1000, 2);

        $this->position->setParameter('distance', $distance);


        $totalDistance = $lastValidPosition ? $lastValidPosition->getParameter('totaldistance', 0) : 0;

        if ($this->position->isValid()) {
            $totalDistance += $distance;
            $this->odometerDistance += $this->position->distance;
        }

        $this->position->setParameter('totaldistance', $totalDistance);
        $this->position->setParameter('valid', $this->position->isValid() ? 'true' : 'false');
        $this->position->setParameter(Position::VIRTUAL_ENGINE_HOURS_KEY, $this->getVirtualEngineHours());

        $this->setSensors();

        if ($this->checkableAlerts()) {
            $this->alerts();
        }

        if ($this->events || $this->isChanged($this->position, $this->getPrevPosition())) {
            $this->addPosition($this->position);

            if ($this->isHistory()) {
                $this->setHistoryRecalculation($this->position);
            }
        }

        // MUST ALWAYS BE CALLED AFTER OTHER PARAMETERS ARE SET!!
        $this->setTraccarDeviceMovedAt($this->position);
        $this->setTraccarDeviceStopedAt($this->position);
        $this->setTraccarDeviceMoveBeginAt($this->position);
        $this->setTraccarDeviceStopBeginAt($this->position);
        $this->setTraccarDeviceParkEndAt($this->position);
        $this->setTraccarDeviceEngineAt($this->position);

        if (!$this->isHistory()) {
            if ($this->position->isValid()) {
                $this->setTraccarDevicePosition($this->position);
            }

            $this->setTraccarDeviceData($this->position);
            $this->taskStatusChanger->process($this->position);
        }

        $this->setCurrentDriver($this->position);

        $this->forwards->process($this->position);

        if ($this->events || count($this->positions) > 100) {
            $this->write();
        }

        $this->recordIntegrations();
    }

    /**
     * Encola la posición para SUTRAN / MININTER si el dispositivo lo tiene activado.
     * El recorder captura sus propias excepciones: nunca frena la escritura de posiciones.
     */
    protected function recordIntegrations()
    {
        if (! $this->device->mtc && ! $this->device->mininter)
            return;

        $this->integrationRecorder()->record($this->device, $this->position, $this->getPrevPosition());
    }

    protected function integrationRecorder(): IntegrationPositionRecorder
    {
        if (is_null($this->integrationRecorder))
            $this->integrationRecorder = new IntegrationPositionRecorder();

        return $this->integrationRecorder;
    }

    protected function getEngineStatus($position)
    {
        if (! isset($this->engine_sensor))
            $this->engine_sensor = $this->device->getEngineSensor();

        if ($this->engine_sensor)
            return $this->engine_sensor->getValue($position->other, false);

        return $position->speed > 0;
    }

    protected function getVirtualEngineHours()
    {
        $prevPosition = $this->getPrevPosition();

        if (!$prevPosition)
            return 0;

        $engineHours = $prevPosition->getVirtualEngineHours();

        $duration = strtotime($this->position->time) - strtotime($prevPosition->time);

        if ($duration < 1)
            return $engineHours;

        //skip if duration between positions is more then 5 mins
        $timeout = max(5, settings('main_settings.default_object_online_timeout')) * 60;
        if ($duration > $timeout)
            return $engineHours;

        if (! isset($this->engine_hours_sensor))
            $this->engine_hours_sensor = $this->device->getEngineHoursSensor();

        if ($this->engine_hours_sensor && $this->engine_hours_sensor->shown_value_by == 'logical') {
            $prevEngineStatus = $this->engine_hours_sensor->getValueParameters($prevPosition);
        } elseif ($this->isHistory($this->position->time)) {
            $prevEngineStatus = $this->getEngineStatus($prevPosition);
        } else {
            $prevEngineStatus = $this->device->traccar->engine_on_at > $this->device->traccar->engine_off_at;
        }

        if (! $prevEngineStatus)
            return $engineHours;

        return $engineHours + $duration;
    }

    protected function cacheAlerts($alerts)
    {
        $cacheCount = 0;
        /** @var \Tobuli\Entities\Alert $alert */
        foreach ($alerts as $alert) {
            $cached = Cache::store('array')->get("alert.{$alert->id}");

            if (!$cached)
                continue;

            if ($alert->updated_at > $cached->updated_at)
                continue;

            $cacheCount++;

            $pivot = $alert->pivot;
            $alert->setRelations($cached->getRelations());
            $alert->setRelation('pivot', $pivot);
        }

        $this->line("Cache count: $cacheCount");

        $alerts->loadMissing(['user', 'geofences', 'drivers', 'events_custom', 'zones']);

        foreach ($alerts as $alert) {
            Cache::store('array')->set("alert.{$alert->id}", $alert);
        }
    }

    protected function alerts()
    {
        if (is_null($this->alertChecker)) {
            $start = microtime(true);

            $alerts = $this->device
                ->alerts()
                ->withPivot('started_at', 'fired_at', 'silenced_at', 'active_from', 'active_to')
                ->checkByPosition()
                ->enabled()
                ->active()
                ->get();

            $this->cacheAlerts($alerts);

            if ($count = count($alerts)) {
                $this->alertChecker = new Checker($this->device, $alerts);

                $this->line('Alerts: ' . count($alerts) . ' ' . $alerts->implode('type', ','));
            } else {
                $this->alertChecker = false;
            }

            $end = microtime(true);
            $this->line('Alerts getting time ' . round($end - $start, 5));
        }

        if ($this->alertChecker === false)
            return;

        $start = microtime(true);

        // reset device with new proterties as lat, lng and etc.
        $this->alertChecker->setDevice($this->device);

        $this->events = $this->alertChecker->check($this->position, $this->getPrevPosition());

        $end = microtime(true);
        $this->line('Alerts check time ' . round($end - $start, 5));
    }

    protected function checkableAlerts()
    {
        if (!$this->device->traccar)
            return false;

        if ($this->device->isExpired())
            return false;

        $timePosition = strtotime($this->position->time);
        $timeDevice = strtotime($this->device->traccar->getOriginal('time'));

        return $timePosition >= $timeDevice;
    }

    protected function setSensors()
    {
        $this->virtualOdometers = [];
        $sensorsValues = [];

        if ($this->device->sensors) {
            foreach ($this->device->sensors as &$sensor) {
                if ($sensor->type == 'odometer' && $sensor->shown_value_by === 'virtual_odometer') {
                    $this->virtualOdometers[] = $sensor->id;
                }

                $sensorValue = null;

                if ($sensor->isCounter()) {
                    if ($sensorValue = $sensor->getValueParameters($this->position))
                        $sensor->setCounter($sensorValue);

                    $sensorsValues[] = [
                        'id'  => $sensor->id,
                        'val' => $sensor->getCounter()
                    ];

                    continue;
                }

                if ($sensor->type == 'engine_hours' && $sensor->shown_value_by == 'logical') {
                    $logicalValue = $sensor->getValueParameters($this->position);

                    if (is_null($logicalValue) && $prevPosition = $this->getPrevPosition()) {
                        $logicalValue = $sensor->getValueParameters($prevPosition);
                    }

                    if (!is_null($logicalValue)) {
                        $sensorsValues[] = [
                            'id' => $sensor->id,
                            'val' => $logicalValue
                        ];
                    }

                    $engineHoursValue = $this->position->getParameter(Position::VIRTUAL_ENGINE_HOURS_KEY, 0) / 3600;

                    $sensor->setValue(round($engineHoursValue, 2), $this->position);

                    continue;
                }

                if ($sensor->isUpdatable()) {
                    $sensorValue = $sensor->getValue($this->position->other, false);
                    $sensor->setValue($sensorValue, $this->position);
                }

                if (! $sensor->isPositionValue())
                    continue;

                if ($this->isHistory()) {
                    $prevSensorValue = null;

                    if ($prevPosition = $this->getPrevPosition()) {
                        $prevSensorValue = $sensor->getValue($prevPosition->other, false);
                    }

                    $sensorValue = $sensor->getValue($this->position->other, false) ?? $prevSensorValue;
                } elseif (is_null($sensorValue)) {
                    $sensorValue = $sensor->getValue($this->position->other);
                }

                if (! is_null($sensorValue)) {
                    $sensorsValues[] = [
                        'id'  => $sensor->id,
                        'val' => $sensorValue
                    ];
                }
            }
        }

        if ($sensorsValues)
            $this->position->sensors_values = json_encode($sensorsValues);
    }

    protected function getRFIDs($position)
    {
        if (! isset($this->rfid_sensor))
            $this->rfid_sensor = $this->device->getRfidSensor();

        if ($this->rfid_sensor) {
            $rfid = $this->rfid_sensor->getValuePosition($position);
            return $rfid ? [$rfid] : null;
        }

        return $position->getRfids();
    }

    protected function setLbsService(): void
    {
        $settings = settings('main_settings.lbs');

        if (empty($settings['provider'])) {
            $this->lbsService = null;
            return;
        }

        try {
            $this->lbsService = LbsManager::createService($settings['provider'], $settings);
        } catch (\Exception $e) {
            $this->line('LBS init: ' . $e->getMessage());

            $this->lbsService = null;
        }
    }

    protected function setCurrentDriver($position): void
    {
        $rfids = $this->getRfids($position);

        if (!$rfids) {
            return;
        }

        $hash = md5(json_encode($rfids));

        if (!array_key_exists($hash, $this->drivers)) {
            $this->drivers[$hash] = UserDriver::whereIn('rfid', $rfids)->first();
        }

        $driver = $this->drivers[$hash];

        if ($driver) {
            if ($this->device->current_driver_id == $driver->id) {
                return;
            }

            $this->device->current_driver_id = $driver->id;
            $this->device->current_driver_rfid = $driver->rfid;

            $driver->changeDevice($this->device, null, $position->time, true);

            return;
        }

        if (!$this->resetDriverOnUnknownRfid) {
            return;
        }

        $rfid = Arr::first($rfids);

        if (!$this->device->current_driver_id && $this->device->current_driver_rfid == $rfid) {
            return;
        }

        $this->device->changeDriver(null, $rfid);
    }

    protected function setTraccarDevicePosition($position)
    {
        $this->device->traccar->lastValidLatitude = $position->latitude;
        $this->device->traccar->lastValidLongitude = $position->longitude;
        $this->device->traccar->altitude = $position->altitude;
        //$this->device->traccar->speed = $position->speed;
        $this->device->traccar->course = $position->course;


        $latest_positions = $this->device->traccar->latest_positions ? explode(';', $this->device->traccar->latest_positions) : [];

        if (! $latest_positions) {
            array_unshift($latest_positions, $position->latitude . '/' . $position->longitude);
        } else {
            list($lat, $lng) = explode('/', reset($latest_positions));

            $distance = getDistance($position->latitude, $position->longitude, $lat, $lng);

            if ($distance > self::MIN_DISTANCE)
                array_unshift($latest_positions, $position->latitude . '/' . $position->longitude);
        }

        $this->device->traccar->latest_positions = implode(';', array_slice($latest_positions, 0, 15));
    }

    protected function setHistoryRecalculation($position): void
    {
        if (!$this->recalculationDate || $position->time < $this->recalculationDate) {
            $this->recalculationDate = $position->time;
        }
    }

    protected function registerHistoryRecalculation(): void
    {
        if (!$this->recalculationDate) {
            return;
        }

        \DB::statement("
            INSERT INTO history_recalculations VALUES ({$this->device->id}, ?)
                ON DUPLICATE KEY 
                    UPDATE date = LEAST(date, VALUES(date))", [$this->recalculationDate]);

        $this->recalculationDate = null;
    }

    protected function updateOdometer(): void
    {
        if (!$this->virtualOdometers) {
            return;
        }

        if (!$this->odometerDistance) {
            return;
        }

        DeviceSensor::whereIn('id', $this->virtualOdometers)->update([
            'value' => \DB::raw("CAST(value AS DOUBLE) + $this->odometerDistance"),
        ]);

        $this->odometerDistance = 0;
    }

    protected function setTraccarDeviceData($position)
    {
        $this->device->traccar->time = $position->time;
        $this->device->traccar->server_time = $position->server_time;
        $this->device->traccar->updated_at = $position->server_time;
        $this->device->traccar->device_time = $position->device_time;
        $this->device->traccar->other = $position->other;
        $this->device->traccar->protocol = $position->protocol;
        $this->device->traccar->speed = $position->speed;

        if ($position->ack) {
            $this->device->traccar->speed = 0;
            $this->device->traccar->ack_time = $position->server_time;
        }
    }

    protected function setTraccarDeviceMovedAt($position)
    {
        if (!$this->isDeviceMovingAtPosition($position))
            return;

        if ($this->device->traccar->moved_at > $position->time)
            return;

        $this->device->traccar->moved_at = $position->time;
    }

    protected function setTraccarDeviceStopedAt($position)
    {
        if ($this->isDeviceMovingAtPosition($position))
            return;

        if ($this->device->traccar->stoped_at > $position->time)
            return;

        $this->device->traccar->stoped_at = $position->time;
    }

    protected function setTraccarDeviceParkEndAt($position)
    {
        if ($position->time < $this->device->traccar->stop_begin_at) {
            return;
        }

        if ($this->device->traccar->stoped_at > $this->device->traccar->moved_at) {
            return;
        }

        if ($this->device->traccar->parked_end_at > $this->device->traccar->stoped_at) {
            return;
        }

        // moving started earlier
        if (!($this->device->traccar->move_at > $this->device->traccar->move_begin_at)) {
            return;
        }

        $stopDuration = strtotime($position->time) - strtotime($this->device->traccar->stop_begin_at);

        if ($stopDuration >= self::PARKED_MIN_DURATION) {
            $this->device->traccar->parked_end_at = $position->time;

            $this->addDeviceTrip($position);
        }
    }

    protected function setTraccarDeviceMoveBeginAt($position)
    {
        //is already moving
        if ($this->device->traccar->move_begin_at > $this->device->traccar->stoped_at) {
            return;
        }

        //is position history
        if ($this->device->traccar->move_begin_at > $position->time) {
            return;
        }

        //is stoped now
        if (!$this->isDeviceMovingAtPosition($position))
            return;

        $this->device->traccar->move_begin_at = $position->time;
    }

    protected function setTraccarDeviceStopBeginAt($position)
    {
        //is already stoped
        if ($this->device->traccar->stop_begin_at > $this->device->traccar->moved_at) {
            return;
        }

        //is position history
        if ($this->device->traccar->stop_begin_at > $position->time) {
            return;
        }

        //is moving now
        if ($this->isDeviceMovingAtPosition($position)) {
            return;
        }

        $this->device->traccar->stop_begin_at = $position->time;
    }

    protected function setTraccarDeviceEngineAt($position)
    {
        $engineOn  = strtotime($this->device->traccar->engine_on_at);
        $engineOff = strtotime($this->device->traccar->engine_off_at);
        $time      = strtotime($position->time);

        // is history
        if ($time < $engineOn && $time < $engineOff)
            return;

        $status = $this->getEngineStatus($position);

        if (is_null($status))
            return;

        if ($status && $time > $engineOn) {
            $this->device->traccar->engine_on_at = $position->time;
        }

        if (!$status && $time > $engineOff) {
            $this->device->traccar->engine_off_at = $position->time;
        }

        if ($this->device->traccar->engine_on_at > $this->device->traccar->engine_off_at != $engineOn > $engineOff) {
            $this->device->traccar->engine_changed_at = $position->time;
        }
    }

    protected function wasDeviceMoving(): bool
    {
        return $this->device->traccar->moved_at > $this->device->traccar->stoped_at;
    }

    protected function isDeviceMovingAtPosition($position): bool
    {
        if (empty($position))
            return false;

        return $position->speed >= $this->device->min_moving_speed;
    }

    protected function addDeviceTrip($position): void
    {
        DB::table('device_trips')->insert([
            'device_id' => $this->device->id,
            'date'      => $position->time,
        ]);
    }

    protected function addPosition($position)
    {
        $this->positions[] = $position;

        $this->positions = Arr::sort($this->positions, function ($value) {
            return $value->time;
        });

        $this->resetPrevPosition();
    }

    protected function updatePosition($position)
    {
        $this->line('Updating last position...');

        // skip if new position
        if (! $position->id)
            return;

        // skip if position already in list
        if (array_filter($this->positions, function ($value) use ($position) {
            return $position->id == $value->id;
        }))
            return;

        $this->addPosition($position);
    }

    protected function resetPrevPosition()
    {
        $this->prevPosition = null;
    }

    protected function write()
    {
        $this->line('Writing:');
        $this->line('Positions ' . count($this->positions));
        $this->line('Events ' . count($this->events));

        $start = microtime(true);

        $this->beaconsPositionWriter->write($this->positions);
        $this->writePositions();
        $this->writeEvents();

        $this->registerHistoryRecalculation();
        $this->updateOdometer();


        foreach ($this->device->sensors as $sensor) {
            $sensor->save();
        }

        if ($this->device->traccar) {
            $positionChanged = $this->device->traccar->isDirty(['lastValidLatitude', 'lastValidLongitude']);
            $engineChanged = $this->device->traccar->isDirty(['engine_changed_at']);

            $this->device->traccar->save();

            if ($positionChanged)
                event(new DevicePositionChanged($this->device));

            if ($engineChanged)
                event(new DeviceEngineChanged($this->device));
        }

        $this->device->save();

        $end = microtime(true);

        $this->line('Write time ' . ($end - $start));

        $this->forwards->send();
    }

    protected function writePositions()
    {
        if (! $this->positions)
            return;

        $data = [];

        foreach ($this->positions as $position) {
            if ($position->id) {
                $this->line('Saving updated position...');
                $position->save();
                continue;
            }

            $attributes = $position->attributesToArray();

            if ($position->getFillable()) {
                $attributes = array_intersect_key($attributes, array_flip($position->getFillable()));
            }

            if (empty($attributes['sensors_values'])) {
                $attributes['sensors_values'] = null;
            } elseif (is_array($attributes['sensors_values'])) {
                $attributes['sensors_values'] = json_encode($attributes['sensors_values']);
            }

            $data[] = $attributes;
        }

        $this->positions = [];

        $count = count($data);

        if (! $count)
            return;

        try {
            $this->writePositionData($data, $count > 1);
        } catch (\Exception $e) {
            if ($this->debug) {
                $this->line('Error positions write: ' . $e->getMessage());
            }

            if ($e->getCode() == '42S02') {
                $this->device->createPositionsTable();
                $this->writePositionData($data, $count > 1);
            } else {
                Bugsnag::notifyException($e);
            }
        }
    }

    protected function writePositionData($data, $multi)
    {
        if ($multi) {
            $this->device->positions()->insert($data);
            $lastPosition = $this->device->positions()->orderliness()->first();
            $this->device->traccar->latestPosition_id = $lastPosition->id;
        } else {
            $position = $this->device->positions()->create($data[0]);

            if (! $this->isHistory())
                $this->device->traccar->latestPosition_id = $position->id;
        }
    }

    protected function writeEvents()
    {
        if (! $this->events)
            return;

        $insertedPosition = $this->device->positions()->orderBy('time', 'desc')->first();

        if (! $insertedPosition) {
            $this->events = [];
            return;
        }

        $this->events = array_map(function ($event) use ($insertedPosition) {
            $event->position_id = $insertedPosition->id;
            return $event;
        }, $this->events);

        $this->eventWriteService->write($this->events);

        $this->events = [];
    }

    protected function getProtocolConfig($protocol, $key)
    {
        $config = \Illuminate\Support\Facades\Cache::store('array')->rememberForever("protocol.$protocol", function () use ($protocol) {
            //get parent protocol
            $parts = explode('-', $protocol, 2);

            return settings("protocols.{$parts[0]}");
        });

        if (empty($config))
            return null;

        return Arr::get($config, $key);
    }

    protected function isSkipableOsmand($data)
    {
        if (!config('addon.device_tracker_app_login'))
            return false;

        if ($data['protocol'] != 'osmand')
            return false;

        $protocol = $this->device->traccar->protocol ?? null;

        if (is_null($protocol))
            return false;

        if ($protocol == 'osmand')
            return false;

        return true;
    }

    protected function isValidPositionLatLng($position)
    {
        if (empty($position->latitude) && empty($position->longitude))
            return false;

        if ($position->latitude < -90)
            return false;

        if ($position->latitude > 90)
            return false;

        if ($position->longitude < -180)
            return false;

        if ($position->longitude > 180)
            return false;

        return true;
    }
}
