<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Transformers\ApiV1\AbstractGroupTransformer;
use CustomFacades\Validators\RouteGroupFormValidator;
use Illuminate\Http\Request;
use Tobuli\Entities\Route;
use Tobuli\Entities\RouteGroup;
use Tobuli\Services\FractalSerializers\WithoutDataArraySerializer;
use Tobuli\Services\RouteGroupService;
use Tobuli\Services\UserOpenGroupService;

class RoutesGroupsController extends Controller
{
    protected RouteGroupService $routeGroupService;

    public function __construct(RouteGroupService $routeGroupService)
    {
        parent::__construct();

        $this->routeGroupService = $routeGroupService;
    }

    public function index()
    {
        $this->checkException('route_groups', 'view');

        $items = RouteGroup::userOwned($this->user)
            ->search($this->data['search_phrase'] ?? null)
            ->toPaginator(
                $this->data['limit'] ?? 10,
                $this->data['sort_by'] ?? 'title',
                $this->data['sort'] ?? 'asc'
            );


        return \FractalTransformer::setSerializer(WithoutDataArraySerializer::class)
            ->paginate($items, AbstractGroupTransformer::class)
            ->toArray();
    }

    public function create()
    {
        $this->checkException('route_groups', 'create');

        $data = [
            'routes' => Route::where('user_id', $this->user->id)->get(),
        ];

        return view('front::RoutesGroups.create')->with($data);
    }

    public function store(Request $request)
    {
        $this->checkException('route_groups', 'store');

        $data = array_merge($request->all(), ['user_id' => $this->user->id]);

        RouteGroupFormValidator::validate('create', $request->all());

        $item = $this->routeGroupService->create($data);

        if (isset($data['routes'])) {
            $this->routeGroupService->syncItems($item, $data['routes']);
        }

        return response()->json(['status' => 1, 'id' => $item->id]);
    }

    public function edit($id)
    {
        $item = RouteGroup::find($id);

        $this->checkException('route_groups', 'edit', $item);

        $data = [
            'item' => $item,
            'routes' => Route::where('user_id', $this->user->id)->get(),
        ];

        return view('front::RoutesGroups.edit')->with($data);
    }

    public function update(Request $request, $id)
    {
        $item = RouteGroup::find($id);

        $this->checkException('route_groups', 'edit', $item);

        $data = array_merge($request->all(), ['user_id' => $this->user->id]);

        RouteGroupFormValidator::validate('update', $request->all());

        $this->routeGroupService->update($item, $data);

        if (isset($data['routes'])) {
            $this->routeGroupService->syncItems($item, $data['routes']);
        }

        return response()->json([
            'id'     => $item->id,
            'status' => 1,
        ]);
    }

    public function changeStatus(Request $request)
    {
        $id = $request->get('id');

        if ($id) {
            $item = RouteGroup::find($request->get('id'));

            $this->checkException('route_groups', 'active', $item);
        }

        (new UserOpenGroupService($this->user->routeGroups()))
            ->changeStatus($id);

        return response()->json([
            'status' => 1,
        ]);
    }
}
