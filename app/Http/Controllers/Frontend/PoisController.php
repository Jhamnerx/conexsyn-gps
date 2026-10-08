<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Transformers\Poi\PoiMapTransformer;
use FractalTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\MapIcon;
use Tobuli\Entities\Poi;
use Tobuli\Entities\PoiGroup;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\GroupModelService;
use Tobuli\Services\PoiUserService;

class PoisController extends Controller
{
    protected PoiUserService $service;

    protected function afterAuth($user)
    {
        $this->service = new PoiUserService($this->user);
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
        $this->checkException('poi', 'view');

        $sort = $this->data['sorting'] ?? [];
        $sortCol = $sort['sort_by'] ?? 'name';
        $sortDir = $sort['sort'] ?? 'asc';

        $items = Poi::userOwned($this->user)
            ->search($this->data['search_phrase'] ?? null)
            ->toPaginator(15, $sortCol, $sortDir);

        return view('front::Pois.' . $view)->with(compact('items'));
    }

    public function index()
    {
        $this->checkException('poi', 'view');

        $items = Poi::userOwned($this->user)->with(['mapIcon'])->paginate(500);

        return response()->json(
            FractalTransformer::paginate($items, PoiMapTransformer::class)->toArray()
        );
    }

    public function create()
    {
        $this->checkException('poi', 'store');

        $data = $this->getFormData();

        return view('front::Pois.create')->with($data);
    }

    public function store(Request $request)
    {
        $item = $this->service->create($request->all());

        return ['status' => 1] + FractalTransformer::item($item, PoiMapTransformer::class)->toArray();
    }

    public function edit(int $id)
    {
        $item = Poi::find($id);

        $this->checkException('poi', 'edit', $item);

        $data = $this->getFormData();
        $data['item'] = $item;

        return view('front::Pois.edit')->with($data);
    }

    public function update(Request $request, ?int $id = null)
    {
        $item = Poi::find($id);

        $this->service->edit($item, $request->all());

        return ['status' => 1] + FractalTransformer::item($item, PoiMapTransformer::class)->toArray();
    }

    private function getFormData(): array
    {
        $mapIcons = MapIcon::all();

        $poiGroups = PoiGroup::where(['user_id' => $this->user->id])->get()
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        return compact('mapIcons', 'poiGroups');
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

        (new GroupModelService($this->user->pois()))->changeActive(
            $ids,
            $groupIds,
            $active
        );

        return [
            'status'    => 1,
            'ids'       => $ids,
            'groupIds'  => $groupIds,
            'active'    => $active,
            'trigger'   => 'pois.change_active'
        ];
    }

    public function destroy($id = null)
    {
        $ids = $this->data['id'] ?? $id;

        if ($ids === null) {
            return ['status' => 0];
        }

        if (is_scalar($ids)) {
            $ids = (array)$ids;
        }

        $items = Poi::findMany($ids);

        foreach ($items as $item) {
            $this->service->remove($item);
        }

        return [
            'status'    => 1,
            'ids'       => $items->pluck('id')->all(),
            'trigger'   => 'pois.delete',
        ];
    }
}
