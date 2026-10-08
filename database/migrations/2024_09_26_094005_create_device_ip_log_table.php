<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceIpLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('device_ip_log')) { return; }

        Schema::create('device_ip_log', function (Blueprint $table) {
            $table->string('imei')->index();
            $table->string('ip')->index()->nullable();

            $table->unique(['imei', 'ip']);
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
        Schema::dropIfExists('device_ip_log');
    }
}
