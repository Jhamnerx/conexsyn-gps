<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('task_sets')) {
            Schema::create('task_sets', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->index();
                $table->string('title');
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('tasks', 'task_set_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->foreignId('task_set_id')
                    ->after('user_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('task_sets')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['task_set_id']);
            $table->dropColumn('task_set_id');
        });

        Schema::dropIfExists('task_sets');
    }
}
