<?php

namespace Database\Factories\Tobuli\Entities;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tobuli\Entities\DeviceGroup;

class DeviceGroupFactory extends Factory
{
    protected $model = DeviceGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->title,
            'open' => $this->faker->boolean,
        ];
    }
}
