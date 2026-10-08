<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Entities\DeviceService;

class AlterDeviceServicesDatesToDatetimes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('device_services', function (Blueprint $table) {
            $table->dateTime('expires_date')->nullable()->change();
            $table->dateTime('remind_date')->nullable()->change();
        });

        DeviceService::join('users', 'users.id', '=', 'device_services.user_id')
            ->join('timezones', 'users.timezone_id', '=', 'timezones.id')
            ->where('expiration_by', 'days')
            ->whereNotNull('expires_date')
            ->update(['device_services.expires_date' => DB::raw('IF(timezones.prefix = \'plus\',
                device_services.expires_date - INTERVAL timezones.time HOUR_MINUTE,
                device_services.expires_date + INTERVAL timezones.time HOUR_MINUTE)')
            ]);

        DeviceService::join('users', 'users.id', '=', 'device_services.user_id')
            ->join('timezones', 'users.timezone_id', '=', 'timezones.id')
            ->whereNotNull('remind_date')
            ->update(['device_services.remind_date' => DB::raw('IF(timezones.prefix = \'plus\',
                device_services.remind_date - INTERVAL timezones.time HOUR_MINUTE,
                device_services.remind_date + INTERVAL timezones.time HOUR_MINUTE)')
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('device_services', function (Blueprint $table) {
            $table->date('expires_date')->nullable()->change();
            $table->date('remind_date')->nullable()->change();
        });
    }
}
