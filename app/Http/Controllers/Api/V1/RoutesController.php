<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Transformers\Route\RouteMapTransformer;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\Route;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\GroupModelService;
use Tobuli\Services\RouteUserService;

class RoutesController extends Controller
{
    private RouteUserService $service;

    protected function afterAuth($user)
    {
        $this->service = new RouteUserService($user);
    }

    public function index()
    {
        $this->checkException('routes', 'view');

        $items = Route::userOwned($this->user)
            ->filter(request()->all())
            ->search(request('search'))
            ->paginate(500);

        return response()->json(
            \FractalTransformer::paginate($items, RouteMapTransformer::class)->toArray()
        );
    }

    public function store()
    {
        $this->data['coordinates'] = $this->data['polyline'];

        $item = $this->service->create($this->data);

        return ['status' => 1] + \FractalTransformer::item($item, RouteMapTransformer::class)->toArray();
    }

    public function update(?int $id = null)
    {
        $this->data['coordinates'] = $this->data['polyline'];

        $item = Route::find($id);

        $this->service->edit($item, $this->data);

        return ['status' => 1] + \FractalTransformer::item($item, RouteMapTransformer::class)->toArray();
    }

    public function changeActive()
    {
        $validator = Validator::make($this->data, [
            'id' => 'required_without:group_id',
            'group_id' => 'required_without:id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $ids = $this->data['id'] ?? false;
        $groupIds = $this->data['group_id'] ?? false;
        $active = $this->data['active'] ?? 0;

        (new GroupModelService($this->user->routes()))->changeActive(
            $ids,
            $groupIds,
            $active
        );

        return [
            'status'    => 1,
            'ids'       => $ids,
            'groupIds'  => $groupIds,
            'active'    => $active,
        ];
    }

    public function destroy($id = null)
    {
        $ids = $this->data['route_id'] ?? ($this->data['id'] ?? $id);

        if ($ids === null) {
            return ['status' => 0];
        }

        if (is_scalar($ids)) {
            $ids = (array)$ids;
        }

        $items = Route::findMany($ids);

        foreach ($items as $item) {
            $this->service->remove($item);
        }

        return [
            'status'    => 1,
            'ids'       => $items->pluck('id')->all(),
        ];
    }
}
