<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('devices', 'fuel_emissions')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->string('fuel_type')->nullable();
            $table->unsignedFloat('fuel_emissions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('devices', 'fuel_emissions')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('fuel_type');
            $table->dropColumn('fuel_emissions');
        });
    }
};
