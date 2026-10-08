<?php namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Tobuli\Entities\Device;
use Tobuli\Services\SafeParkingService;

class SafeParkingController extends Controller
{
    /** @var SafeParkingService */
    protected $safeParkingService;

    protected function afterAuth($user)
    {
        $this->safeParkingService = new SafeParkingService();
    }

    /**
     * Estado actual del parqueo seguro de un device.
     * GET /api/safe_parking/{device_id}
     */
    public function status($deviceId)
    {
        $device = $this->findDevice($deviceId);

        return [
            'status'      => 1,
            'active'      => (bool) $device->safe_parking,
            'geofence_id' => $device->safe_parking_geofence_id,
            'alert_id'    => $device->safe_parking_alert_id,
        ];
    }

    /**
     * Activa / desactiva el parqueo seguro.
     * POST /api/safe_parking  { device_id, status?, radius? }
     *
     * - status = 1 fuerza activar, status = 0 fuerza desactivar.
     * - si no se envia status, alterna el estado actual.
     * - radius (metros) opcional al activar.
     */
    public function toggle($deviceId = null)
    {
        $deviceId = $deviceId ?: ($this->data['device_id'] ?? null);

        $device = $this->findDevice($deviceId);

        $activate = array_key_exists('status', $this->data)
            ? (bool) $this->data['status']
            : ! $device->safe_parking;

        if ($activate) {
            $radius = $this->data['radius'] ?? null;

            return $this->safeParkingService->activate($device, $this->user, $radius);
        }

        return $this->safeParkingService->deactivate($device, $this->user);
    }

    private function findDevice($deviceId): Device
    {
        $device = Device::find($deviceId);

        $this->checkException('devices', 'show', $device);

        return $device;
    }
}
