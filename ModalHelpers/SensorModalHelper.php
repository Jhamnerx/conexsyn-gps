<?php namespace ModalHelpers;

use App\Exceptions\ResourseNotFoundException;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\DeviceSensorRepo;
use CustomFacades\Repositories\EventCustomRepo;
use CustomFacades\Validators\SensorFormValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tobuli\Entities\DeviceSensor;
use Tobuli\Entities\SensorGroupSensor;
use Tobuli\Entities\SensorIcon;
use Tobuli\Entities\TraccarPosition;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\SetFlag;
use Tobuli\Sensors\Extractions\BitCut;
use Tobuli\Sensors\Extractions\Mapping;
use Tobuli\Sensors\Sensor;
use Tobuli\Sensors\SensorsManager;
use Tobuli\Sensors\Types\Counter;
use Tobuli\Sensors\Types\EngineHours;
use Tobuli\Sensors\Types\Odometer;
use Tobuli\Services\ConditionService;

class SensorModalHelper extends ModalHelper {

    /**
     * @var SensorsManager
     */
    protected $sensorsManager;

    public function __construct()
    {
        parent::__construct();

        $this->sensorsManager = new SensorsManager();
    }

    public function paginated($device_id)
    {
        $sensors = DeviceSensorRepo::searchAndPaginate(['filter' => ['device_id' => $device_id]], 'id', 'desc', 10);

        $sensors->getCollection()->transform(function ($sensor)
        {
            $sensor->setAppends(['type_title']);
            return $sensor;
        });

        if ($this->api) {
            $sensors = $sensors->toArray();
            $sensors['url'] = route('api.get_sensors');
        }

        return $sensors;
    }

    public function createData($device_id)
    {
        $sensors = $this->sensorsManager->getEnabledListTitles();

        $types = $this->sensorsManager->getEnabledList()->map(function($sensor) {
            return [
                'type'     => $sensor->getType(),
                'shown_by' => $sensor->getShowTypes(),
                'inputs'   => $sensor->getInputs(),
            ];
        });

        if (!is_null($device_id)) {
            $device = DeviceRepo::find($device_id);
            $params = json_decode($device->parameters, true) ?? [];
            sort($params);
            $parameters = array_combine($params, $params);
        }
        else {
            $parameters = null;
        }

        return compact('sensors', 'types', 'device_id', 'parameters');
    }

    public function create() {
        $this->validate($this->data);

        $update = $this->formatInput($this->data);
        $item = DeviceSensor::create($update);

        $this->setValue($item, $update);

        return ['status' => 1];
    }

    public function editData() {
        if (array_key_exists('sensor_id', $this->data))
            $sensor_id = $this->data['sensor_id'];
        else
            $sensor_id = request()->route('sensors');
        
        $item = DeviceSensorRepo::find($sensor_id);

        if (empty($item))
            throw new ResourseNotFoundException('front.sensor');

        $device = DeviceRepo::find($item->device_id);

        $this->checkException('devices', 'edit', $device);

        $data = $this->createData($item->device_id);

        $data['item'] = $this->itemForm($item);

        return $data;
    }

    public function edit() {
        if (array_key_exists('sensor_id', $this->data))
            $sensor_id = $this->data['sensor_id'];
        else
            $sensor_id = $this->data['id'];

        $item = DeviceSensorRepo::find($sensor_id);

        if (empty($item))
            throw new ResourseNotFoundException('front.sensor');

        $device = DeviceRepo::find($item->device_id);

        $this->checkException('devices', 'update', $device);

        $this->validate($this->data, $item);

        $update = $this->formatInput($this->data);
        $item->update($update);

        $this->setValue($item, $update);

        return ['status' => 1];
    }

    public function destroy() {
        if (array_key_exists('sensor_id', $this->data))
            $sensor_id = $this->data['sensor_id'];
        else
            $sensor_id = request()->id;

        $item = DeviceSensorRepo::find($sensor_id);

        if (empty($item))
            throw new ResourseNotFoundException('front.sensor');

        $device = DeviceRepo::find($item->device_id);

        $this->checkException('devices', 'update', $device);

        DeviceSensorRepo::delete($item->id);

        return ['status' => 1];
    }

