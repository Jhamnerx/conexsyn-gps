<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Traits\DatabaseRunChangesTrait;

class SensorEngineHoursTagNameVirtual extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('device_sensors')
            ->where('type', 'engine_hours')
            ->where('tag_name', 'enginehours')
            ->where('shown_value_by', '!=', 'virtual')
            ->update(['shown_value_by' => 'virtual']);

        DB::table('sensor_group_sensors')
            ->where('type', 'engine_hours')
            ->where('tag_name', 'enginehours')
            ->where('shown_value_by', '!=', 'virtual')
            ->update(['shown_value_by' => 'virtual']);
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
