<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForAllUserDevicesToAlerts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('alerts', 'for_all_user_devices')) {
            return;
        }

        Schema::table('alerts', function (Blueprint $table) {
            $table->boolean('for_all_user_devices')->default(false)->after('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('alerts', 'for_all_user_devices')) {
            return;
        }

        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn('for_all_user_devices');
        });
    }
}
