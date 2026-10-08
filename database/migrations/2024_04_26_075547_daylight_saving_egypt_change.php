<?php

use Illuminate\Database\Migrations\Migration;

class DaylightSavingEgyptChange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table("timezones_dst")
            ->where('country', '=', 'Egypt')
            ->update([
                'from_period' => 'Last Friday of April'
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
