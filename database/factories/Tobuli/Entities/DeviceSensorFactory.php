<?php

namespace Database\Factories\Tobuli\Entities;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tobuli\Entities\DeviceSensor;

class DeviceSensorFactory extends Factory
{
    protected $model = DeviceSensor::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'add_to_history' => 0,
            'odometer_value_unit' => 'km',
            'value' => '-',
            'value_formula' => 0,
            'show_in_popup' => $this->faker->numberBetween(0, 1),
            'unit_of_measurement' => '',
        ];
    }

    public function acc()
    {
        return $this->state(fn () => [
            'tag_name' => 'acc',
            'on_value' => '1',
            'off_value' => '0',
            'type' => 'acc',
        ]);
    }

    public function accSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'acc',
            'on_value' => '1',
            'off_value' => '0',
            'type' => 'acc',
            'setflag' => 1,
            'on_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'on_setflag_2' => $setFlag + 1,
            'on_setflag_3' => $this->faker->numberBetween(0, 9),
            'off_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'off_setflag_2' => $setFlag + 1,
            'off_setflag_3' => $this->faker->numberBetween(0, 9),
        ]);
    }

    public function anonymizer()
    {
        return $this->state(fn () => [
            'tag_name' => 'anonymizer',
            'on_value' => '1',
            'off_value' => '0',
            'type' => 'anonymizer',
        ]);
    }

    public function anonymizerSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'anonymizer',
            'on_value' => '1',
            'off_value' => '0',
            'type' => 'anonymizer',
            'setflag' => 1,
            'on_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'on_setflag_2' => $setFlag + 1,
            'on_setflag_3' => $this->faker->numberBetween(0, 9),
            'off_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'off_setflag_2' => $setFlag + 1,
            'off_setflag_3' => $this->faker->numberBetween(0, 9),
        ]);
    }

    public function battery()
    {
        return $this->state(fn () => [
            'tag_name' => 'battery',
            'shown_value_by' => 'tag_value',
            'type' => 'battery',
        ]);
    }

    public function counter()
    {
        return $this->state(fn () => [
            'tag_name' => 'counter',
            'shown_value_by' => 'logical',
            'type' => 'counter',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'value' => '1',
        ]);
    }

    public function door()
    {
        return $this->state(fn () => [
            'tag_name' => 'door',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'door',
        ]);
    }

    public function doorSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'door',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'door',
            'setflag' => 1,
            'on_type_setflag' => 1,
            'on_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'on_setflag_2' => $setFlag + 1,
            'on_setflag_3' => $this->faker->numberBetween(0, 9),
            'off_type_setflag' => 1,
            'off_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'off_setflag_2' => $setFlag + 1,
            'off_setflag_3' => $this->faker->numberBetween(0, 9),
        ]);
    }

    public function driveBusiness()
    {
        return $this->state(fn () => [
            'tag_name' => 'business_drive',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'drive_business',
        ]);
    }

    public function driveBusinessSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'business_drive',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'drive_business',
            'setflag' => 1,
            'on_type_setflag' => 1,
            'on_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'on_setflag_2' => $setFlag + 1,
            'on_setflag_3' => $this->faker->numberBetween(0, 9),
            'off_type_setflag' => 1,
            'off_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'off_setflag_2' => $setFlag + 1,
            'off_setflag_3' => $this->faker->numberBetween(0, 9),
        ]);
    }

    public function drivePrivateSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'private_drive',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'drive_private',
            'setflag' => 1,
            'on_type_setflag' => 1,
            'on_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'on_setflag_2' => $setFlag + 1,
            'on_setflag_3' => $this->faker->numberBetween(0, 9),
            'off_type_setflag' => 1,
            'off_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'off_setflag_2' => $setFlag + 1,
            'off_setflag_3' => $this->faker->numberBetween(0, 9),
        ]);
    }

    public function engine()
    {
        return $this->state(fn () => [
            'tag_name' => 'engine',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'engine',
        ]);
    }

    public function engineSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'engine',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'engine',
            'setflag' => 1,
            'on_type_setflag' => 1,
            'on_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'on_setflag_2' => $setFlag + 1,
            'on_setflag_3' => $this->faker->numberBetween(0, 9),
            'off_type_setflag' => 1,
            'off_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'off_setflag_2' => $setFlag + 1,
            'off_setflag_3' => $this->faker->numberBetween(0, 9),
        ]);
    }

    public function engineHours()
    {
        return $this->state(fn () => [
            'tag_name' => 'engine_hours',
            'skip_empty' => null,
            'type' => 'engine_hours',
        ]);
    }

    public function fuelTank()
    {
        return $this->state(fn () => [
            'tag_name' => 'fuel',
            'fuel_tank_name' => $this->faker->title,
            'full_tank' => $this->faker->numberBetween(5, 15) * 10,
            'full_tank_value' => 'l',
            'formula' => '',
            'type' => 'fuel_tank',
        ]);
    }

    public function fuelTankCalibration()
    {
        return $this->state(fn () => [
            'tag_name' => 'fuel_with_calibration',
            'fuel_tank_name' => $this->faker->title,
            'calibrations' => $this->generateCalibrations(),
            'skip_calibration' => false,
            'formula' => '',
            'type' => 'fuel_tank_calibration',
        ]);
    }

    public function gsm()
    {
        return $this->state(fn () => [
            'tag_name' => 'gsm',
            'min_value' => $min = $this->faker->numberBetween(0, 10),
            'max_value' => $min + $this->faker->numberBetween(10, 30),
            'type' => 'gsm',
        ]);
    }

    public function harshAcceleration()
    {
        return $this->state(fn () => [
            'tag_name' => 'harsh_acceleration',
            'on_value' => $onValue = $this->faker->numberBetween(1, 10),
            'parameter_value' => $onValue,
            'type' => 'harsh_acceleration',
        ]);
    }

    public function harshAccelerationSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'harsh_acceleration',
            'on_value' => $onValue = $this->faker->numberBetween(1, 10),
            'parameter_value' => $onValue,
            'type' => 'harsh_acceleration',
            'set_flag' => 1,
            'value_setflag_1' => $this->faker->numberBetween(1, 3),
            'value_setflag_2' => $this->faker->numberBetween(1, 9),
        ]);
    }

    public function harshBreaking()
    {
        return $this->state(fn () => [
            'tag_name' => 'harsh_breaking',
            'on_value' => $onValue = $this->faker->numberBetween(1, 10),
            'parameter_value' => $onValue,
            'type' => 'harsh_breaking',
        ]);
    }

    public function harshBreakingSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'harsh_breaking',
            'on_value' => $onValue = $this->faker->numberBetween(1, 10),
            'parameter_value' => $onValue,
            'type' => 'harsh_breaking',
            'set_flag' => 1,
            'value_setflag_1' => $this->faker->numberBetween(1, 3),
            'value_setflag_2' => $this->faker->numberBetween(1, 9),
        ]);
    }

    public function harshTurning()
    {
        return $this->state(fn () => [
            'tag_name' => 'harsh_turning',
            'on_value' => $onValue = $this->faker->numberBetween(1, 10),
            'parameter_value' => $onValue,
            'type' => 'harsh_turning',
        ]);
    }

    public function harshTurningSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'harsh_turning',
            'on_value' => $onValue = $this->faker->numberBetween(1, 10),
            'parameter_value' => $onValue,
            'type' => 'harsh_turning',
            'set_flag' => 1,
            'value_setflag_1' => $this->faker->numberBetween(1, 3),
            'value_setflag_2' => $this->faker->numberBetween(1, 9),
        ]);
    }

    public function ignition()
    {
        return $this->state(fn () => [
            'tag_name' => 'ignition',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'ignition',
        ]);
    }

    public function ignitionSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'ignition',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'ignition',
            'setflag' => 1,
            'on_type_setflag' => 1,
            'on_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'on_setflag_2' => $setFlag + 1,
            'on_setflag_3' => $this->faker->numberBetween(0, 9),
            'off_type_setflag' => 1,
            'off_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'off_setflag_2' => $setFlag + 1,
            'off_setflag_3' => $this->faker->numberBetween(0, 9),
        ]);
    }

    public function load()
    {
        return $this->state(fn () => [
            'tag_name' => 'load',
            'formula' => '[value]',
            'type' => 'load',
        ]);
    }

    public function loadCalibration()
    {
        return $this->state(fn () => [
            'tag_name' => 'load',
            'formula' => '[value]',
            'type' => 'load_calibration',
            'calibrations' => $this->generateCalibrations(),
            'skip_calibration' => false,
        ]);
    }

    public function logical()
    {
        return $this->state(fn () => [
            'tag_name' => 'logical',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'logical',
        ]);
    }

    public function logicalSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'logical',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'logical',
            'setflag' => 1,
            'on_type_setflag' => 1,
            'on_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'on_setflag_2' => $setFlag + 1,
            'on_setflag_3' => $this->faker->numberBetween(0, 9),
            'off_type_setflag' => 1,
            'off_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'off_setflag_2' => $setFlag + 1,
            'off_setflag_3' => $this->faker->numberBetween(0, 9),
        ]);
    }

    public function numerical()
    {
        return $this->state(fn () => [
            'tag_name' => 'numerical',
            'formula' => '[value]',
            'type' => 'numerical',
        ]);
    }

    public function odometer()
    {
        return $this->state(fn () => [
            'tag_name' => 'odometer',
            'odometer_value_by' => 'connected_odometer',
            'type' => 'odometer',
        ]);
    }

    public function rfid()
    {
        return $this->state(fn () => [
            'tag_name' => 'rfid',
            'rfid_value_by' => 'connected_rfid',
            'type' => 'rfid',
        ]);
    }

    public function routeColor()
    {
        return $this->state(fn () => [
            'tag_name' => 'route_color',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'route_color',
        ]);
    }

    public function routeColorSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'route_color',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'route_color',
            'setflag' => 1,
            'on_type_setflag' => 1,
            'on_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'on_setflag_2' => $setFlag + 1,
            'on_setflag_3' => $this->faker->numberBetween(0, 9),
            'off_type_setflag' => 1,
            'off_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'off_setflag_2' => $setFlag + 1,
            'off_setflag_3' => $this->faker->numberBetween(0, 9),
        ]);
    }

    public function satellites()
    {
        return $this->state(fn () => [
            'tag_name' => 'satellites',
            'type' => 'satellites',
        ]);
    }

    public function seatbelt()
    {
        return $this->state(fn () => [
            'tag_name' => 'seatbelt',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'seatbelt',
        ]);
    }

    public function seatbeltSetFlag()
    {
        return $this->state(fn () => [
            'tag_name' => 'seatbelt',
            'on_tag_value' => '1',
            'off_tag_value' => '0',
            'on_type' => '1',
            'off_type' => '0',
            'type' => 'seatbelt',
            'setflag' => 1,
            'on_type_setflag' => 1,
            'on_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'on_setflag_2' => $setFlag + 1,
            'on_setflag_3' => $this->faker->numberBetween(0, 9),
            'off_type_setflag' => 1,
            'off_setflag_1' => $setFlag = $this->faker->numberBetween(1, 3),
            'off_setflag_2' => $setFlag + 1,
            'off_setflag_3' => $this->faker->numberBetween(0, 9),
        ]);
    }

    public function speedEcm()
    {
        return $this->state(fn () => [
            'tag_name' => 'speed_ecm',
            'formula' => '[value]',
            'type' => 'speed_ecm',
        ]);
    }

    public function tachometer()
    {
        return $this->state(fn () => [
            'tag_name' => 'tachometer',
            'formula' => '[value]',
            'type' => 'tachometer',
        ]);
    }

    public function temperature()
    {
        return $this->state(fn () => [
            'tag_name' => 'temperature',
            'formula' => '[value]',
            'type' => 'temperature',
        ]);
    }

    public function temperatureCalibration()
    {
        return $this->state(fn () => [
            'tag_name' => 'temperature_calibration',
            'calibrations' => $this->generateCalibrations(),
            'skip_calibration' => false,
            'formula' => '[value]',
            'type' => 'temperature_calibration',
        ]);
    }

    public function textual()
    {
        return $this->state(fn () => [
            'tag_name' => 'textual',
            'type' => 'textual',
        ]);
    }

    private function generateCalibrations(): array
    {
        $calibrations = [];
        $prevCalibration = 0;

        for ($i = 0, $iMax = $this->faker->numberBetween(1, 5); $i < $iMax; $i++) {
            $calibration = $this->faker->numberBetween(1, 5) + $prevCalibration;

            $calibrations[$calibration] = $calibration;
            $prevCalibration = $calibration;
        }

        return $calibrations;
    }
}
