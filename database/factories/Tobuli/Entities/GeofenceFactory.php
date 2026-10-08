<?php

namespace Database\Factories\Tobuli\Entities;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tobuli\Entities\Geofence;

class GeofenceFactory extends Factory
{
    protected $model = Geofence::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'active' => 1,
            'polygon_color' => $this->faker->safeHexColor,
            'speed_limit' => rand(1, 100),
        ];
    }

    public function polygon()
    {
        return $this->state(fn () => [
            'type' => Geofence::TYPE_POLYGON,
            'polygon' => [
                ['lat' => $lat = $this->faker->latitude(-85, 85), 'lng' => $lng = $this->faker->longitude(-175, 175)],
                ['lat' => $lat + rand(1, 5), 'lng' => $lng + rand(1, 5)],
                ['lat' => $lat - rand(1, 5), 'lng' => $lat - rand(1, 5)],
            ],
        ]);
    }

    public function circle()
    {
        return $this->state(fn () => [
            'type' => Geofence::TYPE_CIRCLE,
            'radius' => $this->faker->numberBetween(),
            'center' => [
                'lat' => $this->faker->latitude,
                'lng' => $this->faker->longitude
            ],
        ]);
    }
}
