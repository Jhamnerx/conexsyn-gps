<?php namespace App\Http\Controllers\Admin;

use App\Events\Device\DeviceDisabled;
use App\Events\Device\DeviceEnabled;
use App\Exceptions\ResourseNotFoundException;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\UserRepo;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Exporters\EntityManager\Device\ExportManager;
use Tobuli\Helpers\Tracker;
use Tobuli\Importers\Device\DeviceImporter;
use Tobuli\Importers\Importer;
use Tobuli\Repositories\Device\DeviceRepositoryInterface as Device;
use Tobuli\Services\DeviceService;
use Tobuli\Validation\ClientFormValidator;

class ObjectsController extends BaseController
{
    /**
     * @var ClientFormValidator
     */
    private $clientFormValidator;

    private $section = 'objects';

    private $deviceService;

    /**
     * @var Device
     */
    private $device;

    function __construct(
        ClientFormValidator $clientFormValidator,
        DeviceService $deviceService,
        Device $device
    ) {
        parent::__construct();
        $this->clientFormValidator = $clientFormValidator;
        $this->deviceService = $deviceService;
        $this->device = $device;
    }

    public function index()
    {
        $input = array_merge(['limit' => 25], request()->input());
        $sort = $input['sorting'] ?? ['sort_by' => 'name', 'sort' => 'asc'];

        $items = $this->user
            ->accessibleDevices()
            ->traccarJoin()
            ->select(['devices.*', 'traccar_devices.server_time', 'traccar_devices.time'])
            ->with(['traccar', 'sensors'])
            ->selectUsersEmails($this->user)
            ->search($input['search_phrase'] ?? '')
            ->clearOrdersBy()
            ->toPaginator($input['limit'], $sort['sort_by'], $sort['sort']);

        $section = $this->section;

        return View::make('admin::' . ucfirst($this->section) . '.' . (Request::ajax() ? 'table' : 'index'))
            ->with(compact('items', 'section'));
    }

    public function create()
    {
        $managers = UserRepo::getOtherManagers(0)
            ->pluck('email', 'id')
            ->prepend('-- ' . trans('admin.select') . ' --', '0')
            ->all();

        return View::make('admin::' . ucfirst($this->section) . '.create')->with(compact('managers'));
    }

    public function destroy()
    {
        if (config('addon.object_delete_pass') && isAdmin() && request('password') != config('addon.object_delete_pass')) {
            return ['status' => 0, 'errors' => ['message' => trans('front.login_failed')]];
        }

        $ids = Request::input('ids');

        if (is_array($ids) && count($ids)) {
            foreach ($ids as $id) {
                $item = DeviceRepo::find($id);

                if (empty($item) || ( ! $this->user->can('remove', $item)))
                    continue;

                $this->deviceService->delete($item);
            }
        }

        return Response::json(['status' => 1]);
    }

    public function doDestroy()
    {
        $validator = Validator::make($this->data, [
            'id'     => 'required|array',
            'id.*'   => 'integer',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->messages());
        }

        return view('admin::Objects.destroy', ['ids' => $this->data['id']]);
    }