    public function preview()
    {
        $this->data['sensor_name'] = 'Preview';

        $sensor = DeviceSensor::find($this->data['id'] ?? null);

        $this->validate($this->data, $sensor);
        $input = $this->formatInput($this->data);

        $sensor = new DeviceSensor($input);
        $value = $this->data['input'] ?? null;
        $position = new TraccarPosition([
            'other' => "<info><{$input['tag_name']}>$value</{$input['tag_name']}></info>"
        ]);

        return [
            'status' => 1,
            'value' => $sensor->getValueParameters($position),
            'formatted' => $sensor->getValueFormated($position),
        ];
    }

    private function getParentQuery()
    {
        if (!empty($this->data['device_id'])) {
            return DeviceSensor::where('device_id', $this->data['device_id']);
        }

        if (!empty($this->data['sensor_group_id'])) {
            return SensorGroupSensor::where('group_id', $this->data['sensor_group_id']);
        }

        return null;
    }

    public function itemForm($item)
    {
        if ($setflag = SetFlag::singleCropValue($item->on_tag_value)) {
            $item->setflag = TRUE;
            $item->on_tag_start = $setflag->getStart();
            $item->on_tag_count = $setflag->getCount();
            $item->on_tag_value = $setflag->getValue();
        }

        if ($setflag = SetFlag::singleCropValue($item->off_tag_value)) {
            $item->setflag = TRUE;
            $item->off_tag_start = $setflag->getStart();
            $item->off_tag_count = $setflag->getCount();
            $item->off_tag_value = $setflag->getValue();
        }

        if ($setflag = SetFlag::singleCrop($item->formula)) {
            $item->setflag = TRUE;
            $item->setflag_start = $setflag->getStart();
            $item->setflag_count = $setflag->getCount();
        }

        if ($mappings = $item->mappings) {
            foreach ($mappings as &$mapping) {
                $sensorIcon = runCacheEntity(SensorIcon::class, $mapping['icon'] ?? null)->first();
                $mapping['icon'] = $sensorIcon->id ?? null;
                $mapping['url'] = $sensorIcon ? asset($sensorIcon->path) : null;
            }
            $item->mappings = $mappings;
        }

        $this->getValue($item);

        return $item;
    }

    public function validate($input, $item = NULL)
    {
        $types = $this->sensorsManager->getEnabledList()->map(function (Sensor $sensor) {
            return $sensor::getType();
        });

        $validator = \Validator::make($input, [
            'sensor_type' => 'required|in:' . $types->implode(','),
            'sensor_name' => 'required'
        ]);
        if ($validator->fails())
            throw new ValidationException($validator->messages());

        $sensorType = $this->sensorsManager->resolveType($input['sensor_type']);

        if ($showTypes = $sensorType::getShowTypes()) {
            $validator = \Validator::make($input, [
                'shown_value_by' => 'required|in:' . implode(',', array_keys($showTypes))
            ]);
            if ($validator->fails())
                throw new ValidationException($validator->messages());
        }

        if ($sensorType::isUnique() && $parentQuery = $this->getParentQuery()) {
            $sensors_nr = $parentQuery->where('type', $input['sensor_type'])->count();

            if ($item && $item['type'] == $input['sensor_type'])
                $sensors_nr--;

            if ($sensors_nr)
                throw new ValidationException(['sensor_type' => trans('front.already_has_sensor')]);
        }

        $rules = $this->getSensorTypeRules($input['sensor_type'], $input['shown_value_by'] ?? null);

        if ($rules) {
            $validator = \Validator::make($input, $rules);

            if ($validator->fails()) {
                throw new ValidationException($validator->messages());
            }
        }
    }

