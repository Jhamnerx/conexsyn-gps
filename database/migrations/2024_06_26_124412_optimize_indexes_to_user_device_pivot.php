<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Traits\DatabaseRunChangesTrait;

class OptimizeIndexesToUserDevicePivot extends Migration
{
    use DatabaseRunChangesTrait;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->addIndexIfNotExists('user_device_pivot', ['user_id', 'device_id', 'group_id']);
        $this->addIndexIfNotExists('user_device_pivot', ['user_id', 'device_id', 'group_id', 'active']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropIndexIfExists('user_device_pivot', ['user_id', 'device_id', 'group_id']);
        $this->dropIndexIfExists('user_device_pivot', ['user_id', 'device_id', 'group_id', 'active']);
    }
}
