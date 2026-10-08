<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeviceAdvancedFieldsPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permissionService = new \Tobuli\Services\PermissionService();

        $permissionService->addToAll("device.device_model");
        $permissionService->addToAll("device.plate_number");
        $permissionService->addToAll("device.registration_number");
        $permissionService->addToAll("device.object_owner");
        $permissionService->addToAll("device.vin");
        $permissionService->addToAll("device.additional_notes");
        $permissionService->addToAll("device.comment");
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
