<?php

namespace App\Http\Controllers\Frontend;

use App\Exceptions\PermissionException;
use App\Http\Controllers\Controller;
use CustomFacades\ModalHelpers\SensorModalHelper;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Validators\BeaconFormValidator;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceGroup;
use Tobuli\Entities\DeviceIcon;
use Tobuli\Entities\SensorGroup;
use Tobuli\Entities\User;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\DeviceService;
use Tobuli\Services\DeviceUsersService;
use Tobuli\Services\EntityLoader\UsersLoader;

class BeaconsController extends Controller
{
    private array $device_icon_colors;
    private $deviceService;
    private $usersLoader;

    public function __construct(DeviceService $deviceService, DeviceUsersService $deviceUsersService)
    {
        if (!settings('plugins.beacons.status')) {
            abort(404);
        }

        parent::__construct();

        $this->deviceService = $deviceService;
        $this->deviceUsersService = $deviceUsersService;
        $this->device_icon_colors = [
            'green'  => trans('front.green'),
            'yellow' => trans('front.yellow'),
            'red'    => trans('front.red'),
            'blue'   => trans('front.blue'),
            'orange' => trans('front.orange'),
            'black'  => trans('front.black'),
        ];
    }

    protected function afterAuth($user)
    {
        $this->usersLoader = new UsersLoader($this->user);
        $this->usersLoader->setRequestKey('user_id');
    }

    private function getCreateData(): array
    {
        $icons_type = [
            'arrow' => trans('front.arrow'),
            'rotating' => trans('front.rotating_icon'),
            'icon' => trans('front.icon')
        ];

        $device_icon_colors = $this->device_icon_colors;

        $device_icons = DeviceIcon::whereNull('user_id')
            ->orWhere('user_id', $this->user->id)
            ->orderBy('order', 'desc')
            ->orderBy('id', 'ASC')
            ->get();

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

        $device_groups = DeviceGroup::where('user_id', $this->user->id)
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $sensor_groups = isAdmin()
            ? SensorGroup::orderBy('title', 'ASC')
                ->pluck('title', 'id')
                ->prepend(trans('front.none'), '0')
                ->all()
            : [];

        $users = [];
        if ($this->api) {
            $device_groups = apiArray($device_groups);
            $sensor_groups = apiArray($sensor_groups);
            $users = User::userAccessible($this->user)->orderBy('email', 'ASC')->toArray();
        }

        $item = new Device();
        $item->active = true;
        $item['icon'] = DeviceIcon::find(settings('device.icon_id'));
        $item->icon_id = $item->icon->id ?? 0;
        $item->tail_length = settings('device.tail.length');
        $item->tail_color = settings('device.tail.color');

        return compact(
            'item', 'device_groups', 'sensor_groups', 'users', 'icons_type', 'device_icons_grouped', 'device_icon_colors'
        );
    }

    public function create()
    {
        $this->checkException('devices', 'store');

        $data = $this->getCreateData();

        return $this->api ? $data : view('Frontend.Devices.create_beacon')->with($data);
    }

    public function store()
    {
        $this->checkException('devices', 'store');

        if ($this->user->perm('custom_device_add', 'view'))
            throw new PermissionException();

        $this->normalize();

        BeaconFormValidator::validate('create', $this->data);

        $this->setAbleUsers();
        $this->usersReachedLimit();
        
        $this->data['kind'] = Device::KIND_BEACON;

        return ['status' => 1, $this->deviceService->create($this->data)];
    }

    private function getEditData(Device $item): array
    {
        $group_id = $this->user->devices()->find($item->id)->pivot->group_id ?? null;

        $icons_type = [
            'arrow' => trans('front.arrow'),
            'rotating' => trans('front.rotating_icon'),
            'icon' => trans('front.icon')
        ];

        $device_icon_colors = $this->device_icon_colors;

        $device_icons = DeviceIcon::whereNull('user_id')
            ->orWhere('user_id', $this->user->id)
            ->orderBy('order', 'desc')
            ->orderBy('id', 'ASC')
            ->get();

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

        $device_groups = DeviceGroup::where('user_id', $this->user->id)
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $sensors = SensorModalHelper::paginated($item->id);

        $sensor_groups = isAdmin()
            ? SensorGroup::orderBy('title', 'ASC')
                ->pluck('title', 'id')
                ->prepend(trans('front.none'), '0')
                ->all()
            : [];

        $users = [];
        $sel_users = [];
        if ($this->api) {
            $device_groups = apiArray($device_groups);
            $users = UserRepo::getUsers($this->user)->toArray();
            $sel_users = $item->users->pluck('id', 'id')->all();
        }

        $device_id = $item->id;

        return compact(
            'item', 'device_id', 'device_groups', 'sensor_groups', 'device_icons', 'sensors', 'users', 'sel_users',
            'group_id', 'icons_type', 'device_icons_grouped', 'device_icon_colors'
        );
    }

    public function edit($id = null)
    {
        $beacon = Device::kindBeacon()->find($id);

        $this->checkException('devices', 'edit', $beacon);

        $data = $this->getEditData($beacon);

        return $this->api ? $data : view('Frontend.Devices.edit_beacon')->with($data);
    }

    public function update($id = null)
    {
        $beacon = Device::kindBeacon()->find($id);

        $this->checkException('devices', 'edit', $beacon);

        $this->normalize($beacon);

        BeaconFormValidator::validate('update', $this->data, $beacon->id);

        $this->setAbleUsers($beacon);
        $this->usersReachedLimit($beacon);

        $this->deviceService->update($beacon, $this->data);

        return ['status' => 1, 'id' => $beacon->id];
    }

    private function normalize(Device $item = null)
    {
        $this->data['group_id'] = empty($this->data['group_id']) ? null : $this->data['group_id'];

        $this->setIconColors($item);
    }

    private function setIconColors(Device $item = null): void
    {
        $this->data['icon_colors'] = $item
            ? $item->icon_colors + settings('device.status_colors.colors')
            : settings('device.status_colors.colors');

        foreach ($this->data['icon_colors'] as $status => $color) {
            $key = "icon_$status";

            if (array_key_exists($key, $this->data) && array_key_exists($this->data[$key], $this->device_icon_colors)) {
                $this->data['icon_colors'][$status] = $this->data[$key];
            }
        }
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
}
