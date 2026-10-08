<?php

namespace Tobuli\Importers\Device;

use Cache;
use CustomFacades\ModalHelpers\SensorModalHelper;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\SensorGroupSensorRepo;
use CustomFacades\Repositories\TimezoneRepo;
use Illuminate\Support\Arr;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceGroup;
use Tobuli\Entities\User;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Importers\Importer;
use Tobuli\Sensors\Types\Blocked;
use Tobuli\Services\DeviceSensorsService;
use Tobuli\Services\DeviceUsersService;
use Tobuli\Services\RequiredFields\DeviceRequiredFieldsService;

class DeviceImporter extends Importer
{
    /**
     * @var DeviceUsersService
     */
    protected $deviceUsersService;

    /**
     * @var DeviceSensorsService
     */
    protected $deviceSensorsService;

    /**
     * @var DeviceRequiredFieldsService
     */
    protected $deviceRequiredFieldsService;

    protected $device_icon_colors = [
        'green',
        'yellow',
        'red',
        'blue',
        'orange',
        'black',
    ];

    public function __construct()
    {
        $this->deviceRequiredFieldsService = new DeviceRequiredFieldsService();
        $this->deviceSensorsService = new DeviceSensorsService();
        $this->deviceUsersService = new DeviceUsersService();
    }

    protected function importItem($data, $attributes = [])
    {
        $data = array_filter($data, function($value) {
            return !(empty($value) && !is_numeric($value));
        });

        $data = $this->mergeDefaults($data);
        $data = $this->normalize($data);

        if ( ! $this->validate($data)) {
            return;
        }

        $device = $this->getDevice($data);

        if ($device && $this->onCollision === self::COLLISION_STOP) {
            throw new ValidationException([
                'imei' => trans('validation.unique', ['attribute' => trans('validation.attributes.imei')]),
            ]);
        }

        if ( ! $device) {
            if ($this->devicesLimit($data)) {
                return;
            }

            $this->create($data);
        }
    }

    private function normalize(array &$data): array
    {
        $users = $this->getUsers($data);

        if ($users) {
            $data['user_id'] = $users->pluck('id')->all();
        } else {
            $data['user_id'] = [auth()->user()->id];
        }

        if (empty($data['icon_id'])) {
            $data['icon_id'] = settings('device.icon_id');
        }

        $statuses = array_keys(settings('device.status_colors.colors'));

        foreach ($statuses as $status) {
            if (isset($data['icon_' . $status]) && in_array($data['icon_' . $status], $this->device_icon_colors)) {
                $data['icon_colors'][$status] = $data['icon_' . $status];
            }
        }

        if ( ! empty($data['timezone'])) {
            $timezone = $this->getTimezone($data['timezone']);

            $data['timezone_id'] = $timezone ? $timezone->id : null;
        }

        return $data;
    }

    private function getUsers($data)
    {
        if (empty($filter) && !empty($data['users'])) {
            $emails = explode(',', $data['users']);
            $emails = array_map('trim', $emails);
            $emails = array_filter($emails);
            $emails = Arr::sort($emails);

            if ($emails) {
                $filter['email'] = $emails;
            }
        }

        if (empty($filter) && !empty($data['user_id'])) {
            $user_ids = is_string($data['user_id']) ? explode(',', $data['user_id']) : $data['user_id'];
            $user_ids = is_array($user_ids) ? $user_ids : [$user_ids];
            $user_ids = array_map('trim', $user_ids);
            $user_ids = array_filter($user_ids);
            $user_ids = Arr::sort($user_ids);

            if ($user_ids) {
                $filter['id'] = $user_ids;
            }
        }

        if (empty($filter)) {
            return collect([auth()->user()]);
        }

        $cacheKey = "device_importer.users." . md5(json_encode($filter));

        $users = Cache::store('array')->rememberForever($cacheKey, function() use ($filter) {

            $query = User::userAccessible(auth()->user());

            foreach ($filter as $key => $values) {
                $query->whereIn($key, $values);
            }

            return $query->get();
        });

        return $users->isEmpty() ? collect([auth()->user()]) : $users;
    }

