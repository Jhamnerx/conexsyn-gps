<?php namespace ModalHelpers;

use App\Exceptions\DeviceLimitException;
use App\Exceptions\PermissionException;
use App\Transformers\ApiV1\DeviceFullJsonTransformer;
use App\Transformers\ApiV1\DeviceFullTransformer;
use App\Transformers\Device\DeviceMapFullTransformer;
use App\Transformers\Device\DeviceMapTransformer;
use App\Transformers\Event\EventLatestTransformer;
use Carbon\Carbon;
use CustomFacades\ModalHelpers\SensorModalHelper;
use CustomFacades\ModalHelpers\ServiceModalHelper;
use CustomFacades\Repositories\DeviceGroupRepo;
use CustomFacades\Repositories\DeviceIconRepo;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\DeviceSensorRepo;
use CustomFacades\Repositories\SensorGroupRepo;
use CustomFacades\Repositories\TimezoneRepo;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Repositories\DeviceCameraRepo;
use CustomFacades\Validators\DeviceConfiguratorFormValidator;
use CustomFacades\Validators\DeviceFormValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\ApnConfig;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceConfig;
use Tobuli\Entities\DeviceIcon;
use Tobuli\Entities\DeviceModel;
use Tobuli\Entities\DeviceType;
use Tobuli\Entities\Event;
use Tobuli\Entities\UnregisteredDevice;
use Tobuli\Entities\User;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\SMS\SMSGatewayManager;
use Tobuli\Services\AlertSoundService;
use Tobuli\Services\CustomValuesService;
use Tobuli\Services\DeviceConfigService;
use Formatter;
use Tobuli\Services\DeviceSensorsService;
use Tobuli\Services\DeviceService;
use Tobuli\Services\DeviceUsersService;
use Tobuli\Services\EntityLoader\UsersLoader;
use Tobuli\Services\FractalSerializers\DataArraySerializer;
use Tobuli\Services\FractalSerializers\WithoutDataArraySerializer;
use Tobuli\Services\FractalTransformerService;

class DeviceModalHelper extends ModalHelper
{
    private $device_fuel_measurements = [];
    private $configService;
    private $customValueService;
    private $deviceService;

    /**
     * @var DeviceUsersService
     */
    private $deviceUsersService;

    /**
     * @var FractalTransformerService
     */
    private $transformerService;

    /**
     * @var UsersLoader
     */
    private $usersLoader;

