<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Entities\BackupProcess;
use Tobuli\Helpers\Backup\Process\DatabaseBackuper;
use Tobuli\Helpers\Backup\Process\DevicesPositionsBackuper;
use Tobuli\Helpers\Backup\Process\FilesBackuper;

class AddItemExpireDurationToBackupProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('backup_processes', 'duration_expire')) {
            return;
        }

        Schema::table('backup_processes', function (Blueprint $table) {
            $table->unsignedInteger('duration_expire')->default(0)->after('duration_active');
        });

        BackupProcess::where('type', DevicesPositionsBackuper::class)->update(['duration_expire' => DB::raw('10 * total')]);
        BackupProcess::where('type', DatabaseBackuper::class)->update(['duration_expire' => 3600]);
        BackupProcess::where('type', FilesBackuper::class)->update(['duration_expire' => 3600]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('backup_processes', 'duration_expire')) {
            return;
        }

        Schema::table('backup_processes', function (Blueprint $table) {
            $table->dropColumn('duration_expire');
        });
    }
}
