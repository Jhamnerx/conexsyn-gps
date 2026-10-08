<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBackupsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('backups')) {
            Schema::create('backups', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('message');
                $table->string('launcher')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('backup_processes')) {
            Schema::create('backup_processes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('backup_id')->nullable()->constrained('backups')->cascadeOnDelete();
                $table->string('type');
                $table->string('source');
                $table->text('options');
                $table->string('last_item_id')->nullable();
                $table->unsignedBigInteger('processed')->default(0);
                $table->unsignedBigInteger('total')->default(0);
                $table->unsignedInteger('duration_active');
                $table->unsignedTinyInteger('attempt')->default(0);
                $table->timestamps();
                $table->timestamp('reserved_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('failed_at')->nullable();
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
        Schema::dropIfExists('backup_processes');
        Schema::dropIfExists('backups');
    }
}
