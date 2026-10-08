<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusAutoChangeFieldsToTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('tasks', 'pickup_ac')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('delivery_ac_finished_at')->nullable()->after('delivery_time_to');
            $table->timestamp('delivery_ac_started_at')->nullable()->after('delivery_time_to');
            $table->integer('delivery_ac_status')->nullable()->after('delivery_time_to');
            $table->unsignedFloat('delivery_ac_duration')->nullable()->after('delivery_time_to');
            $table->unsignedFloat('delivery_ac_radius')->nullable()->after('delivery_time_to');
            $table->boolean('delivery_ac')->default(false)->after('delivery_time_to');

            $table->timestamp('pickup_ac_finished_at')->nullable()->after('pickup_time_to');
            $table->timestamp('pickup_ac_started_at')->nullable()->after('pickup_time_to');
            $table->integer('pickup_ac_status')->nullable()->after('pickup_time_to');
            $table->unsignedFloat('pickup_ac_duration')->nullable()->after('pickup_time_to');
            $table->unsignedFloat('pickup_ac_radius')->nullable()->after('pickup_time_to');
            $table->boolean('pickup_ac')->default(false)->after('pickup_time_to');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('tasks', 'pickup_ac')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_ac_finished_at',
                'delivery_ac_started_at',
                'delivery_ac_status',
                'delivery_ac_duration',
                'delivery_ac_radius',
                'delivery_ac',
                'pickup_ac_finished_at',
                'pickup_ac_started_at',
                'pickup_ac_status',
                'pickup_ac_duration',
                'pickup_ac_radius',
                'pickup_ac',
            ]);
        });
    }
}
