<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDeviceSensorResetBlank extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if( ! Schema::hasColumn('device_sensors', 'reset_blank')) {
            Schema::table('device_sensors', function ($table) {
                $table->boolean('reset_blank')->nullable();
            });
        }

        if( ! Schema::hasColumn('sensor_group_sensors', 'reset_blank')) {
            Schema::table('sensor_group_sensors', function ($table) {
                $table->boolean('reset_blank')->nullable();
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
        Schema::table('device_sensors', function($table) {
            $table->dropColumn('reset_blank');
        });

        Schema::table('sensor_group_sensors', function($table) {
            $table->dropColumn('reset_blank');
        });
    }
}