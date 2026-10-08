<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tobuli\Traits\DatabaseRunChangesTrait;

class SensorColumnsRefactor extends Migration
{
    use DatabaseRunChangesTrait;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->dropColumnIfExists('device_sensors', 'temperature_max');
        $this->dropColumnIfExists('device_sensors', 'temperature_max_value');
        $this->dropColumnIfExists('device_sensors', 'temperature_min');
        $this->dropColumnIfExists('device_sensors', 'temperature_min_value');
        $this->dropColumnIfExists('sensor_group_sensors', 'temperature_max');
        $this->dropColumnIfExists('sensor_group_sensors', 'temperature_max_value');
        $this->dropColumnIfExists('sensor_group_sensors', 'temperature_min');
        $this->dropColumnIfExists('sensor_group_sensors', 'temperature_min_value');


        $data = [
            'on_type' => 1,
            'off_type' => 1,
            'on_tag_value' => DB::raw('on_value'),
            'off_tag_value' => DB::raw('off_value'),
        ];
        $types = ['acc'];
        DB::table('device_sensors')->whereIn('type', $types)->update($data);
        DB::table('sensor_group_sensors')->whereIn('type', $types)->update($data);


        $data = [
            'on_type' => 1,
            'on_tag_value' => DB::raw('on_value'),
        ];
        $types = ['harsh_breaking', 'harsh_acceleration', 'harsh_turning'];
        DB::table('device_sensors')->whereIn('type', $types)->update($data);
        DB::table('sensor_group_sensors')->whereIn('type', $types)->update($data);


        $data = [
            'value' => DB::raw('odometer_value'),
        ];
        DB::table('device_sensors')
            ->where('type','odometer')
            ->where('odometer_value_by', 'virtual_odometer')
            ->update($data);


        $data = [
            'shown_value_by' => DB::raw('odometer_value_by'),
        ];
        $types = ['odometer'];
        DB::table('device_sensors')->whereIn('type', $types)->update($data);
        DB::table('sensor_group_sensors')->whereIn('type', $types)->update($data);


        $types = [
            'load_calibration' => 'load',
            'fuel_tank_calibration' => 'fuel_tank',
            'temperature_calibration' => 'temperature',
        ];
        foreach ($types as $from => $to) {
            DB::table('device_sensors')->where('type', $from)->update(['type' => $to]);
            DB::table('sensor_group_sensors')->where('type', $from)->update(['type' => $to]);
        }


        if (!Schema::hasColumn('device_sensors', 'bitcut')) {
            Schema::table('device_sensors', function (Blueprint $table) {
                $table->boolean('add_to_graph')->nullable()->after('add_to_history');
                $table->text('bitcut')->nullable();
                $table->text('mappings')->nullable();
                $table->integer('icon_id')->unsigned()->nullable()->index()->after('device_id');
                $table->foreign('icon_id')->references('id')->on('sensor_icons')->onDelete('set null');
            });
        }
        if (!Schema::hasColumn('sensor_group_sensors', 'bitcut')) {
            Schema::table('sensor_group_sensors', function (Blueprint $table) {
                $table->boolean('add_to_graph')->nullable()->after('add_to_history');
                $table->text('bitcut')->nullable();
                $table->text('mappings')->nullable();
                $table->integer('icon_id')->unsigned()->nullable()->index()->after('group_id');
                $table->foreign('icon_id')->references('id')->on('sensor_icons')->onDelete('set null');
            });
        }

        $data = [
            'add_to_graph' => 1,
        ];
        $types = [
            'fuel_tank',
            'fuel_consumption',
            'temperature',
            'odometer',
            'tachometer',
            'gsm',
            'battery',
            'load',
            'ignition',
            'engine',
            'speed_ecm',
            'engine_hours'
        ];
        DB::table('device_sensors')->whereIn('type', $types)->update($data);
        DB::table('sensor_group_sensors')->whereIn('type', $types)->update($data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
