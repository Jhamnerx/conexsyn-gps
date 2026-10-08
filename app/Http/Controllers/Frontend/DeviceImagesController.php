<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use CustomFacades\Validators\DeviceImageValidator;
use Tobuli\Entities\Device;
use Tobuli\Services\DeviceImageService;

class DeviceImagesController extends Controller
{
    public function store(int $id)
    {
        $device = Device::find($id);

        $this->checkException('devices', 'update', $device);

        DeviceImageValidator::validate('upload', $this->data);

        (new DeviceImageService($device))->save($this->data['image']);

        return ['status' => 1];
    }

    public function destroy(int $id)
    {
        $device = Device::find($id);

        $this->checkException('devices', 'update', $device);

        (new DeviceImageService($device))->delete();

        return ['status' => 1];
    }
}