    protected function getSensorTypeRules($type, $showBy)
    {
        $sensorType = $this->sensorsManager->resolveType($type);

        $sensorInputs = $sensorType::getInputsFor($showBy);

        $conditionTypes = array_keys(ConditionService::getList());

        $rules = [
            'shown_value_by' => 'sometimes',
            'add_to_history' => 'sometimes',
            'add_to_graph' => 'sometimes'
        ];
        foreach ($sensorInputs as $key => $status) {
            if (!$status)
                continue;

            switch ($key) {
                case 'tag_name':
                    $rules['tag_name'] = 'required';
                    break;
                case 'logic_on':
                    $rules['on_type'] = 'required|in:' . implode(',', $conditionTypes) ;
                    $rules['on_tag_value'] = 'required';
                    $rules['on_tag_start'] = 'required_if:setflag,1|numeric|min:0';
                    $rules['on_tag_count'] = 'required_if:setflag,1|numeric|min:1';
                    break;
                case 'logic_off':
                    $rules['off_type'] = 'required|in:' . implode(',', $conditionTypes) ;
                    $rules['off_tag_value'] = 'required';
                    $rules['off_tag_start'] = 'required_if:setflag,1|numeric|min:0';
                    $rules['off_tag_count'] = 'required_if:setflag,1|numeric|min:1';
                    break;
                case 'formula':
                    $rules['formula'] = 'nullable|formula';
                    break;
                case 'full_tank':
                    $rules['full_tank'] = 'required_without:calibrations|numeric|nullable';
                    $rules['full_tank_value'] = 'required_without:calibrations|numeric|nullable';
                    $rules['fuel_tank_name'] = 'nullable|string';
                    break;
                case 'calibration':
                    $rules['calibrations'] = 'required_if:calibration,1|nullable|array|min:2|array_max:100';
                    $rules['calibrations.*'] = 'required|numeric';
                    $rules['skip_calibration'] = '';
                    break;
                case 'mapping':
                    $rules['mappings'] = 'required_if:mapping,1|nullable|array|min:1|array_max:20';
                    $rules['mappings.*.vt'] = 'required|in:' . Mapping::getValueTypes()->keys()->implode(',');
                    $rules['mappings.*.v'] = 'sometimes';
                    $rules['mappings.*.t'] = 'sometimes';
                    $rules['mappings.*.mt'] = 'required|in:' . Mapping::getMapTypes()->keys()->implode(',');
                    $rules['mappings.*.mv'] = 'sometimes';
                    break;
                case 'setflag':
                    $rules['setflag_start'] = 'required_if:setflag,1|numeric|min:0';
                    $rules['setflag_count'] = 'required_if:setflag,1|numeric|min:1';
                    break;
                case 'bitcut':
                    $rules['bitcut'] = 'nullable|array';
                    $rules['bitcut.start'] = 'required_with:bitcut|numeric|min:0';
                    $rules['bitcut.count'] = 'required_with:bitcut|numeric|min:1';
                    $rules['bitcut.base']  = 'required_with:bitcut|in:' . BitCut::bases()->implode('key', ',');
                    break;
                case 'minmax':
                    $rules['min_value'] = 'required|numeric|min:0';
                    $rules['max_value'] = 'required|numeric|min:0';
                    break;
                case 'unit':
                    $rules['unit_of_measurement'] = 'max:32';
                    break;
                case 'unit_type':
                    $rules['unit_type'] = 'required|in:' . EngineHours::unitTypes()->implode('key', ',');
                    break;
                case 'value':
                    $rules['value'] = 'sometimes|numeric';
                    break;
                case 'skip_empty':
                    $rules['skip_empty'] = '';
                    break;
                case 'reset_blank':
                    $rules['reset_blank'] = '';
                    break;
                case 'bin':
                    $rules['decbin'] = '';
                    $rules['hexbin'] = '';
                    break;
                case 'ascii':
                    $rules['ascii'] = '';
                    break;
                case 'odometer_unit':
                    $rules['odometer_value_unit'] = 'in:km,mi';
                    break;
            }
        }

        return $rules;
    }