    private function getTimezone($timezone)
    {
        return TimezoneRepo::findWhere(['title' => 'UTC ' . $timezone]);
    }

    private function getDevice($data)
    {
        return DeviceRepo::whereImei($data['imei']);
    }

    private function devicesLimit($data)
    {
        if ($this->deviceUsersService->isServerLimitReached()) {
            throw new ValidationException(['id' => trans('front.devices_limit_reached')]);
        }

        $users = $this->getUsers($data);

        foreach ($users as $user) {
            if ($this->deviceUsersService->isUserLimitReached($user)) {
                throw new ValidationException(['id' => $user->email . ': ' . trans('front.devices_limit_reached')]);
            }
        }

        return false;
    }

    private function create($data)
    {
        beginTransaction();
        try {
            $device = DeviceRepo::create($data);

            $this->deviceSyncUsers($device, $data);

            $device->createPositionsTable();

            $this->createSensors($device, $data);

        } catch (\Exception $e) {
            rollbackTransaction();
            throw new ValidationException(['id' => $e->getMessage()]);
        }
        commitTransaction();
    }

    private function deviceSyncUsers($device, $data)
    {
        $this->deviceUsersService->syncUsers($device, $data['user_id']);

        $group = $data['group_id'] ? DeviceGroup::find($data['group_id']) : 0;

        // Filter User with group
        $users = array_filter($data['user_id'], function($user_id) use ($group){
            return $group && $user_id == $group->user_id;
        });

        if ($users) {
            $this->deviceUsersService->setGroup($device, $users, $group, $data['visible'] ? true : false);
        }

        // Filter Users without group
        $users = array_filter($data['user_id'], function($user_id) use ($group){
            return ! $group || $user_id != $group->user_id;
        });

        if ($users) {
            $this->deviceUsersService->setGroup($device, $users, null, $data['visible'] ? true : false);
        }
    }

    protected function createSensors($device, $data)
    {
        if ( ! isAdmin()) {
            return;
        }

        if ( ! isset($data['sensor_group_id'])) {
            return;
        }

        $sensor_group_id = intval($data['sensor_group_id']);

        if (empty($sensor_group_id))
            return;

        $this->deviceSensorsService->addSensorGroup($device, getActingUser(), $sensor_group_id);
    }

    public function getValidationExtraRules(): array
    {
        return $this->deviceRequiredFieldsService->getRules();
    }

    public function getValidationBaseRules(): array
    {
        return [
            'imei'                => 'required|imei',
            'name'                => 'required',
            'icon_id'             => 'exists:device_icons,id',
            'fuel_quantity'       => 'numeric',
            'fuel_price'          => 'numeric',
            'fuel_measurement_id' => 'in:1,2,3,4,5',
            'tail_length'         => 'numeric|min:0|max:10',
            'max_speed'           => 'numeric|min:1',
            'min_moving_speed'    => 'numeric|min:1|max:50',
            'min_fuel_fillings'   => 'numeric|min:1|max:1000',
            'min_fuel_thefts'     => 'numeric|min:1|max:1000',
            'group_id'            => 'nullable|exists:device_groups,id',
            'sim_number'          => 'unique:devices,sim_number',
            'timezone_id'         => 'nullable|exists:timezones,id',
            'lbs'                 => 'nullable|boolean',
        ];
    }

    protected function getDefaults()
    {
        return [
            'visible'             => true,
            'active'              => true,
            'group_id'            => null,
            'icon_id'             => settings('device.icon_id'),
            'fuel_quantity'       => 0,
            'fuel_price'          => 0,
            'fuel_measurement_id' => 1,
            'min_moving_speed'    => settings('device.min_moving_speed'),
            'min_fuel_fillings'   => settings('device.min_fuel_fillings'),
            'min_fuel_thefts'     => settings('device.min_fuel_thefts'),
            'tail_length'         => settings('device.tail.length'),
            'tail_color'          => settings('device.tail.color'),
            'timezone_id'         => null,
            'expiration_date'     => '0000-00-00 00:00:00',
            'gprs_templates_only' => false,
            'snap_to_road'        => false,
            'icon_colors'         => settings('device.status_colors.colors'),
        ];
    }
}
