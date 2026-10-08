<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValueTimestampsToDeviceSensors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('device_sensors', 'value_set_at')) {
            return;
        }

        Schema::table('device_sensors', function (Blueprint $table) {
            $table->timestamp('value_set_at')->nullable();
            $table->timestamp('value_changed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('device_sensors', 'value_set_at')) {
            return;
        }

        Schema::table('device_sensors', function (Blueprint $table) {
            $table->dropColumn('value_set_at');
            $table->dropColumn('value_changed_at');
        });
    }
}
