<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Entities\EmailTemplate;

class CreatePasswordResetCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('password_reset_codes')) {
            Schema::create('password_reset_codes', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('code');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (Schema::hasTable('email_templates')) {
            EmailTemplate::unguard();

            EmailTemplate::updateOrCreate(['name' => 'reset_password_code'], [
                'name'  => 'reset_password_code',
                'title' => 'Reset password code',
                'note'  => 'You are receiving this email because we received a password reset code request for your account.'
                    . '<br><br>[code]<br><br>If you did not request a password reset, no further action is required.',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('password_reset_codes');

        if (Schema::hasTable('email_templates')) {
            EmailTemplate::where('name', 'reset_password_code')->delete();
        }
    }
}
