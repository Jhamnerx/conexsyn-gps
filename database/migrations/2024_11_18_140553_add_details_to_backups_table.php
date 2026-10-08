<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Entities\Backup;

class AddDetailsToBackupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('backups', 'details')) {
            return;
        }

        App::setLocale(settings('main_settings.default_language'));

        Schema::table('backups', function (Blueprint $table) {
            $table->text('details')->nullable()->after('message');
        });

        $successMsg = trans('front.successfully_uploaded');
        $errorMsg = trans('front.unexpected_error');

        Backup::whereNotNull('message')
            ->where('message', '!=', $successMsg)
            ->where('message', '!=', '')
            ->update([
                'details' => DB::raw('message'),
                'message' => $errorMsg,
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('backups', 'details')) {
            return;
        }

        Backup::whereNotNull('details')->update([
            'message' => DB::raw('details'),
        ]);

        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
}
