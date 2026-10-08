<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Traits\DatabaseRunChangesTrait;

class SensorTableAddUnitType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('device_sensors', 'unit_type')) {
            Schema::table('device_sensors', function (Blueprint $table) {
                $table->string('unit_type',16)->nullable()->after('unit_of_measurement');
            });
        }
        if (!Schema::hasColumn('sensor_group_sensors', 'unit_type')) {
            Schema::table('sensor_group_sensors', function (Blueprint $table) {
                $table->string('unit_type',16)->nullable()->after('unit_of_measurement');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('device_sensors', function (Blueprint $table) {
            $table->dropColumn('unit_type');
        });

        Schema::table('sensor_group_sensors', function (Blueprint $table) {
            $table->dropColumn('unit_type');
        });
    }
}
