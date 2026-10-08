<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReadonlyToUserSecondaryCredentials extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('user_secondary_credentials', 'readonly')) {
            return;
        }

        Schema::table('user_secondary_credentials', function (Blueprint $table) {
            $table->boolean('readonly')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('user_secondary_credentials', 'readonly')) {
            return;
        }

        Schema::table('user_secondary_credentials', function (Blueprint $table) {
            $table->dropColumn('readonly');
        });
    }
}
