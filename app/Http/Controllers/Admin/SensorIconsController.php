<?php

namespace App\Http\Controllers\Admin;

use App\Events\SensorIconsDeleted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Tobuli\Entities\SensorIcon;
use Tobuli\Validation\SensorIconUploadValidator;

class SensorIconsController extends BaseController
{
    private const ICONS_DIR = 'images/sensor_icons';

    private SensorIconUploadValidator $uploadValidator;

    function __construct(SensorIconUploadValidator $uploadValidator)
    {
        parent::__construct();

        $this->uploadValidator = $uploadValidator;
    }

    public function index()
    {
        $input = Request::all();

        $items = SensorIcon::paginate(41);

        return View::make('admin::SensorIcons.' . (Request::ajax() ? 'table' : 'index'))
            ->with(compact('items', 'input'));
    }

    public function create()
    {
        return View::make('admin::SensorIcons.create');
    }

    public function store()
    {
        $file = Request::file('file');

        $this->uploadValidator->validate('create', [
            'file' => $file,
        ]);

        list($width, $height) = getimagesize($file);

        $filename = $this->moveFile($file);

        SensorIcon::create([
            'path' => self::ICONS_DIR . '/' . $filename,
            'width' => $width,
            'height' => $height
        ]);

        return Response::json(['status' => 1]);
    }

    public function edit($id)
    {
        $item = SensorIcon::find($id);

        if (empty($item)) {
            return modalError(dontExist('global.icon'));
        }

        return View::make('admin::SensorIcons.edit')->with(compact('item'));
    }

    public function update($id)
    {
        if (empty($item = SensorIcon::find($id))) {
            return modalError(dontExist('global.icon'));
        }

        $file = Request::file('file');

        $this->uploadValidator->validate('update', [
            'file' => $file,
        ]);

        list($width, $height) = getimagesize($file);

        $filename = $this->moveFile($file);

        $item->update([
            'path' => self::ICONS_DIR . '/' . $filename,
            'width' => $width,
            'height' => $height,
        ]);

        return Response::json(['status' => 1]);
    }

    private function moveFile(UploadedFile $file): string
    {
        $filename = uniqid('', true) . '.' . $file->getClientOriginalExtension();

        while (!empty(glob(self::ICONS_DIR . '/' . $filename . '*'))) {
            $filename = uniqid('', true);
        }

        $file->move(self::ICONS_DIR, $filename);

        return $filename;
    }

    public function destroy()
    {
        $ids = Request::input('id');

        if (!is_array($ids) || empty($nr = count($ids))) {
            return Response::json(['status' => 1]);
        }

        $all = SensorIcon::count();

        if ($nr >= $all) {
            return Response::json(['status' => 0, 'error' => trans('admin.cant_delete_all')]);
        }

        event(new SensorIconsDeleted($ids));

        $delIcons = SensorIcon::whereIn('id', $ids)->get();
        $ids = $delIcons->pluck('id')->all();

        foreach ($delIcons as $delIcon) {
            $delIcon->delete();
        }

        event(new SensorIconsDeleted($ids));

        return Response::json(['status' => 1]);
    }
}
