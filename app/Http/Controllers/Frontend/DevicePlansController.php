<?php

namespace App\Http\Controllers\Frontend;

use App\Exceptions\PermissionException;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Tobuli\Entities\Device;
use Tobuli\Entities\DevicePlan;

class DevicePlansController extends Controller
{
    public function __construct()
    {
        if (! settings('main_settings.enable_device_plans') ?? false) {
            throw new PermissionException();
         }

         parent::__construct();
    }

    public function index($device_id = null)
    {
        $devices = $this->user->devices;

        $device = $this->user->devices()->find($device_id);

        if (empty($device) && $devices)
            $device = $devices->first();

        $plans = $plans = $this->groupPlans(
            $device
                ? DevicePlan::active()->forDevice($device)->orderBy('price')->get()
                : DevicePlan::active()->orderBy('price')->get()
        );

        $devicesAttributes = $devices
            ->keyBy('id')
            ->map(fn (Device $device) => ['data-subtext' => '<span class="text-' . ($device->isExpired() ? 'danger' : 'success')
                . '">' . $device->expiration_date . '</span>'])
            ->all();

        return view('front::DevicePlans.index', [
            'plans'                 => $plans,
            'devices'               => $devices->pluck('name', 'id'),
            'devices_attributes'    => $devicesAttributes,
            'device_id'             => $device->id ?? null
        ]);
    }

    public function plans($device_id)
    {
        $device = $this->user->devices()->find($device_id);

        $this->checkException('devices', 'view', $device);

        $plans = $this->groupPlans(
            DevicePlan::active()->forDevice($device)->orderBy('price')->get()
        );

        return view('front::DevicePlans.plans', [
            'plans'             => $plans,
            'device_id'         => $device->id,
        ]);
    }

    private function groupPlans(Collection $plans)
    {
        if (settings('main_settings.group_device_plans')) {
            return $plans->groupBy('duration_type');
        } else {
            return new Collection(['all' => $plans]);
        }
    }
}
