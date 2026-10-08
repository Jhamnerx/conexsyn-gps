<?php

namespace Database\Factories\Tobuli\Entities;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tobuli\Entities\DeviceModel;

class DeviceModelFactory extends Factory
{
    private const TEST_VALUES = ['demo', 'test'];

    protected $model = DeviceModel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'active' => true,
            'title' => $this->faker->word,
            'model' => $this->faker->randomElement(self::TEST_VALUES),
            'protocol' => $this->faker->randomElement(self::TEST_VALUES),
        ];
    }
}
