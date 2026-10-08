<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTemplateToPlansTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('device_plans', 'template')) {
            Schema::table('device_plans', function (Blueprint $table) {
                $table->text('template');
            });
        }

        if (!Schema::hasColumn('billing_plans', 'template')) {
            Schema::table('billing_plans', function (Blueprint $table) {
                $table->text('template');
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
        if (Schema::hasColumn('billing_plans', 'template')) {
            Schema::table('billing_plans', function (Blueprint $table) {
                $table->dropColumn('template');
            });
        }

        if (Schema::hasColumn('device_plans', 'template')) {
            Schema::table('device_plans', function (Blueprint $table) {
                $table->dropColumn('template');
            });
        }
    }
}
