<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnonLatLngToDeviceSensors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('device_sensors', 'data')) {
            return;
        }

        Schema::table('device_sensors', function (Blueprint $table) {
            $table->text('data')->nullable()->after('off_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('device_sensors', 'data')) {
            return;
        }

        Schema::table('device_sensors', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }
}
