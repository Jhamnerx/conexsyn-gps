<?php

namespace Tobuli\Services;

use Tobuli\Entities\CommandTemplate;
use Tobuli\Entities\Device;
use Tobuli\Entities\Geofence;
use Tobuli\Entities\User;
use Tobuli\Entities\UserGprsTemplate;
use Tobuli\Services\Commands\SendCommandService;

/**
 * Parqueo seguro.
 *
 * Al activar:
 *   1. Crea una geocerca circular alrededor de la ubicacion actual del vehiculo.
 *   2. Crea una alerta de "salida de geocerca" (geofence_out) para el device + geocerca.
 *   3. Si el device esta conectado y existe una plantilla GPRS marcada como corte de
 *      corriente (power_action = 'cut') adaptada al device, envia ese comando.
 *   4. Guarda el estado y los ids de geocerca/alerta en el device.
 *
 * Al desactivar:
 *   1. Envia (si existe/conectado) la plantilla de restablecer corriente (power_action = 'restore').
 *   2. Elimina la geocerca y la alerta creadas.
 *   3. Limpia el estado en el device.
 */
class SafeParkingService
{
    const DEFAULT_RADIUS = 100;      // metros
    const GEOFENCE_COLOR = '#FF0000';

    /** @var SendCommandService */
    private $sendCommandService;

    public function __construct(SendCommandService $sendCommandService = null)
    {
        $this->sendCommandService = $sendCommandService ?: new SendCommandService();
    }

    public function activate(Device $device, User $user, $radius = null): array
    {
        if ($device->safe_parking) {
            return [
                'status'  => 1,
                'active'  => true,
                'message' => trans('front.safe_parking_already_active'),
            ];
        }

        $lat = $device->latitude;
        $lng = $device->longitude;

        if (is_null($lat) || is_null($lng)) {
            return [
                'status' => 0,
                'active' => false,
                'error'  => trans('front.safe_parking_no_position'),
            ];
        }

        $radius = $radius ? (int) $radius : self::DEFAULT_RADIUS;

        beginTransaction();
        try {
            $geofence = $this->createGeofence($device, $user, $lat, $lng, $radius);
            $alert    = $this->createAlert($device, $user, $geofence);

            $device->safe_parking             = true;
            $device->safe_parking_geofence_id = $geofence->id;
            $device->safe_parking_alert_id    = $alert->id;
            $device->save();
        } catch (\Exception $e) {
            rollbackTransaction();
            throw $e;
        }
        commitTransaction();

        $command = $this->sendPowerCommand($device, $user, CommandTemplate::POWER_ACTION_CUT);

        return [
            'status'      => 1,
            'active'      => true,
            'message'     => trans('front.safe_parking_activated'),
            'geofence_id' => $geofence->id,
            'alert_id'    => $alert->id,
            'radius'      => $radius,
            'command'     => $command,
        ];
    }

    public function deactivate(Device $device, User $user): array
    {
        if (! $device->safe_parking) {
            return [
                'status'  => 1,
                'active'  => false,
                'message' => trans('front.safe_parking_not_active'),
            ];
        }

        // Se intenta restablecer corriente antes de limpiar la proteccion.
        $command = $this->sendPowerCommand($device, $user, CommandTemplate::POWER_ACTION_RESTORE);

        beginTransaction();
        try {
            $this->deleteGeofence($device->safe_parking_geofence_id);
            $this->deleteAlert($user, $device->safe_parking_alert_id);

            $device->safe_parking             = false;
            $device->safe_parking_geofence_id = null;
            $device->safe_parking_alert_id    = null;
            $device->save();
        } catch (\Exception $e) {
            rollbackTransaction();
            throw $e;
        }
        commitTransaction();

        return [
            'status'  => 1,
            'active'  => false,
            'message' => trans('front.safe_parking_deactivated'),
            'command' => $command,
        ];
    }

    private function createGeofence(Device $device, User $user, $lat, $lng, $radius): Geofence
    {
        return Geofence::create([
            'user_id'       => $user->id,
            'name'          => trans('front.safe_parking') . ' - ' . $device->name,
            'type'          => Geofence::TYPE_CIRCLE,
            'center'        => ['lat' => $lat, 'lng' => $lng],
            'radius'        => $radius,
            'polygon_color' => self::GEOFENCE_COLOR,
            'active'        => 1,
        ]);
    }

    private function createAlert(Device $device, User $user, Geofence $geofence)
    {
        $alert = $user->alerts()->create([
            'active'               => 1,
            'type'                 => 'geofence_out',
            'name'                 => trans('front.safe_parking') . ' - ' . $device->name,
            'for_all_user_devices' => 0,
            'notifications'        => [
                'push'  => ['active' => 1],
                'popup' => ['active' => 1],
            ],
        ]);

        $alert->devices()->sync([$device->id]);
        $alert->geofences()->sync([$geofence->id]);

        return $alert;
    }

    private function deleteGeofence($geofenceId): void
    {
        if (empty($geofenceId)) {
            return;
        }

        $geofence = Geofence::find($geofenceId);

        if ($geofence) {
            $geofence->delete();
        }
    }

    private function deleteAlert(User $user, $alertId): void
    {
        if (empty($alertId)) {
            return;
        }

        $alert = $user->alerts()->find($alertId);

        if (! $alert) {
            return;
        }

        $alert->devices()->detach();
        $alert->geofences()->detach();
        $alert->delete();
    }

    private function sendPowerCommand(Device $device, User $user, string $action): array
    {
        $template = $this->findPowerTemplate($device, $user, $action);

        if (! $template) {
            return [
                'sent'   => false,
                'reason' => 'no_template',
            ];
        }

        $result   = $this->sendCommandService->gprs($device, ['type' => 'template_' . $template->id], $user);
        $response = $result->first() ?: [];

        return [
            'sent'        => (($response['status'] ?? 0) == 1),
            'template_id' => $template->id,
            'status'      => $response['status'] ?? 0,
            'message'     => $response['error'] ?? ($response['message'] ?? null),
        ];
    }

    /**
     * Busca la plantilla GPRS del usuario/comun marcada con la accion de energia
     * pedida y que aplique al device (por protocolo / device / tipo de device).
     */
    private function findPowerTemplate(Device $device, User $user, string $action)
    {
        $templates = UserGprsTemplate::userAccessible($user)
            ->powerAction($action)
            ->orderByRaw('adapted IS NULL')   // las adaptadas (mas especificas) primero
            ->orderByRaw('user_id IS NULL')   // las del usuario antes que las comunes
            ->get();

        return $templates->first(function ($template) use ($device) {
            return $template->isAdaptedFromDevice($device);
        });
    }
}
