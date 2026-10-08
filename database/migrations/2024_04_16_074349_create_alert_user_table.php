<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAlertUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        if (!Schema::hasTable('alert_user')) {
            Schema::create('alert_user', function (Blueprint $table) {
                $table->integer('user_id')->unsigned()->index();
                $table->integer('alert_id')->unsigned()->index();
                $table->foreign('alert_id')->references('id')->on('alerts')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

                $table->primary(['user_id', 'alert_id']);
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
        Schema::dropIfExists('alert_user');
    }
}
