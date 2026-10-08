<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAlertDeviceSilencedAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('alert_device', 'silenced_at'))
            return;

        Schema::table('alert_device', function ($table) {
            $table->dateTime('silenced_at')->after('fired_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('alert_device', function($table) {
            $table->dropColumn('silenced_at');
        });
    }
}