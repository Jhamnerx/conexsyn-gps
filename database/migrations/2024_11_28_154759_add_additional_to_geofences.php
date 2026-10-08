<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalToGeofences extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('geofences', 'additional')) {
            return;
        }

        Schema::table('geofences', function (Blueprint $table) {
            $table->text('additional')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('geofences', 'additional')) {
            return;
        }

        Schema::table('geofences', function (Blueprint $table) {
            $table->dropColumn('additional');
        });
    }
}
