<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnlyOneSessionToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('users', 'only_one_session')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('only_one_session')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('users', 'only_one_session')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('only_one_session');
        });
    }
}
