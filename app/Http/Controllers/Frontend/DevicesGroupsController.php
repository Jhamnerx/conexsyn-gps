<?php namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Transformers\ApiV1\AbstractGroupTransformer;
use CustomFacades\Repositories\DeviceGroupRepo;
use CustomFacades\Validators\DeviceGroupFormValidator;
use Tobuli\Entities\DeviceGroup;
use Tobuli\Entities\Forward;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\EntityLoader\UserDevicesGroupLoader;
use Tobuli\Services\EntityLoader\UserDevicesLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tobuli\Services\FractalSerializers\WithoutDataArraySerializer;


class DevicesGroupsController extends Controller
{
    public function index()
    {
        $this->checkException('devices_groups', 'view');

        $this->data['filter']['user_id'] = $this->user->id;

        $items = DeviceGroupRepo::searchAndPaginate($this->data, 'title', 'asc', $this->data['limit'] ?? 10);

        if ($this->api) {
            return \FractalTransformer::setSerializer(WithoutDataArraySerializer::class)
                ->paginate($items, AbstractGroupTransformer::class)
                ->toArray();
        }

        $data = [
            'devices_groups' => $items,
        ];

        return view('front::DevicesGroups.index')->with($data);
    }

    public function table()
    {
        $this->checkException('devices_groups', 'view');

        $this->data['filter']['user_id'] = $this->user->id;

        $data = [
            'devices_groups' => $item = DeviceGroupRepo::searchAndPaginate($this->data, 'title', 'asc', 10),
        ];

        return view('front::DevicesGroups.table')->with($data);
    }

    public function create()
    {
        $this->checkException('devices_groups', 'create');

        $data = [
            'devices' => [],
            'forwards' => Forward::userAccessible($this->user)->get()
        ];

        return view('front::DevicesGroups.create')->with($data);
    }

    public function store(Request $request)
    {
        $this->checkException('devices_groups', 'store');

        $data = array_merge($request->all(), ['user_id' => $this->user->id]);

        DeviceGroupFormValidator::validate('create', $data);

        $item = DeviceGroupRepo::create($data);

        if ($this->api)
            $this->syncDevicesAPI($item);
        else
            $this->syncDevices($item);

        if ($this->user->perm('forwards', 'view')) {
            $item->forwards()->sync($request->input('forwards', []));
        }

        return response()->json(['status' => 1, 'id' => $item->id]);
    }

    public function edit($id)
    {
        $item = DeviceGroupRepo::find($id);

        $this->checkException('devices_groups', 'edit', $item);

        $data = [
            'item'   => $item,
            'devices' => [],
            'forwards' => Forward::userAccessible($this->user)->get()
        ];

        return view('front::DevicesGroups.edit')->with($data);
    }

    public function update(Request $request, $id)
    {
        $item = DeviceGroupRepo::find($id);

        $this->checkException('devices_groups', 'update', $item);

        DeviceGroupFormValidator::validate('update', $request->all());

        $item->update($request->all());

        if ($this->user->perm('forwards', 'view') && $request->has('forwards')) {
            $forwards = $request->input('forwards');
            $forwards = empty($forwards) ? [] : $forwards;
            $item->forwards()->sync($forwards);
        }

        if ($this->api)
            $this->syncDevicesAPI($item);
        else
            $this->syncDevices($item);

        return response()->json(['status' => 1, 'id' => $item->id]);
    }

    public function devices($id = null)
    {
        $userDevicesLoader = new UserDevicesGroupLoader($this->user);
        $userDevicesLoader->setRequestKey('devices');

        if ($group = DeviceGroup::find($id))
            $userDevicesLoader->setQueryStored($group->devices());

        return response()->json($userDevicesLoader->get());
    }

    protected function syncDevices($group)
    {
        $userDevicesLoader = new UserDevicesGroupLoader($this->user);
        $userDevicesLoader->setQueryStored($group->devices());
        $userDevicesLoader->setRequestKey('devices');


        if ($userDevicesLoader->hasDetach()) {
            DB::table('user_device_pivot AS udp_update')
                ->join(DB::raw('(' . $userDevicesLoader->getQueryDetach()->toRaw() . ') AS selected_devices'), function ($join) {
                    $join->on('selected_devices.id', '=', 'udp_update.device_id');
                })
                ->where([
                    'udp_update.group_id' => $group->id,
                    'udp_update.user_id' => $this->user->id,
                ])
                ->update([
                    'group_id' => 0,
                ]);
        }

        if ($userDevicesLoader->hasAttach()) {
            DB::table('user_device_pivot AS udp_update')
                ->join(DB::raw('(' . $userDevicesLoader->getQueryAttach()->toRaw() . ') AS selected_devices'), function ($join) {
                    $join->on('selected_devices.id', '=', 'udp_update.device_id');
                })
                ->where([
                    'udp_update.user_id' => $this->user->id,
                ])
                ->update([
                    'group_id' => $group->id,
                ]);
        }
    }

    protected function syncDevicesAPI($group)
    {
        $devices = request()->input('devices', []);

        $devicesQuery = $this->user->devices()->whereIn('id', $devices);

        DB::table('user_device_pivot AS udp_update')
            ->leftJoin(DB::raw('(' . $devicesQuery->select('id')->toRaw(). ') AS selected_devices'), function($join) {
                $join->on('selected_devices.id', '=', 'udp_update.device_id');
            })
            ->where([
                'udp_update.group_id' => $group->id,
                'udp_update.user_id' => $this->user->id,
            ])
            ->whereNull('selected_devices.id')
            ->update([
                'group_id' => 0,
            ]);

        DB::table('user_device_pivot AS udp_update')
            ->join(DB::raw('(' . $devicesQuery->select('id')->toRaw(). ') AS selected_devices'), function($join) {
                $join->on('selected_devices.id', '=', 'udp_update.device_id');
            })
            ->whereNotNull('selected_devices.id')
            ->where([
                'udp_update.user_id' => $this->user->id,
            ])
            ->update([
                'group_id' => $group->id,
            ]);
    }

    public function doDestroy($id)
    {
        $item = DeviceGroupRepo::find($id);

        $this->checkException('devices_groups', 'remove', $item);

        $data = [
            'item' => $item,
        ];

        return view('front::DevicesGroups.destroy')->with($data);
    }

    public function destroy($id)
    {
        $item = DeviceGroupRepo::find($id);

        $this->checkException('devices_groups', 'remove', $item);

        DB::table('user_device_pivot')
            ->where([
                'group_id' => $item->id,
            ])
            ->update([
                'group_id' => 0,
            ]);

        $item->delete();

        return ['status' => 1];
    }
}
