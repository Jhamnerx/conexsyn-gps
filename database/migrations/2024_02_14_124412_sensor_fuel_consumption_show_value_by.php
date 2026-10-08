<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Traits\DatabaseRunChangesTrait;

class SensorFuelConsumptionShowValueBy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('device_sensors')
            ->where('type', 'fuel_consumption')
            ->whereNull('shown_value_by')
            ->update(['shown_value_by' => 'incremental']);

        DB::table('sensor_group_sensors')
            ->where('type', 'fuel_consumption')
            ->whereNull('shown_value_by')
            ->update(['shown_value_by' => 'incremental']);
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
