<?php namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Repositories\Device\DeviceRepositoryInterface as Device;
use Tobuli\Repositories\DeviceIcon\DeviceIconRepositoryInterface as DeviceIcon;
use Tobuli\Validation\DeviceIconUploadValidator;


class DeviceIconsController extends BaseController {

    const STATUSES_COUNT = 4;

    private $section = 'device_icons';

    private $device;
    private $deviceIcon;
    private $deviceIconUploadValidator;

    private $iconsDirectory;


    function __construct(DeviceIcon $deviceIcon, Device $device, DeviceIconUploadValidator $deviceIconUploadValidator)
    {
        parent::__construct();

        $this->device = $device;
        $this->deviceIcon = $deviceIcon;
        $this->deviceIconUploadValidator = $deviceIconUploadValidator;

        $this->iconsDirectory = 'images/device_icons';
    }

    public function index()
    {
        $input = Request::all();

        $section = $this->section;

        $items = \Tobuli\Entities\DeviceIcon::where('id', '>', 0)->paginate(41);

        return View::make('admin::' . ucfirst($section) . '.' . (Request::ajax() ? 'table' : 'index'))->with(compact('items', 'input', 'section'));
    }

    public function create() {
        $types = ['icon' => 'Icon', 'rotating' => 'Rotating'];

        return View::make('admin::'.ucfirst($this->section).'.' . 'create')->with(compact('types'));
    }

    public function store() {
        $type = Request::input('type', 'icon');

        if ( ! Request::input('by_status')) { // single icon store
            $file = Request::file('file');

            $this->deviceIconUploadValidator->validate('create', [
                'file' => $file,
                'type' => $type
            ]);

            list($width, $height) = getimagesize($file);

            $filename = uniqid('', TRUE). '.' . $file->getClientOriginalExtension();
            while ( ! empty(glob($this->iconsDirectory . '/' . $filename . '*')))
                $filename = uniqid('', TRUE);

            $file->move($this->iconsDirectory, $filename);

            $this->deviceIcon->create([
                'type'          => $type,
                'path'          => $this->iconsDirectory . '/' . $filename,
                'width'         => $width,
                'height'        => $height
            ]);

            return Response::json(['status' => 1]);

        } else { // by status icons store
            return $this->storeByStatus($type);
        }
    }

    public function edit($id) {
        $item = $this->deviceIcon->find($id);

        if (empty($item))
            return modalError(dontExist('global.icon'));

        $by_status = $item->by_status;
        $types = ['icon' => 'Icon', 'rotating' => 'Rotating'];

        return View::make('admin::'.ucfirst($this->section).'.' . 'edit')->with(compact('types', 'item', 'by_status'));
    }

    public function update($id) {
        if (empty($item = $this->deviceIcon->find($id)))
            return modalError(dontExist('global.icon'));

        $type = Request::input('type', 'icon');

        if ( ! Request::input('by_status')) { // single icon update
            $file = Request::file('file');

            $this->deviceIconUploadValidator->validate('update', [
                'file' => $file,
                'type' => $type
            ]);

            if ( ! $file) {
                $item->update([
                    'type'      => $type,
                    'by_status' => false
                ]);

                return Response::json(['status' => 1]);
            }

            list($width, $height) = getimagesize($file);

            $filename = uniqid('', TRUE) . '.' . $file->getClientOriginalExtension();
            while ( ! empty(glob($this->iconsDirectory . '/' . $filename . '*')))
                $filename = uniqid('', TRUE);

            $file->move($this->iconsDirectory, $filename);

            $item->update([
                'path'      => $this->iconsDirectory . '/' . $filename,
                'width'     => $width,
                'height'    => $height,
                'type'      => $type,
                'by_status' => false
            ]);

            return Response::json(['status' => 1]);

        } else { // by status icons update
            return $this->updateByStatus($item, $type);
        }
    }

    public function destroy() {
        $ids = Request::input('id');
        if (is_array($ids) && $nr = count($ids)) {
            $all = $this->deviceIcon->count();
            if ($nr >= $all) {
                return Response::json(['status' => 0, 'error' => trans('admin.cant_delete_all')]);
            }
            $icon = $this->deviceIcon->whereNotInFirst($ids);

            $this->device->updateWhereIconIds($ids, ['icon_id' => $icon->id]);
            foreach($ids as $id) {
                if ($id == 0)
                    continue;

                $del_icon = $this->deviceIcon->find($id);
                if ($del_icon) {
                    $filename = public_path().'/'.$del_icon->path;
                    if (File::exists($filename)) {
                        File::delete($filename);
                    }
                    $this->deviceIcon->delete($id);
                }
            }

            /*File::cleanDirectory(base_path('../../').'/public/frontend/images/device_icons');
            File::copyDirectory('frontend/images/device_icons', base_path('../../').'/public/frontend/images/device_icons');*/
        }

        return Response::json(['status' => 1]);
    }

    private function storeByStatus($type)
    {
        if (count($files = Request::file()) < self::STATUSES_COUNT)
            return Response::make(['errors' => ['message' => 'You must upload 4 icons for all statuses.']], '406');

        $icons_base_name = uniqid('', TRUE);
        while ( ! empty(glob($this->iconsDirectory . '/' . $icons_base_name . '*')))
            $icons_base_name = uniqid('', TRUE);

        foreach ($files as $status => $file) {
            $this->deviceIconUploadValidator->validate('create', [
                'file' => $file,
                'type' => $type
            ]);

            list($width, $height) = getimagesize($file);

            $file->move($this->iconsDirectory, $filename = $icons_base_name . '_' . $status . '.' . $file->getClientOriginalExtension());

            if ($status == 'online') {
                $this->deviceIcon->create([
                    'type'          => $type,
                    'path'          => $this->iconsDirectory . '/' . $filename,
                    'width'         => $width,
                    'height'        => $height,
                    'by_status'     => true,
                ]);
            }
        }

        return Response::json(['status' => 1]);
    }

    private function updateByStatus($item, $type)
    {
        if ($item->by_status && empty($files = Request::file())) {
            $item->update([
                'type'      => $type,
                'by_status' => true
            ]);

            return Response::json(['status' => 1]);
        }

        // if wasn't by status, upload all images
        if ( ! $item->by_status && count($files = Request::file()) < self::STATUSES_COUNT)
            return Response::make(['errors' => ['message' => 'You must upload 4 icons for all statuses.']], '406');

        $path_info = pathinfo($item->path);

        $filename_parts = explode('_', $path_info['filename']);

        $icons_base_name = $filename_parts[0];

        foreach ($files as $status => $file) {
            $this->deviceIconUploadValidator->validate('update', [
                'file' => $file,
                'type' => $type
            ]);

            list($width, $height) = getimagesize($file);

            $file->move($this->iconsDirectory, $filename = $icons_base_name . '_' . $status . '.' . $file->getClientOriginalExtension());

            if ($status == 'online') {
                $item->update([
                    'path'      => $this->iconsDirectory . '/' . $filename,
                    'width'     => $width,
                    'height'    => $height,
                    'type'      => $type,
                    'by_status' => true
                ]);
            }
        }

        return Response::json(['status' => 1]);
    }
}
