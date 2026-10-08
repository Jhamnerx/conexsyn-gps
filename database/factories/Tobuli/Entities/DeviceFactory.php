<?php

namespace Database\Factories\Tobuli\Entities;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tobuli\Entities\Device;
use Tobuli\Services\DeviceService;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    private static ?array $defaults = null;

    public function configure()
    {
        return $this->afterCreating(function (Device $device) {
            $time = date('Y-m-d H:i:s');

            $device->traccar->protocol = 'demo';
            $device->traccar->lastValidLatitude = $this->faker->latitude;
            $device->traccar->lastValidLongitude = $this->faker->longitude;
            $device->traccar->device_time = $time;
            $device->traccar->time = $time;
            $device->traccar->server_time = $time;

            if ($device->speed >= $device->min_moving_speed) {
                $device->traccar->moved_at = $time;
            } else {
                $device->traccar->stoped_at = $time;
            }

            $device->save();
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
                'name' => $this->faker->domainWord(),
                'plate_number' => $this->faker->bothify(),
                'imei' => $this->faker->imei,
            ] + self::getDefaults();
    }

    public static function getDefaults(): array
    {
        return self::$defaults ?? (self::$defaults = \Illuminate\Support\Arr::except(
            app()->make(DeviceService::class)->getDefaults(),
            ['group_id', 'device_icons_type']
        ));
    }
}
