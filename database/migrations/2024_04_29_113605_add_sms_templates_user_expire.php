<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Tobuli\Entities\SmsTemplate;

class AddSmsTemplatesUserExpire extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SmsTemplate::unguard();

        SmsTemplate::updateOrCreate(['name' => 'expiring_user'], [
            'name'  => 'expiring_user',
            'title' => 'User expiration',
            'note'  => 'Hello,\r\n\r\nUser ([email]) is expiring in [days] days',
        ]);

        SmsTemplate::updateOrCreate(['name' => 'expired_user'], [
            'name'  => 'expired_user',
            'title' => 'User expired',
            'note'  => 'Hello,\r\n\r\nUser ([email]) expired before [days] days',
        ]);
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
