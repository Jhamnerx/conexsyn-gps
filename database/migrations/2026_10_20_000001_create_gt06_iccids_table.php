<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGt06IccidsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('gt06_iccids')) {
            return;
        }

        Schema::create('gt06_iccids', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('imei', 20)->unique();
            $table->string('iccid', 22);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gt06_iccids');
    }
}
