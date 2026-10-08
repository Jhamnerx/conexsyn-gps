<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AlterUsersDatetimeFormatTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        if( ! Schema::hasColumn('users', 'date_format')) {
            Schema::table('users', function ($table) {
                $table->string('time_format', 32)->nullable()->after('unit_of_capacity');
                $table->string('date_format', 32)->nullable()->after('unit_of_capacity');
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
        Schema::table('users', function($table)
        {
            $table->dropColumn('time_format');
            $table->dropColumn('date_format');
        });
	}

}
