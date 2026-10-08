<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUntouchableToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('users', 'untouchable')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('untouchable')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('users', 'untouchable')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('untouchable');
        });
    }
}
