<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskSetLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('task_set_locations')) {
            return;
        }

        Schema::create('task_set_locations', function (Blueprint $table) {
            $table->id();
            $table->integer('order')->nullable();

            $table->double('lat')->index();
            $table->double('lng')->index();
            $table->dateTime('time_from')->nullable()->index();
            $table->dateTime('time_to')->nullable()->index();

            $table->foreignId('task_set_id')
                    ->references('id')
                ->on('task_sets')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_set_locations');
    }
}
