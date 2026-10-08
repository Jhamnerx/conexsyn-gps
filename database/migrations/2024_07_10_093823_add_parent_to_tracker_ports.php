<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentToTrackerPorts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('tracker_ports', 'parent')) {
            return;
        }

        Schema::table('tracker_ports', function (Blueprint $table) {
            $table->string('parent', 255)->nullable()->after('name');
            $table->string('name', 255)->change();

            $table->foreign('parent')->references('name')->on('tracker_ports')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('tracker_ports', 'parent')) {
            return;
        }

        Schema::table('tracker_ports', function (Blueprint $table) {
            $table->string('name', 50)->change();

            $table->dropForeign('tracker_ports_parent_foreign');
            $table->dropColumn('parent');
        });
    }
}
