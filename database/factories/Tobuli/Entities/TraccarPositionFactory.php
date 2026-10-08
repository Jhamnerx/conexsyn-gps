<?php

namespace Database\Factories\Tobuli\Entities;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tobuli\Entities\TraccarDevice;
use Tobuli\Entities\TraccarPosition;

class TraccarPositionFactory extends Factory
{
    protected $model = TraccarPosition::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $time = \Carbon::now();

        return [
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'other' => '<info></info>',
            'speed' => $this->faker->numberBetween(0, 150),
            'altitude' => $this->faker->numberBetween(0, 500),
            'course' => $this->faker->numberBetween(0, 359),
            'time' => $time,
            'device_time' => $time,
            'server_time' => $time,
            'protocol' => 'gt06',
            'valid' => true
        ];
    }

    public function configure()
    {
        return $this
            ->afterMaking(function (TraccarPosition $position) {
            $position->setTable('positions_' . $position->device_id);
        })
            ->afterCreating(function (TraccarPosition $position) {
            TraccarDevice::where('id', $position->device_id)->update([
                'lastValidLatitude' => $position->latitude,
                'lastValidLongitude' => $position->longitude,
                'server_time' => $position->server_time,
                'ack_time' => $position->server_time,
            ]);
        });
    }
}
