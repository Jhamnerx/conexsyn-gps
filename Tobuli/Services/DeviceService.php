<?php

namespace Tobuli\Services;

use App\Exceptions\DeviceLimitException;
use App\Jobs\DeleteDatabaseTable;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceType;
use Tobuli\Entities\User;

class DeviceService
{
    protected User $user;

    public function __construct(
        private CustomValuesService $customValueService,
        private DeviceUsersService $deviceUsersService,
        private TagService $tagService,
    ) {}

    public function setActingUser(User $user)
    {
        $this->user = $user;
    }

    public function getActingUser()
    {
        if (!isset($this->user))
            return getActingUser();

        return $this->user;
    }

    public function getDefaults()
    {
        $expirationDate = '0000-00-00 00:00:00';
        $installation_date = '0000-00-00';

        if (settings('plugins.create_only_expired_objects.status')) {
            $expirationOffset = settings('plugins.create_only_expired_objects.options.offset') ?? 0;
            $expirationOffsetType = settings('plugins.create_only_expired_objects.options.offset_type') ?? 'days';
            $expirationDate = date('Y-m-d H:i:s', strtotime(" + {$expirationOffset} {$expirationOffsetType}"));
        }

        if (self::isDefaultInstallationDateToday()) {
            $installation_date = date('Y-m-d');
        }

        return [
            'active'              => true,
            'imei'                => null,
            'group_id'            => 0,
            'timezone_id'         => null,
            'fuel_price'          => 0,
            'fuel_quantity'       => 0,
            'fuel_measurement_id' => 1,
            'min_fuel_fillings'   => settings('device.min_fuel_fillings'),
            'min_fuel_thefts'     => settings('device.min_fuel_thefts'),
            'min_moving_speed'    => settings('device.min_moving_speed'),
            'tail_length'         => settings('device.tail.length'),
            'tail_color'          => settings('device.tail.color'),
            'expiration_date'     => $expirationDate,
            'installation_date'   => $installation_date,
            'icon_id'             => settings('device.icon_id'),
            'icon_colors'         => settings('device.status_colors.colors')
        ];
    }

    public function normalize($data)
    {
        if (array_key_exists('icon_id', $data) && empty($data['icon_id'])) {
            $data['device_icons_type'] = 'arrow';
        }

        if (array_key_exists('device_icons_type', $data) && $data['device_icons_type'] == 'arrow') {
            $data['icon_id'] = 0;
        }

        if (array_key_exists('model_id', $data) && empty($data['model_id'])) {
            $data['model_id'] = null;
        }

        if (array_key_exists('max_speed', $data) && empty($data['max_speed'])) {
            $data['max_speed'] = null;
        }

        if (array_key_exists('fuel_quantity', $data)) {
            $data['fuel_quantity'] = floatval($data['fuel_quantity']);
        }

        if (array_key_exists('fuel_price', $data)) {
            $data['fuel_price'] = floatval($data['fuel_price']);
        }

        if (array_key_exists('custom_data', $data) && !config('addon.device_custom_data')) {
            unset($data['custom_data']);
        }

        if (array_key_exists('gprs_templates_only', $data)) {
            $data['gprs_templates_only'] = empty($data['gprs_templates_only']) ? 0 : 1;
        }

        if (!empty($data['sim_activation_date']) && settings('plugins.annual_sim_expiration.status')) {
            $data['sim_expiration_date'] = Carbon::createFromTimestamp(strtotime($data['sim_activation_date']))
                ->addDays(settings('plugins.annual_sim_expiration.options.days'))
                ->toDateString();
        }

        foreach (['mtc', 'mininter'] as $integration) {
            if (array_key_exists($integration, $data)) {
                $data[$integration] = empty($data[$integration]) ? 0 : 1;
            }
        }

        if (array_key_exists('mininter_type', $data) && empty($data['mininter_type'])) {
            $data['mininter_type'] = Device::MININTER_TYPE_SERENAZGO;
        }

        if ($forwardIP = Arr::get($data, 'forward.ip')) {
            //clear empty
            $data['forward']['ip'] = implode(';', semicol_explode($forwardIP));
        }

        return $data;
    }

    protected function normalizeCreate(Device $device, array $data): void
    {
        if (isset($data['kind'])) {
            $device->kind = $data['kind'];
        }

        $user = $this->getActingUser();
        $databaseService = DatabaseService::instance();

        if ($user) {
            $databaseId = $databaseService->getUserActiveDatabaseId($user);
        }

        if (!isset($databaseId)) {
            $databaseId = $databaseService->getActiveDatabaseId();
        }

        $device->database_id = $databaseId;
    }

