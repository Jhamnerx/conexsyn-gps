<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintOnReportDevice extends Migration
{
    private const INDEX = 'report_device_pivot_report_id_device_id_unique';

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

        Schema::table('report_device_pivot', function (Blueprint $table) {
            $table->unique(['report_id', 'device_id'], self::INDEX);
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

        Schema::table('report_device_pivot', function (Blueprint $table) {
            $table->dropUnique(self::INDEX);
        });
    }

    private function hasIndex(): bool
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes('report_device_pivot');

        return isset($indexes[self::INDEX]);
    }

    private function removeDuplicates(): void
    {
        Schema::table('report_device_pivot', function (Blueprint $table) {
            $table->bigIncrements('id')->first();
        });

        DB::table('report_device_pivot as t1')
            ->join('report_device_pivot as t2', fn (JoinClause $join) => $join
                ->on('t1.report_id', '=', 't2.report_id')
                ->on('t1.device_id', '=', 't2.device_id')
                ->on('t1.id', '<', 't2.id')
            )
            ->delete();

        Schema::table('report_device_pivot', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }
}
