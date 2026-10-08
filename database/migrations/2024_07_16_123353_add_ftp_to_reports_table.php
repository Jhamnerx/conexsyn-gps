<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFtpToReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('report_logs', 'ftp')) {
            Schema::table('report_logs', function (Blueprint $table) {
                $table->boolean('is_upload')->nullable()->after('is_send');
                $table->string('ftp')->nullable()->after('email');
            });
        }

        if (!Schema::hasColumn('reports', 'ftp')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->string('ftp')->nullable()->after('email');
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
        if (Schema::hasColumn('report_logs', 'ftp')) {
            Schema::table('report_logs', function (Blueprint $table) {
                $table->dropColumn('is_upload');
                $table->dropColumn('ftp');
            });
        }

        if (Schema::hasColumn('reports', 'ftp')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->dropColumn('ftp');
            });
        }
    }
}
