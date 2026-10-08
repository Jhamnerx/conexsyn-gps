<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaxSpeedToDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('devices', 'max_speed')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedInteger('max_speed')->nullable()->after('valid_by_avg_speed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('devices', 'max_speed')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('max_speed');
        });
    }
}