    public function __construct(
        DeviceConfigService $configService,
        DeviceService $deviceService,
        CustomValuesService $customValueService,
        FractalTransformerService $transformerService
    ) {
        parent::__construct();

        $this->device_fuel_measurements = [
            [
                'id' => 1,
                'title' => trans('front.l_km'),
                'fuel_title' => trans('front.liters'),
                'distance_title' => '100 ' . strtolower(trans('front.kilometers')),
                'cost_title' => strtolower(trans('front.liter')),
            ],
            [
                'id' => 2,
                'title' => trans('front.mpg'),
                'fuel_title' => trans('front.miles'),
                'distance_title' => strtolower(trans('front.gallon')),
                'cost_title' => strtolower(trans('front.gallon')),
            ],
            [
                'id' => 3,
                'title' => trans('front.kwh_km'),
                'fuel_title' => trans('front.kwhs'),
                'distance_title' => strtolower(trans('front.kilometer')),
                'cost_title' => strtolower(trans(trans('front.kwh'))),
            ],
            [
                'id' => 4,
                'title' => trans('front.l_h'),
                'fuel_title' => trans('front.liters'),
                'distance_title' => strtolower(trans('front.hour')),
                'cost_title' => strtolower(trans('front.liter')),
            ],
            [
                'id' => 5,
                'title' => trans('front.km_l'),
                'fuel_title' => trans('front.kilometers'),
                'distance_title' => strtolower(trans('front.liter')),
                'cost_title' => strtolower(trans('front.liter')),
            ],
        ];

        $this->icons_types = [
            'arrow' => trans('front.arrow'),
            'rotating' => trans('front.rotating_icon'),
            'icon' => trans('front.icon')
        ];

        $this->device_icon_colors = [
            'green'  => trans('front.green'),
            'yellow' => trans('front.yellow'),
            'red'    => trans('front.red'),
            'blue'   => trans('front.blue'),
            'orange' => trans('front.orange'),
            'black'  => trans('front.black'),
        ];

        $this->fuel_detect_stop_durations = [
            60 => '1 ' . trans('front.minute_short'),
            120 => '2 ' . trans('front.minute_short'),
            180 => '3 ' . trans('front.minute_short'),
            300 => '5 ' . trans('front.minute_short'),
        ];

        $this->expiration_date_select = [
            '0000-00-00 00:00:00' => trans('front.unlimited'),
            '1' => trans('validation.attributes.expiration_date')
        ];

        $this->fuel_types = [
            null => trans('front.nothing_selected'),
            'petrol' => trans('front.petrol'),
            'diesel' => trans('front.diesel'),
            'biodiesel' => trans('front.biodiesel'),
            'biogas' => trans('front.biogas'),
            'hydrogen' => trans('front.hydrogen'),
            'electric' => trans('front.electric'),
            'ethanol' => trans('front.ethanol'),
            'petrol_electric' => trans('front.petrol_electric'),
            'diesel_electric' => trans('front.diesel_electric'),
            'propane' => trans('front.propane'),
            'methanol' => trans('front.methanol'),
        ];

        $this->configService = $configService;
        $this->deviceService = $deviceService;
        $this->customValueService = $customValueService;
        $this->transformerService = $transformerService->setSerializer(WithoutDataArraySerializer::class);
        $this->deviceUsersService = new DeviceUsersService();

        $this->usersLoader = new UsersLoader($this->user);
        $this->usersLoader->setRequestKey('user_id');
    }

    public function createData() {
        $perm = request()->get('perm');

        if ($perm == null || ($perm != null && $perm != 1)) {
            if ($perm != null && $perm != 2) {
                if ($this->deviceUsersService->isLimitReached($this->user)) {
                    throw new DeviceLimitException();
                }
            }

            $this->checkException('devices', 'create');
        }

        $icons_type = $this->icons_types;
        $device_icon_colors = $this->device_icon_colors;
        $fuel_detect_sec_after_stop_options = $this->fuel_detect_stop_durations;
        $expiration_date_select = $this->expiration_date_select;
        $fuel_types = $this->fuel_types;
        $device_fuel_measurements = $this->device_fuel_measurements;
        $device_fuel_measurements_select = Arr::pluck($this->device_fuel_measurements, 'title', 'id');

        $device_icons = DeviceIconRepo::getMyIcons($this->user->id);
        $device_icons_grouped = $device_icons
            ->filter(function($icon) { return $icon->type != 'arrow'; })
            ->groupBy('type');

        $users = [];

        $device_groups = DeviceGroupRepo::getWhere(['user_id' => $this->user->id])
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $timezones = TimezoneRepo::order()
            ->pluck('title', 'id')
            ->prepend(trans('front.default'), '0')
            ->map(function($timezone) { return str_replace('UTC ', '', $timezone); })
            ->all();

        $sensor_groups = isAdmin()
            ? SensorGroupRepo::getWhere([], 'title')
                ->pluck('title', 'id')
                ->prepend(trans('front.none'), '0')
                ->all()
            : [];

        $device_types = DeviceType::active()->get()->pluck('title', 'id')->prepend(trans('front.none'), '');
        $models = $this->getDeviceModels(new Device());

        if ($this->api) {
            $timezones = apiArray($timezones);
            $device_groups = apiArray($device_groups);
            $sensor_groups = apiArray($sensor_groups);
            $models = apiArray($models);
            $users = UserRepo::getUsers($this->user)->toArray();
        }

        $device_configs = [];
        $apn_configs = [];

        if ($this->user->able('configure_device')) {
            $device_configs = DeviceConfig::active()
                ->get()
                ->pluck('fullName', 'id');
            $apn_configs = ApnConfig::active()
                ->get()
                ->pluck('name', 'id');
        }

        $item = new Device();
        $item->active = true;
        $item->expiration_date = DeviceService::getExpirationDateOffset();
        $item->setRelation('icon', DeviceIcon::find(settings('device.icon_id')));
        $item->icon_id = $item->icon->id ?? 0;
        $item->valid_by_avg_speed = true;
        $item->min_moving_speed = settings('device.min_moving_speed');
        $item->min_fuel_fillings = settings('device.min_fuel_fillings');
        $item->min_fuel_thefts = settings('device.min_fuel_thefts');
        $item->tail_length = settings('device.tail.length');
        $item->tail_color = settings('device.tail.color');
        $item->installation_date = DeviceService::isDefaultInstallationDateToday()
            ? Formatter::date()->convert(date('Y-m-d'))
            : null;

        return compact('device_groups', 'sensor_groups', 'item',
            'device_fuel_measurements', 'device_icons', 'users', 'timezones',
            'expiration_date_select', 'fuel_types', 'device_fuel_measurements_select',
            'icons_type', 'device_icons_grouped', 'device_icon_colors',
            'device_configs', 'apn_configs', 'device_types', 'fuel_detect_sec_after_stop_options',
            'models'
        );
    }

