<?php

use Illuminate\Database\Migrations\Migration;
use Tobuli\Entities\SmsTemplate;

class AddDeviceExpirationSmsTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SmsTemplate::unguard();

        SmsTemplate::updateOrCreate(['name' => 'expiring_device'], [
            'name'  => 'expiring_device',
            'title' => 'Device expiration',
            'note'  => 'Hello,\r\n\r\nDevice [device.name] (IMEI: [device.imei]) is expiring in [days] days',
        ]);

        SmsTemplate::updateOrCreate(['name' => 'expired_device'], [
            'name'  => 'expired_device',
            'title' => 'Device expired',
            'note'  => 'Hello,\r\n\r\nDevice [device.name] (IMEI: [device.imei]) expired before [days] days',
        ]);

        SmsTemplate::reguard();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
