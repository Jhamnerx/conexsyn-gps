<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Traits\DatabaseRunChangesTrait;

class SensorTableAddAscii extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('device_sensors', 'ascii')) {
            Schema::table('device_sensors', function (Blueprint $table) {
                $table->boolean('ascii')->nullable()->after('bitcut');
            });
        }
        if (!Schema::hasColumn('sensor_group_sensors', 'ascii')) {
            Schema::table('sensor_group_sensors', function (Blueprint $table) {
                $table->boolean('ascii')->nullable()->after('bitcut');
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
            $table->dropColumn('ascii');
        });

        Schema::table('sensor_group_sensors', function (Blueprint $table) {
            $table->dropColumn('ascii');
        });
    }
}
