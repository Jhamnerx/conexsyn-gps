<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSafeParkingToDevicesTable extends Migration
{
    public function up()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->boolean('safe_parking')->default(0)->after('active');
            $table->unsignedInteger('safe_parking_geofence_id')->nullable()->after('safe_parking');
            $table->unsignedInteger('safe_parking_alert_id')->nullable()->after('safe_parking_geofence_id');
        });
    }

    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['safe_parking', 'safe_parking_geofence_id', 'safe_parking_alert_id']);
        });
    }
}
