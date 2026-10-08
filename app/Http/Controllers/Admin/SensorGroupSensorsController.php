<?php namespace App\Http\Controllers\Admin;

use CustomFacades\Repositories\DeviceSensorRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use ModalHelpers\SensorModalHelper;
use Tobuli\Entities\SensorGroupSensor;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Repositories\SensorGroup\SensorGroupRepositoryInterface as SensorGroup;
use Tobuli\Services\DeviceSensorsService;
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

        $sensor = SensorGroupSensor::create($arr);

        $count = SensorGroupSensor::where(['group_id' => $arr['group_id']])->count();

        $sensorGroupRepo->update($arr['group_id'], [
            'count' => $count
        ]);

        // Propaga el sensor a los vehículos cuyo tipo de dispositivo usa este grupo.
        (new DeviceSensorsService())->syncSensorAddedToDevices($sensor, getActingUser());

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

        // Identidad anterior (puede cambiar el nombre/tipo al editar).
        $previous = ['type' => $sensor->type, 'name' => $sensor->name];

        $arr = $sensorModalHelper->formatInput($this->data);

        $sensor->update($arr);

        // Propaga la edición a los vehículos del grupo (vía tipo de dispositivo).
        (new DeviceSensorsService())->syncSensorUpdatedOnDevices($sensor->fresh(), $previous, getActingUser());

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

        $items = SensorGroupSensor::whereIn('id', $ids)->get();
        $item  = $items->first();

        SensorGroupSensor::whereIn('id', $ids)->delete();

        $count = SensorGroupSensor::where(['group_id' => $item->group_id])->count();
        $sensorGroupRepo->update($item->group_id, [
            'count' => $count
        ]);

        // Quita los sensores de los vehículos del grupo (vía tipo de dispositivo).
        $sensorsService = new DeviceSensorsService();
        foreach ($items as $removed) {
            $sensorsService->syncSensorRemovedFromDevices($removed->group_id, $removed->type, $removed->name);
        }

        return response()->json(['status' => 1, 'trigger' => 'updateSensorGroupsTable']);
    }
}