    public function createUnregistered()
    {
        $items = UnregisteredDevice::whereIn('imei', $this->data['imeis'])->get();

        $invalidUsers = $this->deviceUsersService->getUsersUnableGetNewDevices($this->user, $items->count());

        if ($invalidUsers->isNotEmpty()) {
            throw new ValidationException(['user_id' => trans('validation.attributes.devices_limit') . ': ' . $invalidUsers->implode('email', ', ')]);
        }

        $multiItems = $items->count() > 1;
        $name = Arr::get($this->data, 'name');

        foreach ($items as $item) {
            $this->data['imei'] = $item->imei;

            if (request()->has('name')) {
                $this->data['name'] = $name . ($multiItems ? " $item->imei" : '');
            }

            $this->create();

            $item->delete();
        }

        return ['status' => 1];
    }

    public function create()
    {
        $this->checkException('devices', 'store');

        if ($this->deviceUsersService->isLimitReached($this->user)) {
            throw new DeviceLimitException();
        }

        $this->data['imei'] = isset($this->data['imei']) ? trim($this->data['imei']) : null;

        if (array_key_exists('enable_expiration_date', $this->data) && empty($this->data['enable_expiration_date'])) {
            $this->data['expiration_date'] = null;
        }

        if (!empty($this->data['expiration_date'])) {
            $this->data['expiration_date'] = Formatter::time()->reverse($this->data['expiration_date']);
        }

        $this->data['icon_colors'] = $this->setIconColors();

        $this->data = onlyEditables(new Device(), $this->user, $this->data);

        $this->setAbleUsers();
        $this->usersReachedLimit();

        DeviceFormValidator::validate('create', $this->data);

        $device = $this->deviceService->create($this->data);

        if ($this->data['configure_device'] ?? false) {
            $this->configureDevice($device);
        }

        return ['status' => 1, 'id' => $device->id,];
    }