    public function formatInput($input)
    {
        $rules = $this->getSensorTypeRules($input['sensor_type'], $input['shown_value_by'] ?? null);

        $defaults = array_fill_keys((new DeviceSensor())->getFillable(), null);
        unset($defaults['value'], $defaults['odometer_value'], $defaults['odometer_value_unit']);

        $update = array_merge($defaults, [
            'user_id' => $this->user->id,
            'device_id' => $input['device_id'],
            'name' => $input['sensor_name'],
            'type' => $input['sensor_type'],
        ]);

        foreach ($rules as $key => $rule) {
            switch ($key) {
                case 'tag_name':
                    $update['tag_name'] = trim($input['tag_name'] ?? '');
                    break;
                case 'calibrations':
                    if (Arr::get($input,'calibrations')) {
                        asort($input['calibrations']);
                        foreach ($input['calibrations'] as $value => $calibrated) {
                            if (!is_numeric($calibrated) || !is_numeric($value))
                                unset($input['calibrations'][$value]);
                        }
                        $update[$key] = $input[$key];
                    }
                    break;
                case 'mappings':
                    if (Arr::get($input,'mappings')) {
                        $update[$key] = collect($input[$key])->sortBy('v')->toArray();
                    }
                    break;
                case 'bitcut':
                    if (Arr::get($input,'bitcut')) {
                        $update['bitcut'] = [
                            'start' => $input['bitcut']['start'],
                            'count' => $input['bitcut']['count'],
                            'base'  => $input['bitcut']['base']
                        ];
                    }
                    break;
                case 'setflag_start':
                case 'setflag_count':
                    if (Arr::get($input,'setflag')) {
                        $update['formula'] = SetFlag::buildCrop($input['setflag_start'], $input['setflag_count']);
                    }
                    break;
                case 'on_tag_value':
                    if (Arr::get($input,'setflag')) {
                        $update['on_tag_value'] = SetFlag::buildCropValue(
                            $input['on_tag_start'],
                            $input['on_tag_count'],
                            $input['on_tag_value'],
                        );
                    } else {
                        $update['on_tag_value'] = $input['on_tag_value'];
                    }
                    break;
                case 'off_tag_value':
                    if (Arr::get($input,'setflag')) {
                        $update['off_tag_value'] = SetFlag::buildCropValue(
                            $input['off_tag_start'],
                            $input['off_tag_count'],
                            $input['off_tag_value'],
                        );
                    } else {
                        $update['off_tag_value'] = $input['off_tag_value'];
                    }
                    break;
                case 'on_tag_start':
                case 'on_tag_count':
                case 'off_tag_start':
                case 'off_tag_count':
                    break;
                default:
                    if (array_key_exists($key, $input))
                        $update[$key] = $input[$key];
            }
        }


        switch ($input['sensor_type']) {
            case EngineHours::getType():
                if (Arr::get($input, 'shown_value_by') == 'virtual') {
                    $update['tag_name'] = TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY;
                }
                if (Arr::has($input, 'value')) {

                }
                break;
            case Odometer::getType():
                if (Arr::get($input, 'shown_value_by') == 'virtual_odometer' && Arr::has($input, 'value') ) {
                    $update['odometer_value'] = $input['value'];
                    unset($update['value']);
                }
                break;
        }

        return $update;
    }

    protected function setValue($item, $input)
    {
        $item->setValue($item->getValue($item->device->other));

        $value = Arr::get($input, 'value');

        if (is_null($value))
            return;

        switch ($item->type) {
            case EngineHours::getType():
                if (in_array($item->shown_value_by, ['virtual', 'logical'])) {
                    $value = floatval($value);
                    $engine_hours = round($value * 3600);

                    if ($position = $item->device->positions()->lastest()->first()) {
                        $position->setParameter(TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY, $engine_hours);
                        $position->save();
                    }

                    $item->device->setParameter(TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY, $engine_hours);
                    $item->device->traccar->save();
                }
                break;
        }
    }

    protected function getValue($item) {
        switch ($item->type) {
            case EngineHours::getType():
                if (in_array($item->shown_value_by, ['virtual', 'logical']) && $item->device) {
                    $engine_hours = $item->device->getParameter(TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY, 0) / 3600;
                    $item->value  = round($engine_hours, 2);
                }
                break;
            case Odometer::getType():
                if (in_array($item->shown_value_by, ['virtual_odometer'])) {
                    $item->value  = $item->odometer_value;
                }
                break;
        }
    }

    /**
     * @deprecated
     * @param $device_id
     * @return array
     */
    public function getVirtualEngineHours($device_id)
    {
        $device = DeviceRepo::find($device_id);

        $this->checkException('devices', 'show', $device);

        $engine_hours = round($device->getParameter(TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY, 0) / 3600, 2);

        return [
            'device_id'    => $device_id,
            'engine_hours' => $engine_hours
        ];
    }

    /**
     * @deprecated
     * @param $device_id
     * @return array
     */
    public function setVirtualEngineHours($device_id)
    {
        $device = DeviceRepo::find($device_id);

        $this->checkException('devices', 'edit', $device);

        $validator = \Validator::make($this->data, [
            'engine_hours' => 'required|numeric'
        ]);

        if ($validator->fails())
            throw new ValidationException(['engine_hours' => $validator->errors()->first()]);

        $engine_hours = round($this->data['engine_hours'] * 3600);

        if ($position = $device->positions()->lastest()->first()) {
            $position->setParameter(TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY, $engine_hours);
            $position->save();
        }

        $device->setParameter(TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY, $engine_hours);
        $device->traccar->save();

        $device->sensors()
            ->where('type', 'engine_hours')
            ->where(function($query) {
                $query->where('tag_name', TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY)->orWhere('shown_value_by', 'logical');
            })
            ->update([
                'value' => $this->data['engine_hours']
            ]);

        return ['status' => 1];
    }

}