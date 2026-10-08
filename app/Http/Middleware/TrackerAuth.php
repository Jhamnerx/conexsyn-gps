<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tobuli\Entities\Device;

class TrackerAuth
{

    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(\Illuminate\Contracts\Auth\Factory $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $type)
    {
        if ($type === 'token') {
            $device = $this->auth->guard('api-tracker')->check()
                ? $this->auth->guard('api-tracker')->user()
                : null;
        } else {
            $imei = $request->get('imei');
            $device = $imei
                ? Device::where('imei', $imei)->first()
                : null;

        }

        if (empty($device)) {
            return response()->json(['success' => false, 'message' => trans('front.login_failed')], 401);
        }

        if (!$this->checkProtocol($device)) {
            return response()->json(['success' => false, 'message' => trans('front.login_failed')], 401);
        }

        if (!$this->checkAppUuid($device, $request->header('app-uuid'))) {
            return response()->json(['success' => false, 'message' => trans('front.wrong_device_app_uuid')], 401);
        }

        \app()->instance(Device::class, $device);

        return $next($request);
    }

    private function checkProtocol(Device $device)
    {
        $trackerAppLoginEnabled = config('addon.device_tracker_app_login') && $device->app_tracker_login;

        if (!$trackerAppLoginEnabled && ($device->protocol && $device->protocol != 'osmand')) {
            return false;
        }

        return true;
    }

    private function checkAppUuid(Device $device, $appUuid): bool
    {
        if (!config('addon.device_app_single_usage')) {
            return true;
        }

        if (empty($appUuid)) {
            return false;
        }

        if ($device->app_uuid && $device->app_uuid !== $appUuid) {
            return false;
        }

        if (!$device->app_uuid) {
            $device->app_uuid = $appUuid;
            $device->save();
        }

        return true;
    }
}