    public function editData() {
        $device_id = $this->data['id']
            ?? request()->route('id')
            ?? $this->data['device_id']
            ?? null;

        $item = Device::find($device_id);

        $this->checkException($item && $item->isBeacon() ? 'beacons' : 'devices', 'edit', $item);

        $timezone_id = $item->timezone_id;
        $group_id = $this->user->devices()->find($item->id)->pivot->group_id ?? null;

        $icons_type = $this->icons_types;
        $device_icon_colors = $this->device_icon_colors;
        $fuel_detect_sec_after_stop_options = $this->fuel_detect_stop_durations;
        $expiration_date_select = $this->expiration_date_select;
        $fuel_types = $this->fuel_types;
        $device_fuel_measurements = $this->device_fuel_measurements;
        $device_fuel_measurements_select = Arr::pluck($this->device_fuel_measurements, 'title', 'id');

        $device_icons = DeviceIconRepo::getMyIcons($this->user->id);
        
        $device_icons_grouped = [];

        foreach ($device_icons as $dicon) {
            if ($dicon['type'] == 'arrow') {
                continue;
            }

            if (!array_key_exists($dicon['type'], $device_icons_grouped)) {
                $device_icons_grouped[$dicon['type']] = [];
            }

            $device_icons_grouped[$dicon['type']][] = $dicon;
        }

        $device_groups = DeviceGroupRepo::getWhere(['user_id' => $this->user->id])
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $sensors = SensorModalHelper::paginated($item->id);
        $services = ServiceModalHelper::paginated($item->id);

        $detects = $this->getDetectSensors($item);
        $engine_hours = $detects['engine_hours'];
        $detect_engine = $detects['engine'];
        $detect_distance = $detects['distance'];
        $detect_speed = $detects['speed'];

        $timezones = TimezoneRepo::order()
            ->pluck('title', 'id')
            ->prepend(trans('front.default'), '0')
            ->map(function($timezone) { return str_replace('UTC ', '', $timezone); })
            ->all();

        $sensor_groups = isAdmin()
            ? SensorGroupRepo::getWhere([], 'title')
                ->pluck('title', 'id')
                ->prepend(trans('front.none'), '0')
                ->all()
            : [];

        $models = $this->getDeviceModels($item);

        $users = [];
        $sel_users = [];
        if ($this->api) {
            $device_groups = apiArray($device_groups);
            $timezones = apiArray($timezones);
            $models = apiArray($models);
            $users = UserRepo::getUsers($this->user)->toArray();
            $sel_users = $item->users->pluck('id', 'id')->all();
        }

        $device_cameras = DeviceCameraRepo::searchAndPaginate(['filter' => ['device_id' => $device_id]], 'id', 'desc', 10);

        $device_types = DeviceType::active()->get()->pluck('title', 'id')->prepend(trans('front.none'), '');

        return compact('device_id', 'engine_hours', 'detect_engine', 'detect_distance', 'detect_speed',
            'device_groups', 'sensor_groups', 'item',
            'device_fuel_measurements', 'device_icons', 'sensors', 'services',
            'expiration_date_select', 'fuel_types', 'timezones',
            'users', 'sel_users', 'group_id', 'timezone_id',
            'device_fuel_measurements_select', 'icons_type',
            'device_icons_grouped', 'device_icon_colors', 'device_cameras', 'device_types',
            'fuel_detect_sec_after_stop_options', 'models'
        );
    }

    public function edit()
    {
        $this->data['id'] = $this->data['id']
            ?? $this->data['device_id']
            ?? null;

        $item = Device::find($this->data['id']);

        $this->checkException('devices', 'update', $item);

        if ($item->isBeacon())
            throw new ValidationException(['id' => 'Device is kind of beacon.']);

        if ( ! empty($this->data['timezone_id']) && $this->data['timezone_id'] != 57 && $item->isCorrectUTC()) {
            throw new ValidationException(['timezone_id' => 'Device time is correct. Check your timezone Setup -> Main -> Timezone']);
        }

        if (array_key_exists('enable_expiration_date', $this->data) && empty($this->data['enable_expiration_date'])) {
            $this->data['expiration_date'] = null;
        }

        if (!empty($this->data['expiration_date'])) {
            $this->data['expiration_date'] = Formatter::time()->reverse($this->data['expiration_date']);
        }

        $this->data['icon_colors'] = $this->setIconColors($item);

        $this->data = onlyEditables($item, $this->user, $this->data);

        $this->setAbleUsers($item);

        $this->usersReachedLimit($item);

        DeviceFormValidator::validate('update', $this->data, $item->id);

        $this->deviceService->update($item, $this->data);

        return ['status' => 1, 'id' => $item->id];
    }

