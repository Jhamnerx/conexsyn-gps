<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSenderToSendQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('send_queue', 'sender')) {
            return;
        }

        Schema::table('send_queue', function (Blueprint $table) {
            $table->string('sender')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('send_queue', 'sender')) {
            return;
        }

        Schema::table('send_queue', function (Blueprint $table) {
            $table->dropColumn('sender');
        });
    }
}
