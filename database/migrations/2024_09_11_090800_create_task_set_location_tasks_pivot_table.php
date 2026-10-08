<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskSetLocationTasksPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('task_set_location_tasks_pivot')) {
            return;
        }

        Schema::create('task_set_location_tasks_pivot', function (Blueprint $table) {
            $table->unsignedInteger('task_id');
            $table->unsignedBigInteger('task_set_location_id');
            $table->integer('task_order');
            $table->string('address_key');

            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->cascadeOnDelete();

            $table->foreign('task_set_location_id')
                ->references('id')
                ->on('task_set_locations')
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
        Schema::dropIfExists('task_set_location_tasks_pivot');
    }
}