    public function resetAppUuid(int $id): array
    {
        /** @var Device $item */
        $item = Device::findOrFail($id);

        $this->checkException('devices', 'edit', $item);

        $item->app_uuid = null;
        $success = $item->save();

        return ['status' => (int)$success, 'id' => $item->id];
    }

    public function destroy()
    {
        $imei = $this->data['imei'] ?? null;

        if (!is_null($imei)) {
            $item = DeviceRepo::whereImei($imei);
        } else {
            $device_id = $this->data['id'] ?? $this->data['device_id'] ?? null;
            $item = DeviceRepo::find($device_id);
        }

        $this->checkException('devices', 'remove', $item);

        $this->deviceService->delete($item);

        return ['status' => 1, 'id' => $item->id, 'deleted' => 1];
    }

    public function detach()
    {
        $device_id = $this->data['id']
            ?? $this->data['device_id']
            ?? null;

        $item = DeviceRepo::find($device_id);

        $this->checkException('devices', 'own', $item);

        $item->users()->detach($this->user->id);

        return ['status' => 1];
    }

    public function changeActive()
    {
        $validator = Validator::make($this->data, [
            'id' => 'required_without:group_id',
            'group_id' => 'required_without:id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $active = (isset($this->data['active']) && filter_var($this->data['active'], FILTER_VALIDATE_BOOLEAN)) ? 1 : 0;

        if (array_key_exists('group_id', $this->data)) {
            $updateCount = $this->deviceUsersService->setVisibleGroups($this->user, $this->data['group_id'], $active);
        } else {
            $updateCount = $this->deviceUsersService->setVisibleDevices($this->user, $this->data['id'], $active);
        }

        return ['status' => $updateCount ? 1 : 0];
    }

    public function itemsJson()
    {
        $this->checkException('devices', 'view');

        $now  = time();
        $time = empty($this->data['time']) ? $now - 5 : intval($this->data['time']);

        $query = empty($this->data['id']) ? $this->user->devices() : $this->user->accessibleDevices();

        $query
            ->filter($this->data)
            ->filter($this->data['filters'] ?? null)
            ->updatedAfter(date('Y-m-d H:i:s', $time))
            ->clearOrdersBy();

        if (request()->has('page') || request()->has('limit')) {
            $page = max(1, request()->get('page', 1));
            $limit = max(1, request()->get('limit', 100));
            $query->forPage($page, $limit);
        }

        $items = $this->api
            ? $this->transformerService
                ->collection($query->get(), DeviceFullJsonTransformer::class)->toArray()
            : $this->transformerService
                ->setSerializer(new DataArraySerializer())
                ->cursorPaginate($query->cursorPaginate(1000)->appends(['time' => $time]), DeviceMapTransformer::class)->toArray();

        if ($this->user->perm('events', 'view') && empty($this->data['cursor'])) {
            $eventTime = ($now - $time > 300) ? $now - 300 : $time;

            $events = Event::userAccessible($this->user)->higherTime($eventTime, $this->data['id'] ?? null)->get();
            $events = $events->filter(fn ($event) => Arr::get($event, 'alert.notifications.popup.active', true));
            $events = $this->transformerService
                ->setSerializer(new WithoutDataArraySerializer())
                ->collection($events, EventLatestTransformer::class)->toArray();
        } else {
            $events = [];
        }


        return [
            'items' => $items,
            'events' => $events,
            'time' => $now,
            'version' => Config::get('tobuli.version')
        ];
    }

    private function getDeviceModels(Device $item): array
    {
        if (!$this->user->can('view', $item, 'model_id')) {
            return [];
        }

        return DeviceModel::where(
            fn (Builder $query) => $query->where('active', 1)->orWhere('id', $item->model_id)
        )
            ->pluck('title', 'id')
            ->prepend(trans('front.none'), '')
            ->all();
    }

    private function usersReachedLimit($device = null)
    {
        if (empty($this->data['user_id'])) {
            return;
        }

        $users = $this->deviceUsersService->getUsersReachedLimit($this->data['user_id'], $device);

        if (!$users->isEmpty()) {
            throw new ValidationException(['user_id' => trans('validation.attributes.devices_limit') . ': ' . $users->implode('email', ', ')]);
        }
    }

    private function configureDevice(Device $device)
    {
        if (! $this->user->able('configure_device')) {
            throw new PermissionException(['id' => trans('front.dont_have_permission')]);
        }

        DeviceConfiguratorFormValidator::validate('configure', $this->data);

        $config = DeviceConfig::find($this->data['config_id']);

        $smsManager = new SMSGatewayManager();
        $gatewayArgs = settings('sms_gateway.use_as_system_gateway')
            ? ['request_method' => 'system']
            : null;

        $smsSenderService = $smsManager->loadSender($this->user, $gatewayArgs);
        $apnData = request()->all(['apn_name', 'apn_username', 'apn_password']);

        if ($this
            ->configService
            ->setSmsManager($smsSenderService)
            ->configureDevice($device->sim_number, $apnData, $config->commands)
        ) {
            return ['status' => 2];
        }

        throw new \Exception(trans('validation.cant_configure_device'));
    }

    private function setAbleUsers($device = null)
    {
        if (!$this->api) {
            unset($this->data['user_id']);
        }

        if (!(isAdmin() && $this->user->can('edit', new User()))){
            unset($this->data['user_id']);
        }

        if (is_null($device) && empty($this->data['user_id']) ) {
            $this->data['user_id'] = [$this->user->id];
        }

        if ($this->usersLoader->hasSelect()) {
            if ($device)
                $this->usersLoader->setQueryStored($device->users());
            $this->data['user_id'] = $this->usersLoader;
        } else {
            if ($users = $this->data['user_id'] ?? []) {
                $this->data['user_id'] = User::userAccessible($this->user)->whereIn('id', $users)->pluck('id')->all();
            }
        }
    }

    protected function setIconColors($item = null)
    {
        $icon_colors = $item
            ? $item->icon_colors + settings('device.status_colors.colors')
            : settings('device.status_colors.colors');

        foreach ($icon_colors as $status => $color) {
            $key = "icon_$status";

            if (array_key_exists($key, $this->data) && array_key_exists($this->data[$key], $this->device_icon_colors)) {
                $icon_colors[$status] = $this->data[$key];
            }
        }

        return $icon_colors;
    }

    protected function getDetectSensors($device)
    {
        $engine_hours = [
            'gps' => trans('front.gps')
        ];

        $engine = [
            'gps' => trans('front.gps')
        ];

        $speed = [
            'gps' => trans('front.gps')
        ];

        $distance = [
            'gps' => trans('front.gps')
        ];

        $sensors = $device->sensors()
            ->whereIn('type', ['acc', 'engine', 'ignition', 'engine_hours', 'odometer', 'speed_ecm'])
            ->get();

        foreach ($sensors as $sensor) {

            $title = trans('front.sensor') . ': ' . $sensor->type_title;

            switch ($sensor->type) {
                case 'acc':
                case 'engine':
                case 'ignition':
                    $engine_hours[$sensor->type] = $title;
                    $engine[$sensor->type] = $title;
                    break;
                case 'engine_hours':
                    $engine_hours[$sensor->type] = $title;
                    break;
                case 'odometer':
                    $distance[$sensor->type] = $title;
                    break;
                case 'speed_ecm':
                    $speed[$sensor->type] = $title;
                    break;
            }
        }

        return [
            'engine_hours' => $engine_hours,
            'engine'       => $engine,
            'distance'     => $distance,
            'speed'        => $speed,
        ];
    }
}