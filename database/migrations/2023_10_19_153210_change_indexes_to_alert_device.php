<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Traits\DatabaseRunChangesTrait;

class ChangeIndexesToAlertDevice extends Migration
{
    use DatabaseRunChangesTrait;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->addIndexIfNotExists('alert_device', ['device_id', 'alert_id']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropIndexIfExists('alert_device', ['device_id', 'alert_id']);
    }
}
