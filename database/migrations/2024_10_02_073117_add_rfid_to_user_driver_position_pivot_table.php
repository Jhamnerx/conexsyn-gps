<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Entities\Device;
use Tobuli\Entities\UserDriver;

class AddRfidToUserDriverPositionPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->upUdpPivot();
        $this->upDevices();
    }

    private function upUdpPivot(): void
    {
        if (Schema::hasColumn('user_driver_position_pivot', 'rfid')) {
            return;
        }

        $success = $this->attemptDropFk('user_driver_position_pivot_driver_id_foreign', false);

        if (!$success) {
            $this->attemptDropFk('user_driver_position_pivot_ibfk_2', true);
        }

        Schema::table('user_driver_position_pivot', function (Blueprint $table) {
            $table->string('rfid')->nullable()->after('driver_id');

            $table->foreign('driver_id')->references('id')->on('user_drivers')->nullOnDelete();
        });

        DB::table('user_driver_position_pivot')->update([
            'rfid' => DB::raw(
                '(' . UserDriver::select('rfid')->whereColumn('id', 'user_driver_position_pivot.driver_id')->toRaw() . ')'
            )
        ]);
    }

    private function upDevices()
    {
        if (Schema::hasColumn('devices', 'current_driver_rfid')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->string('current_driver_rfid')->nullable()->after('current_driver_id');
        });

        Device::whereNotNull('current_driver_id')->update([
            'current_driver_rfid' => DB::raw(
                '(' . UserDriver::select('rfid')->whereColumn('id', 'devices.current_driver_id')->toRaw() . ')'
            )
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->downUdpPivot();
        $this->downDevices();
    }

    private function downUdpPivot(): void
    {
        if (!Schema::hasColumn('user_driver_position_pivot', 'rfid')) {
            return;
        }

        Schema::table('user_driver_position_pivot', function (Blueprint $table) {
            $table->dropColumn('rfid');

            $table->dropForeign('user_driver_position_pivot_driver_id_foreign');
            $table->foreign('driver_id')->references('id')->on('user_drivers')->cascadeOnDelete();
        });
    }

    private function downDevices(): void
    {
        if (!Schema::hasColumn('devices', 'current_driver_rfid')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('current_driver_rfid');
        });
    }

    private function attemptDropFk(string $fk, bool $throw): bool
    {
        try {
            Schema::table('user_driver_position_pivot', function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk);
            });
        } catch (QueryException $e) {
            if ($throw || !($e->getCode() === 'HY000' && strpos($e->getMessage(), '1025 Error on rename'))) {
                throw $e;
            }

            return false;
        }

        return true;
    }
}
