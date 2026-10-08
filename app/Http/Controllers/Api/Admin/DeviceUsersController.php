<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Frontend\BaseController;
use App\Transformers\User\UserBasicTransformer;
use Tobuli\Entities\Device;

class DeviceUsersController extends BaseController
{
    public function index($id)
    {
        $device = Device::find($id);

        $this->checkException('devices', 'show', $device);

        $users = $device
            ->users()
            ->orderBy('email')
            ->paginate($this->data['limit'] ?? null);

        return response()->json(array_merge(
            ['status' => 1],
            \FractalTransformer::paginate($users, UserBasicTransformer::class)->toArray()
        ));
    }
}
