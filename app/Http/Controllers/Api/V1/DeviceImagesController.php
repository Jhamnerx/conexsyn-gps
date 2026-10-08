<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ResourseNotFoundException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Tobuli\Entities\Device;
use Tobuli\Services\DeviceImageService;

class DeviceImagesController extends Controller
{
    public function show(int $id)
    {
        $device = Device::find($id);

        $this->checkException('devices', 'show', $device);

        $path = (new DeviceImageService($device))->get();

        if (!$path) {
            throw new ResourseNotFoundException('front.image');
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        return response($file)->header('Content-Type', $type);
    }
}
