<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Builder;
use Tobuli\Entities\DeviceSensor;

class ConvertDeviceSensorsHexDecToBitcut extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $rules = DeviceSensor::where('on_tag_value', 'LIKE', '%SETFLAG%')
            ->where('off_tag_value', 'LIKE', '%SETFLAG%')
            ->where(function (Builder $query) {
                $query->where('hexbin', 1)
                    ->orWhere('decbin', 1);
            })
            ->groupBy(['decbin', 'hexbin', 'on_tag_value', 'off_tag_value'])
            ->select(['decbin', 'hexbin', 'on_tag_value', 'off_tag_value'])
            ->cursor();

        foreach ($rules as $rule) {
            $bitcut = $this->getBitcutValue($rule);

            if ($bitcut === null) {
                continue;
            }

            $onTagValue = $this->getTagValue($rule, 'on_tag_value');
            $offTagValue = $this->getTagValue($rule, 'off_tag_value');

            if ($onTagValue === null || $offTagValue === null) {
                continue;
            }

            DeviceSensor::where($rule->toArray())->update([
                'bitcut' => $bitcut,
                'on_tag_value' => $onTagValue,
                'off_tag_value' => $offTagValue,
            ]);
        }
    }

    private function getBitcutValue(DeviceSensor $sensor): ?string
    {
        switch (true) {
            case $sensor->decbin:
                $base = 10;
                break;
            case $sensor->hexbin:
                $base = 16;
                break;
            default:
                return null;
        }

        if (!preg_match('/\[(\d+),(\d+)/', $sensor->on_tag_value, $matches)) {
            return null;
        }

        return json_encode([
            'start' => $matches[1],
            'count' => $matches[2],
            'base'  => $base,
        ]);
    }

    private function getTagValue(DeviceSensor $sensor, string $property): ?int
    {
        return preg_match('/\[(\d+),(\d+),(\d+)]/', $sensor->$property, $matches)
            ? $matches[3]
            : null;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
