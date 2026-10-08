<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Frontend\BaseController;
use App\Transformers\Device\DeviceListTransformer;
use Tobuli\Entities\User;

class ClientDevicesController extends BaseController
{
    public function index($id)
    {
        $user = User::find($id);

        $this->checkException('users', 'show', $user);

        $devices = $user
            ->devices()
            ->orderBy('imei')
            ->paginate($this->data['limit'] ?? null);

        return response()->json(array_merge(
            ['status' => 1],
            \FractalTransformer::paginate($devices, DeviceListTransformer::class)->toArray()
        ));
    }
}
