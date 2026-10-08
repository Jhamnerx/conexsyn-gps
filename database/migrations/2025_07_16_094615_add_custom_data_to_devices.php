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
        if (Schema::hasColumn('devices', 'custom_data')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->text('custom_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('devices', 'custom_data')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('custom_data');
        });
    }
};
