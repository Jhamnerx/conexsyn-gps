<?php

namespace Database\Factories\Tobuli\Entities;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tobuli\Entities\Route;

class RouteFactory extends Factory
{
    protected $model = Route::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $coords = [];
        $count = $this->faker->numberBetween(3, 7);

        for ($i = 0; $i < $count; $i++) {
            $coords[] = [
                'lat' => $this->faker->latitude,
                'lng' => $this->faker->longitude,
            ];
        }

        return [
            'active' => 1,
            'name' => $this->faker->title,
            'coordinates' => $coords,
            'color' => $this->faker->hexColor,
        ];
    }
}
