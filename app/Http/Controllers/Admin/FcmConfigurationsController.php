<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Tobuli\Entities\FcmConfiguration;
use Tobuli\Helpers\FcmConfigurationService;

class FcmConfigurationsController extends BaseController
{
    private FcmConfigurationService $service;

    public function __construct(FcmConfigurationService $service)
    {
        parent::__construct();

        $this->service = $service;
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

        $items = FcmConfiguration::search($input['search_phrase'] ?? '')
            ->withCount('tokens')
            ->toPaginator(
                10,
                $sorting['sort_by'] ?? 'title',
                $sorting['sort'] ?? 'asc'
            );

        return View::make('Admin.FcmConfigurations.' . $view)
            ->with(compact('items', 'input'));
    }

    public function create()
    {
        return view('Admin.FcmConfigurations.create');
    }

    public function store()
    {
        $item = $this->service->store($this->data);

        return ['status' => 1, 'data' => $item->attributesToArray()];
    }

    public function edit(int $id)
    {
        $item = FcmConfiguration::findOrFail($id);

        return view('Admin.FcmConfigurations.edit')->with(compact('item'));
    }

    public function update(int $id)
    {
        $item = $this->service->store(['id' => $id] + $this->data);

        return ['status' => 1, 'data' => $item->attributesToArray()];
    }

    public function setDefault(int $id)
    {
        $this->service->setDefault($id);

        return ['status' => 1];
    }

    public function destroy(int $id = null)
    {
        $id = $id ?: request('id');

        $this->service->delete($id);

        return ['status' => 1];
    }
}
