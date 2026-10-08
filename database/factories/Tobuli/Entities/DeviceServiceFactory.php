<?php

namespace Database\Factories\Tobuli\Entities;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Tobuli\Entities\DeviceService;

class DeviceServiceFactory extends Factory
{
    protected $model = DeviceService::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->title,
            'renew_after_expiration' => 0,
            'trigger_event_left' => 1,
            'expires_date' => null,
            'remind_date' => null,
            'event_sent' => 0,
            'expired' => 0,
            'email' => '',
            'mobile_phone' => '',
            'description' => '',
        ];
    }

    public function days()
    {
        return $this->state(function () {
            $interval = $this->faker->numberBetween(50, 500);

            $lastService = $this->faker
                ->dateTimeBetween('-30 days', 'yesterday')
                ->format('Y-m-d');

            $expires = Carbon::parse($lastService)->addDays($interval);

            $remind = $expires->copy()
                ->subDay()
                ->toDateString();

            $expires = $expires->toDateString();

            return [
                'expires_date' => $expires,
                'remind_date' => $remind,
                'expiration_by' => 'days',
                'interval' => $interval,
                'last_service' => $lastService,
            ];
        });
    }

    public function odometer()
    {
        return $this->byTagValue('odometer');
    }

    public function engineHours()
    {
        return $this->byTagValue('engine_hours');
    }

    private function byTagValue(string $tag)
    {
        return $this->state(fn () => [
            'interval' => $interval = $this->faker->numberBetween(50, 500),
            'last_service' => $lastService = $this->faker->numberBetween(50, 500),
            'expires' => $interval + $lastService,
            'remind' => $interval + $lastService - 1,
            'expiration_by' => $tag,
        ]);
    }
}
