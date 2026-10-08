<?php

namespace Database\Factories\Tobuli\Entities;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tobuli\Entities\Poi;

class PoiFactory extends Factory
{
    protected $model = Poi::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'map_icon_id' => 1,
            'active' => 1,
            'name' => $this->faker->title,
            'description' => $this->faker->text,
            'coordinates' => [
                'lat' => $this->faker->latitude,
                'lng' => $this->faker->longitude,
            ],
        ];
    }
}
