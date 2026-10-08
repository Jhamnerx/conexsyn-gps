<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Transformers\Geofence\GeofenceMapTransformer;
use FractalTransformer;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\Geofence;
use Tobuli\Entities\GeofenceGroup;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\GeofenceUserService;
use Tobuli\Services\GroupModelService;

class GeofencesController extends Controller
{
    private GeofenceUserService $geofenceService;

    protected function afterAuth($user)
    {
        $this->geofenceService = new GeofenceUserService($this->user);
    }

    public function indexModal()
    {
        return $this->getList('modal');
    }

    public function table()
    {
        return $this->getList('table');
    }

    public function getList(string $view)
    {
        $this->checkException('geofences', 'view');

        $sort = $this->data['sorting'] ?? [];
        $sortCol = $sort['sort_by'] ?? 'name';
        $sortDir = $sort['sort'] ?? 'asc';

        $items = Geofence::userOwned($this->user)
            ->search($this->data['search_phrase'] ?? null)
            ->select(['id', 'active', 'name', 'type', 'polygon_color'])
            ->toPaginator(15, $sortCol, $sortDir);

        return view('front::Geofences.' . $view)->with(compact('items'));
    }

    public function index()
    {
        $this->checkException('geofences', 'view');

        $items = Geofence::userOwned($this->user)->paginate(500);

        return response()->json(
            FractalTransformer::paginate($items, GeofenceMapTransformer::class)->toArray()
        );
    }

    public function create()
    {
        $this->checkException('geofences', 'store');

        $data = $this->getFormData();

        return view('front::Geofences.create')->with($data);
    }

    public function edit(int $id)
    {
        $item = Geofence::find($id);

        $this->checkException('geofences', 'edit', $item);

        $data = $this->getFormData();

        if (settings('plugins.geofences_speed_limit.status') && $item->speed_limit !== null) {
            $item->speed_limit = round(\Formatter::speed()->convert($item->speed_limit));
        }

        $data['item'] = $item;

        return view('front::Geofences.edit')->with($data);
    }

    private function getFormData()
    {
        $geofenceTypes = ['polygon' => trans('front.polygon'), 'circle' => trans('front.circle')];

        $geofenceGroups = GeofenceGroup::userOwned($this->user)
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $data = compact('geofenceTypes', 'geofenceGroups');

        if (settings('plugins.moving_geofence.status')) {
            $data['devices'] = $this->user->devices()->get();
        }

        return $data;
    }

    public function store()
    {
        $geofence = $this->geofenceService->create($this->data);

        return ['status' => 1] + FractalTransformer::item($geofence, GeofenceMapTransformer::class)->toArray();
    }

    public function update()
    {
        $geofence = Geofence::find($this->data['id']);

        $this->geofenceService->edit($geofence, $this->data);

        return ['status' => 1] + FractalTransformer::item($geofence, GeofenceMapTransformer::class)->toArray();
    }

    public function destroy($id = null)
    {
        $ids = $this->data['geofence_id'] ?? ($this->data['id'] ?? $id);

        if ($ids === null) {
            return ['status' => 0];
        }

        if (is_scalar($ids)) {
            $ids = (array)$ids;
        }

        $items = Geofence::findMany($ids);

        foreach ($items as $item) {
            $this->geofenceService->remove($item);
        }

        return [
            'status'    => 1,
            'ids'       => $items->pluck('id')->all(),
            'trigger'   => 'geofences.delete',
        ];
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

        (new GroupModelService($this->user->geofences()))->changeActive(
            $ids,
            $groupIds,
            $active
        );

        return [
            'status'    => 1,
            'ids'       => $ids,
            'groupIds'  => $groupIds,
            'active'    => $active,
            'trigger'   => 'geofences.change_active'
        ];
    }
}
