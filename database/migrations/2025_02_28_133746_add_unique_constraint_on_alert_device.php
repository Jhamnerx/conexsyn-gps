<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintOnAlertDevice extends Migration
{
    private const INDEX = 'alert_device_alert_id_device_id_unique';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ($this->hasIndex()) {
            return;
        }

        $this->removeDuplicates();

        Schema::table('alert_device', function (Blueprint $table) {
            $table->unique(['alert_id', 'device_id'], self::INDEX);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!$this->hasIndex()) {
            return;
        }

        Schema::table('alert_device', function (Blueprint $table) {
            $table->dropUnique(self::INDEX);
        });
    }

    private function hasIndex(): bool
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes('alert_device');

        return isset($indexes[self::INDEX]);
    }

    private function removeDuplicates(): void
    {
        Schema::table('alert_device', function (Blueprint $table) {
            $table->bigIncrements('id')->first();
        });

        DB::table('alert_device as t1')
            ->join('alert_device as t2', fn (JoinClause $join) => $join
                ->on('t1.alert_id', '=', 't2.alert_id')
                ->on('t1.device_id', '=', 't2.device_id')
                ->on('t1.id', '<', 't2.id')
            )
            ->delete();

        Schema::table('alert_device', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }
}
