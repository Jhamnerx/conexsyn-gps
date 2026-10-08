<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\DeviceLimitException;
use App\Exceptions\PermissionException;
use CustomFacades\ModalHelpers\DeviceModalHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Tobuli\Entities\UnregisteredDevice;
use Tobuli\Services\DeviceUsersService;

class UnregisteredDevicesLogController extends BaseController
{
    public function index()
    {
        $items = UnregisteredDevice::orderBy('date', 'desc')->paginate(50);

        return view('admin::UnregisteredDevicesLog.' . (Request::ajax() ? 'table' : 'index'))->with(compact('items'));
    }

    public function create()
    {
        $data = DeviceModalHelper::createData();
        $data['imeis'] = UnregisteredDevice::whereIn('imei', (array)Request::input('id'))->pluck('imei')->all();
        $data['multiItems'] = count($data['imeis']) > 1;

        return view('Frontend.Devices.create_unregistered')->with($data);
    }

    public function store()
    {
        $this->checkException('devices', 'store');

        DeviceModalHelper::createUnregistered();

        return ['status' => 1];
    }

    public function destroy()
    {
        $id = Request::input('id');

        $ids = is_array($id) ? $id : [$id];

        UnregisteredDevice::whereIn('imei', $ids)->delete();

        return ['status' => 1];
    }
}
