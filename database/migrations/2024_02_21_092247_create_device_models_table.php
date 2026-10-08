<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('device_models')) {
            Schema::create('device_models', function (Blueprint $table) {
                $table->id();
                $table->boolean('active')->default(true);
                $table->string('title');
                $table->string('protocol');
                $table->string('model');
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('devices', 'model_id')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->foreignId('model_id')
                    ->after('icon_id')
                    ->nullable()
                    ->constrained('device_models')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('device_models')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['model_id']);
            $table->dropColumn('model_id');
        });

        Schema::dropIfExists('device_models');
    }
}
