<?php

namespace Tobuli\Services;

use CustomFacades\ModalHelpers\SensorModalHelper;
use Illuminate\Database\Eloquent\Collection;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceSensor;
use Tobuli\Entities\DeviceType;
use Tobuli\Entities\SensorGroupSensor;
use Tobuli\Entities\User;

class DeviceSensorsService
{
    public function __construct()
    {

    }

    /**
     * @param Device $device
     * @param User $user
     * @param int $sensor_group_id
     */
    public function addSensorGroup(Device $device, User $user, int $sensor_group_id) {
        $group_sensors = SensorGroupSensor::where(['group_id' => $sensor_group_id])->get();

        if (empty($group_sensors)) {
            return;
        }

        foreach ($group_sensors as $sensor) {
            $sensor = $sensor->toArray();

            $this->addSensor($device, $user, $sensor);
        }
    }

    /**
     * @param Device $device
     * @param User $user
     * @param array $data
     */
    public function addSensor(Device $device, User $user, array $data)
    {
        //tmp
        if ( ! $data['show_in_popup']) {
            unset($data['show_in_popup']);
        }

        SensorModalHelper::setData(array_merge([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'sensor_type' => $data['type'],
            'sensor_name' => $data['name'],
        ], $data));

        SensorModalHelper::create();
    }

    /**
     * Devices asociados a un grupo de sensores a través de su tipo de
     * dispositivo (device_types.sensor_group_id).
     *
     * @param int $sensor_group_id
     * @return Collection|Device[]
     */
    public function devicesForSensorGroup(int $sensor_group_id): Collection
    {
        $typeIds = DeviceType::where('sensor_group_id', $sensor_group_id)->pluck('id');

        if ($typeIds->isEmpty()) {
            return new Collection();
        }

        return Device::whereIn('device_type_id', $typeIds)->get();
    }

    /**
     * Propaga un sensor recién agregado al grupo hacia los devices del grupo.
     *
     * @param SensorGroupSensor $sensor
     * @param User|null $actingUser usuario por defecto si el device no tiene dueño
     */
    public function syncSensorAddedToDevices(SensorGroupSensor $sensor, ?User $actingUser = null): void
    {
        $data = $sensor->toArray();

        foreach ($this->devicesForSensorGroup($sensor->group_id) as $device) {
            // Evitar duplicar si el device ya tiene ese sensor.
            if ($this->matchingDeviceSensors($device->id, $sensor->type, $sensor->name)->exists()) {
                continue;
            }

            $user = $this->resolveDeviceUser($device, $actingUser);

            if (! $user) {
                continue;
            }

            try {
                $this->addSensor($device, $user, $data);
            } catch (\Throwable $e) {
                // Un device con error no debe interrumpir el resto.
            }
        }
    }

    /**
     * Propaga la edición de un sensor del grupo hacia los devices del grupo.
     * Se busca por la identidad anterior (type|name) porque el nombre pudo cambiar.
     *
     * @param SensorGroupSensor $sensor   versión ya actualizada del sensor de grupo
     * @param array $previous             ['type' => ..., 'name' => ...] antes de editar
     * @param User|null $actingUser
     */
    public function syncSensorUpdatedOnDevices(SensorGroupSensor $sensor, array $previous, ?User $actingUser = null): void
    {
        $oldType = $previous['type'] ?? $sensor->type;
        $oldName = $previous['name'] ?? $sensor->name;

        $update = $this->copyableAttributes($sensor);

        foreach ($this->devicesForSensorGroup($sensor->group_id) as $device) {
            $matches = $this->matchingDeviceSensors($device->id, $oldType, $oldName)->get();

            if ($matches->isEmpty()) {
                // El device aún no tenía el sensor: lo creamos.
                $user = $this->resolveDeviceUser($device, $actingUser);

                if ($user) {
                    try {
                        $this->addSensor($device, $user, $sensor->toArray());
                    } catch (\Throwable $e) {
                    }
                }

                continue;
            }

            foreach ($matches as $deviceSensor) {
                try {
                    $deviceSensor->fill($update)->save();
                } catch (\Throwable $e) {
                }
            }
        }
    }

    /**
     * Quita de los devices del grupo el sensor eliminado del grupo.
     *
     * @param int $sensor_group_id
     * @param string $type
     * @param string $name
     */
    public function syncSensorRemovedFromDevices(int $sensor_group_id, string $type, string $name): void
    {
        foreach ($this->devicesForSensorGroup($sensor_group_id) as $device) {
            $sensors = $this->matchingDeviceSensors($device->id, $type, $name)->get();

            foreach ($sensors as $deviceSensor) {
                try {
                    $deviceSensor->delete();
                } catch (\Throwable $e) {
                }
            }
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function matchingDeviceSensors($device_id, $type, $name)
    {
        return DeviceSensor::where('device_id', $device_id)
            ->where('type', $type)
            ->where('name', $name);
    }

    protected function resolveDeviceUser(Device $device, ?User $fallback = null): ?User
    {
        $user = $device->users()->first();

        return $user ?: $fallback;
    }

    /**
     * Atributos "plantilla" copiables del sensor de grupo al sensor del device.
     * Se excluyen identidad/estado que no deben sobrescribirse en el device.
     */
    protected function copyableAttributes(SensorGroupSensor $sensor): array
    {
        $data = $sensor->toArray();

        // id/group_id: identidad del grupo.
        // odometer_value: su mutador en DeviceSensor sobreescribe el valor
        //   acumulado del odómetro del vehículo; no debe tocarse al editar.
        unset($data['id'], $data['group_id'], $data['odometer_value']);

        return $data;
    }
}
