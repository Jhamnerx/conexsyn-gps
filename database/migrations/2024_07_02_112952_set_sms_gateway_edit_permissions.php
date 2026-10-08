<?php

use Illuminate\Database\Migrations\Migration;

class SetSmsGatewayEditPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('user_permissions')
            ->where('name', 'sms_gateway')
            ->where('view', 1)
            ->update(['edit' => 1]);

        DB::table('billing_plan_permissions')
            ->where('name', 'sms_gateway')
            ->where('view', 1)
            ->update(['edit' => 1]);

        $permission = settings('main_settings.user_permissions.sms_gateway');

        if ($permission && $permission['view']) {
            $permission['edit'] = 1;

            settings('main_settings.user_permissions.sms_gateway', $permission);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('user_permissions')
            ->where('name', 'sms_gateway')
            ->update(['edit' => 0]);

        DB::table('billing_plan_permissions')
            ->where('name', 'sms_gateway')
            ->update(['edit' => 0]);

        $permission = settings('main_settings.user_permissions.sms_gateway');

        if ($permission && $permission['view']) {
            $permission['edit'] = 0;

            settings('main_settings.user_permissions.sms_gateway', $permission);
        }
    }
}
