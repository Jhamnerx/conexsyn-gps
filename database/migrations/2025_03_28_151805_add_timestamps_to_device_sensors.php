<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimestampsToDeviceSensors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('device_sensors', 'updated_at')) {
            return;
        }

        Schema::table('device_sensors', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('device_sensors', 'updated_at')) {
            return;
        }

        Schema::table('device_sensors', function (Blueprint $table) {
            $table->dropColumn('created_at', 'updated_at');
        });
    }
}
