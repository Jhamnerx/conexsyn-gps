<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Entities\SmsTemplate;
use Tobuli\Entities\User;

class AddPhoneVerifiedAtToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('users', 'phone_verified_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('phone_verified_at')->nullable()->after('email_verified_at');
        });

        User::query()->update(['phone_verified_at' => date('Y-m-d H:i:s')]);

        SmsTemplate::unguard();

        SmsTemplate::updateOrCreate(['name' => 'phone_verification'], [
            'name' => 'phone_verification',
            'title' => 'Phone verification',
            'note' => 'Verification link: [link]'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('users', 'phone_verified_at')) {
            return;
        }

        SmsTemplate::where('name', 'phone_verification')->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_verified_at');
        });
    }
}
