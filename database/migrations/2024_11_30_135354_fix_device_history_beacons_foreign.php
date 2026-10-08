<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixDeviceHistoryBeaconsForeign extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('device_history_beacons_pivot', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');

            $table->dropForeign(['beacon_id']);
            $table->foreign('beacon_id')->references('id')->on('devices')->onDelete('cascade');
        });
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
