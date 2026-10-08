<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Entities\DeviceService;

class AlterDeviceServicesUserIdOnCascadeNull extends Migration
{
    use \Tobuli\Traits\DatabaseRunChangesTrait;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->dropForeignIfExists('device_services', 'user_id');

        Schema::table('device_services', function (Blueprint $table) {
            $table->integer('user_id')->unsigned()->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        DeviceService::leftJoin('users', 'device_services.user_id', '=', 'users.id')
            ->whereNull('users.id')
            ->update(['device_services.user_id' => null]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropForeignIfExists('device_services', 'user_id');

        Schema::table('device_services', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
}
