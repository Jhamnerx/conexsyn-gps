<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Entities\BackupProcess;
use Tobuli\Helpers\Backup\Process\DatabaseBackuper;
use Tobuli\Helpers\Backup\Process\DevicesPositionsBackuper;
use Tobuli\Helpers\Backup\Process\FilesBackuper;

class RemoveDurationFromBackupProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('backup_processes', 'skipped')) {
            Schema::table('backup_processes', function (Blueprint $table) {
                $table->unsignedBigInteger('skipped')->after('processed');
            });
        }

        if (!Schema::hasColumn('backup_processes', 'duration_active')) {
            return;
        }

        Schema::table('backup_processes', function (Blueprint $table) {
            $table->dropColumn('duration_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('backup_processes', 'skipped')) {
            Schema::table('backup_processes', function (Blueprint $table) {
                $table->dropColumn('skipped');
            });
        }

        if (Schema::hasColumn('backup_processes', 'duration_active')) {
            return;
        }

        Schema::table('backup_processes', function (Blueprint $table) {
            $table->unsignedInteger('duration_active')->after('total');
        });

        BackupProcess::where('type', DevicesPositionsBackuper::class)->update(['duration_active' => 30 * 60]);
        BackupProcess::where('type', DatabaseBackuper::class)->update(['duration_active' => 30 * 60]);
        BackupProcess::where('type', FilesBackuper::class)->update(['duration_active' => 60]);
    }
}