    public function filterEditables(User $user, $data)
    {
        return onlyEditables(new Device(), $user, $data);
    }

    public function create($data)
    {
        if ($this->deviceUsersService->isLimitReached()) {
            throw new DeviceLimitException();
        }

        $data = array_merge($this->getDefaults(), $data);
        $data = $this->normalize($data);

        beginTransaction();

        try {
            $device = new Device($data);
            $this->normalizeCreate($device, $data);
            $device->save();

            $this->saveCustomFields($device, $data);
            $this->saveSensors($device, $data);
            $this->saveUsers($device, $data);
            $this->attachCreator($device);
            $this->saveGroup($device, $data);
            $this->saveTags($device, $data);

            $device->createPositionsTable();

            commitTransaction();

        } catch (\Exception $e) {
            rollbackTransaction();

            throw $e;
        }

        return $device;
    }

    protected function attachCreator(Device $device): void
    {
        if (!settings('plugins.device_attached_to_creator.status')) {
            return;
        }

        if (!$user = $this->getActingUser()) {
            return;
        }

        if ($device->users()->where('user_id', $user->id)->count() === 0) {
            $device->users()->attach($user->id);
        }
    }

    public function update(Device $device, $data)
    {
        $data = $this->normalize($data);

        beginTransaction();

        try {
            $device->update($data);

            $this->saveCustomFields($device, $data);
            $this->saveSensors($device, $data);
            $this->saveUsers($device, $data);
            $this->saveGroup($device, $data);
            $this->saveTags($device, $data);

            commitTransaction();

        } catch (\Exception $e) {
            rollbackTransaction();

            throw $e;
        }

        return $device;
    }

    public function delete(Device $device)
    {
        beginTransaction();

        try {
            $device->users()->sync([]);
            $device->tags()->sync([]);
            $device->events()->delete();
            $device->sensors()->delete();
            $device->services()->delete();
            DB::table('user_drivers')->where('device_id', $device->id)->update(['device_id' => null]);

            if ($device->traccar) {
                $positionTable = $device->positions()->getRelated();
                $device->traccar->delete();
            }

            $device->delete();

            if (!empty($positionTable)) {
                dispatch(new DeleteDatabaseTable($positionTable->getTable(), $positionTable->getConnectionName()));
            }

            commitTransaction();
        } catch (\Exception $e) {
            rollbackTransaction();

            throw $e;
        }
    }

    protected function saveCustomFields(Device $device, $data)
    {
        if (!array_key_exists('custom_fields', $data))
            return;

        $this->customValueService->saveCustomValues($device, $data['custom_fields'] ?? []);
    }

    protected function saveUsers(Device $device, $data)
    {
        if (!array_key_exists('user_id', $data))
            return;

        $this->deviceUsersService->syncUsers($device, $data['user_id'] ?? []);
    }

    protected function saveGroup(Device $device, $data)
    {
        if (!array_key_exists('group_id', $data))
            return;

        $this->deviceUsersService->setGroup($device, $this->getActingUser(), $data['group_id']);
    }

    protected function saveSensors(Device $device, $data)
    {
        $sensor_group_id = $data['sensor_group_id'] ?? null;

        if (empty($sensor_group_id)
            && $device->wasRecentlyCreated
            && $device->device_type_id
            && $deviceType = DeviceType::find($device->device_type_id)) {
            $sensor_group_id = $deviceType->sensor_group_id;
        }

        if (empty($sensor_group_id)) {
            return;
        }

        $sensorsService = new DeviceSensorsService();
        $sensorsService->addSensorGroup($device, $this->getActingUser(), $sensor_group_id);
    }

    protected function saveTags(Device $device, $data): void
    {
        if (!array_key_exists('tags', $data)) {
            return;
        }

        $user = $this->getActingUser();

        if (!$user->can('edit', $device, 'tags')) {
            return;
        }

        $this->tagService->setUser($user)->setToModel($device, $data['tags']);
    }

    public static function getExpirationDateOffset()
    {
        $offset = settings('device.expiration_offset');

        if (empty($offset))
            return null;

        return Carbon::now()->addDays($offset);
    }

    public static function isDefaultInstallationDateToday(): bool
    {
        $pluginConf = settings('plugins.additional_installation_fields');

        return !empty($pluginConf['status']) && !empty($pluginConf['options']['installation_date_default_today']);
    }
}
