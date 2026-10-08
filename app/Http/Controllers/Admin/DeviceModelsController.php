<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PermissionException;
use Illuminate\Support\Facades\View;
use Tobuli\Entities\DeviceModel;

class DeviceModelsController extends BaseController
{
    public function __construct()
    {
        if (!config('addon.device_models')) {
            abort(404);
        }

        parent::__construct();
    }

    protected function afterAuth($user)
    {
        if (!$user->isAdmin()) {
            throw new PermissionException();
        }
    }

    public function index()
    {
        return $this->getList('index');
    }

    public function table()
    {
        return $this->getList('table');
    }

    private function getList(string $view)
    {
        $input = request()->input();

        $sorting = $input['sorting'] ?? [];

        $items = DeviceModel::search($input['search_phrase'] ?? '')
            ->toPaginator(
                20,
                $sorting['sort_by'] ?? 'title',
                $sorting['sort'] ?? 'asc'
            );

        return View::make('Admin.DeviceModels.' . $view)
            ->with(compact('items', 'input'));
    }

    public function edit(int $id)
    {
        $item = DeviceModel::findOrFail($id);

        return view('Admin.DeviceModels.edit')->with(compact('item'));
    }

    public function update(int $id)
    {
        $item = DeviceModel::findOrFail($id);

        $this->validate(request(), ['title' => 'required']);

        $success = $item->update(request()->only(['title', 'active']));

        return ['status' => (int)$success, 'data' => $item->attributesToArray()];
    }
}
