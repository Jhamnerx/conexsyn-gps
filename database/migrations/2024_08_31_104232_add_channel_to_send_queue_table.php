<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChannelToSendQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('send_queue', 'channel')) {
            return;
        }

        Schema::table('send_queue', function (Blueprint $table) {
            $table->dropColumn('channels');
            $table->string('channel')->nullable()->after('data_type')->index();
            $table->text('channel_data')->nullable()->after('data_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('send_queue', 'channel')) {
            return;
        }

        Schema::table('send_queue', function (Blueprint $table) {
            $table->text('channels')->nullable()->after('data_type');
            $table->dropColumn('channel');
            $table->dropColumn('channel_data');
        });
    }
}
