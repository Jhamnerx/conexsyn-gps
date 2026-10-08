<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Traits\DatabaseRunChangesTrait;

class ChangeIndexesToUserDevicePivot extends Migration
{
    use DatabaseRunChangesTrait;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->dropForeignIfExists('user_device_pivot', 'current_driver_id');
        $this->dropForeignIfExists('user_device_pivot', 'timezone_id');
        $this->dropIndexIfExists('user_device_pivot', 'current_driver_id');
        $this->dropIndexIfExists('user_device_pivot', 'timezone_id');
        $this->dropIndexIfExists('user_device_pivot', 'active');

        $this->addIndexIfNotExists('user_device_pivot', ['user_id', 'active']);
        $this->addIndexIfNotExists('user_device_pivot', ['user_id', 'group_id']);
        $this->addIndexIfNotExists('user_device_pivot', ['device_id', 'group_id']);
        $this->addIndexIfNotExists('user_device_pivot', ['user_id', 'device_id']);

        $this->dropColumnIfExists('user_device_pivot', 'current_driver_id');
        $this->dropColumnIfExists('user_device_pivot', 'current_geofences');
        $this->dropColumnIfExists('user_device_pivot', 'current_events');
        $this->dropColumnIfExists('user_device_pivot', 'timezone_id');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            Schema::table('user_device_pivot', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'device_id']);
            });
        } catch (QueryException $e) {
            return;
        }

        $this->dropIndexIfExists('user_device_pivot', ['user_id', 'active']);
        $this->dropIndexIfExists('user_device_pivot', ['user_id', 'group_id']);
        $this->dropIndexIfExists('user_device_pivot', ['device_id', 'group_id']);

        Schema::table('user_device_pivot', function (Blueprint $table) {
            $table->integer('current_driver_id')->unsigned()->nullable()->index();
            $table->foreign('current_driver_id')->references('id')->on('user_drivers')->onDelete('set null');
            $table->integer('timezone_id')->unsigned()->nullable()->index();
            $table->foreign('timezone_id')->references('id')->on('timezones')->onDelete('set null');
            $table->text('current_geofences');
            $table->text('current_events');

            $table->index('active');
        });
    }
}
