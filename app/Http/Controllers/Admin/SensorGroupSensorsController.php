<?php namespace App\Http\Controllers\Admin;

use CustomFacades\Repositories\DeviceSensorRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use ModalHelpers\SensorModalHelper;
use Tobuli\Entities\SensorGroupSensor;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Repositories\SensorGroup\SensorGroupRepositoryInterface as SensorGroup;
use Tobuli\Validation\AdminSensorGroupFormValidator;

class SensorGroupSensorsController extends BaseController {

    public function index($id, $ajax = 0) {
        $items = SensorGroupSensor::where(['group_id' => $id])
            ->orderBy('id', 'desc')
            ->get()
            ->filter(function ($item) {
                return $item->getTypeObject()::isEnabled();

            });

        return view('admin::SensorGroupSensors.'.($ajax ? 'table' : 'index'))->with(compact('items', 'id'));
    }

    public function create($id, SensorModalHelper $sensorModalHelper) {
        $data = array_merge($sensorModalHelper->createData(null), [
            'route' => 'admin.sensor_group_sensors.store',
            'id' => $id,
            'sensor_group_id' => $id,
        ]);

        return view('front::Sensors.create')->with($data);
    }
    
    public function store(Request $request, SensorModalHelper $sensorModalHelper, SensorGroup $sensorGroupRepo) {
        $input = $request->all();

        $sensorModalHelper->validate($this->data);

        $arr = $sensorModalHelper->formatInput($this->data);
        $arr['group_id'] = $input['id'];

        SensorGroupSensor::create($arr);

        $count = SensorGroupSensor::where(['group_id' => $arr['group_id']])->count();

        $sensorGroupRepo->update($arr['group_id'], [
            'count' => $count
        ]);

        return ['status' => 1];
    }

    public function edit($id, SensorModalHelper $sensorModalHelper) {
        $data = array_merge($sensorModalHelper->createData(null), [
            'route' => ['admin.sensor_group_sensors.update', $id],
            'id' => $id
        ]);

        $data['item'] = $sensorModalHelper->itemForm(SensorGroupSensor::find($id));

        return view('front::Sensors.edit')->with($data);
    }

    public function update(Request $request, SensorModalHelper $sensorModalHelper) {
        $input = $request->all();
        $sensor = SensorGroupSensor::find($input['id']);

        $sensorModalHelper->validate($this->data, $sensor);

        $arr = $sensorModalHelper->formatInput($this->data);

        $sensor->update($arr);

        return ['status' => 1];
    }

    public function destroy(Request $request, SensorGroup $sensorGroupRepo) {
        $input = $request->all();
        if (!isset($input['id']) || empty($input['id']))
            return response()->json(['status' => 0]);

        $ids = $input['id'];

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $item = SensorGroupSensor::whereIn('id', $ids)->first();

        SensorGroupSensor::whereIn('id', $ids)->delete();

        $count = SensorGroupSensor::where(['group_id' => $item->group_id])->count();
        $sensorGroupRepo->update($item->group_id, [
            'count' => $count
        ]);

        return response()->json(['status' => 1, 'trigger' => 'updateSensorGroupsTable']);
    }
}