    public function setActiveMulti($active)
    {
        $this->data['active'] = (bool)$active;

        $validator = Validator::make($this->data, [
            'id'     => 'required|array',
            'id.*'   => 'integer',
            'active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->messages());
        }

        $this->user
            ->accessibleDevices()
            ->whereIn('id', $this->data['id'])
            ->clearOrdersBy()
            ->update(['devices.active' => $this->data['active']]);

        $devices = $this->user
            ->accessibleDevices()
            ->whereIn('id', $this->data['id'])
            ->get();

        foreach ($devices as $device) {
            event($device->active ? new DeviceEnabled($device) : new DeviceDisabled($device));
        }

        return Response::json(['status' => 1]);
    }

    public function restartTraccar()
    {
        $tracker = new Tracker();
        $tracker->actor($this->user)->restart();

        return redirect()->back();
    }

    public function import()
    {
        $collisionOptions = DeviceImporter::getCollisionOptions();

        return View::make('admin::' . ucfirst($this->section) . '.import')
            ->with(compact('collisionOptions'));
    }

    public function importSet()
    {
        $validator = Validator::make(request()->all(), ['file' => 'required']);

        if ($validator->fails())
            throw new ValidationException($validator->messages());

        $file = Request::file('file');

        if ( ! $file->isValid())
            return;

        $manager = new \Tobuli\Importers\Device\DeviceImportManager();
        $manager->import($file->getPathName(), request()->only(Importer::ON_COLLISION));

        return Response::json(['status' => 1]);
    }

    public function export()
    {
        $devices = $this->user->accessibleDevices()->select(['devices.*']);
        $devicesCheck = clone $devices;

        if ($devicesCheck->count() === 0) {
            return Response::json(['status' => 1]);
        }

        /** @var \Tobuli\Entities\Device $firstDevice */
        $firstDevice = $devicesCheck->first();
        $fields = request()->get('fields', []);

        if (!$firstDevice)
            return Response::json(['status' => 0]);

        foreach ($fields as $index => $field) {
            if (!$this->user->can('view', $firstDevice, $field)) {
                unset($fields[$index]);
            }

            if ($field == 'users_list') {
                $devices->selectUsersEmails($this->user);
            }
        }

        $relations = $firstDevice->getRelationsForAttributes($fields);

        if (\count($relations)) {
            $devices->with($relations);
        }

        return (new ExportManager($devices->getQuery()))
            ->download($fields, request('format'));
    }

    public function exportModal()
    {
        return view('admin::' . ucfirst($this->section) . '.export', [
            'formats' => config('tobuli.exports.formats'),
            'fields'  => \Tobuli\Entities\Device::getFields()
        ]);
    }

    public function expiration(\Illuminate\Http\Request $request, $imei)
    {
        $validator = Validator::make($request->all(), [
            'expiration_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            throw new ValidationException( $validator->messages() );
        }

        $device = $this->device->findWhere(['imei' => $imei]);

        if ( ! $device)
           throw new ResourseNotFoundException('global.device');

        $device->update(['expiration_date' => $request->input('expiration_date')]);

        return Response::json(['status' => 1]);
    }

    public function bulkDelete()
    {
        if ( ! $this->user->isAdmin())
            throw new AuthorizationException();

        $validator = Validator::make(request()->all(), ['file' => 'required']);

        if ($validator->fails())
            throw new ValidationException($validator->messages());

        $file = request()->file('file');

        if (is_null($file) || $file->getClientOriginalExtension() != 'csv')
            throw new ValidationException('Only CSV');

        $source = file_get_contents($file);
        $rows = str_getcsv($source, "\n");

        if (empty($rows)) {
            return null;
        }
        $headers = array_shift($rows);
        $imeis = $rows;

        $errors_count = 0;
        $content = trans('admin.logs') . " <br>";

        if (is_array($imeis) && count($imeis)) {
            foreach ($imeis as $imei) {
                $device = DeviceRepo::whereImei($imei);

                if (empty($device)) {
                    $content .= trans('validation.attributes.imei') . "($imei) ". trans('global.not_found') ."<br>";
                    $errors_count++;
                    continue;
                }

                if ($this->removeDevice($device) == false) {
                    $content .= trans('global.device') . "($imei) " . trans('global.failed') . "<br>";
                    $errors_count++;
                }
            }
        }

        $content .= "<br>" . trans('global.successful') . " " . lcfirst(trans('global.count')) . ": " . (count($imeis) - $errors_count);
        $content .= "<br>" . trans('global.failed') . " " . lcfirst(trans('global.count')) . ":  $errors_count";

        return Response::json([
            'status'  => 0,
            'content' => $content,
            'trigger' => 'bulk_delete_object',
        ]);
    }

    public function bulkDeleteModal()
    {
        return view('admin::' . ucfirst($this->section) . '.bulk_delete');
    }

    private function removeDevice($device)
    {
        try {
            $this->deviceService->delete($device);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
