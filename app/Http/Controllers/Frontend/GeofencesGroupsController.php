<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Transformers\ApiV1\AbstractGroupTransformer;
use Illuminate\Http\Request;
use Tobuli\Entities\Geofence;
use Tobuli\Entities\GeofenceGroup;
use Tobuli\Services\FractalSerializers\WithoutDataArraySerializer;
use Tobuli\Services\GeofenceGroupService;
use Tobuli\Services\UserOpenGroupService;
use CustomFacades\Validators\GeofenceGroupFormValidator;

class GeofencesGroupsController extends Controller
{
    protected GeofenceGroupService $geofenceGroupService;

    public function __construct(GeofenceGroupService $geofenceGroupService)
    {
        parent::__construct();

        $this->geofenceGroupService = $geofenceGroupService;
    }

    public function index()
    {
        $this->checkException('geofences_groups', 'view');

        $items = GeofenceGroup::userOwned($this->user)
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
        $this->checkException('geofences_groups', 'create');

        $data = [
            'geofences' => Geofence::where('user_id', $this->user->id)->get(),
        ];

        return view('front::GeofencesGroups.create')->with($data);
    }

    public function store(Request $request)
    {
        $this->checkException('geofences_groups', 'store');

        $data = array_merge($request->all(), ['user_id' => $this->user->id]);

        GeofenceGroupFormValidator::validate('create', $request->all());

        $item = $this->geofenceGroupService->create($data);

        if (isset($data['geofences'])) {
            $this->geofenceGroupService->syncItems($item, $data['geofences']);
        }

        return response()->json(['status' => 1, 'id' => $item->id]);
    }

    public function edit($id)
    {
        $item = GeofenceGroup::find($id);

        $this->checkException('geofences_groups', 'edit', $item);

        $data = [
            'item' => $item,
            'geofences' => Geofence::where('user_id', $this->user->id)->get(),
        ];

        return view('front::GeofencesGroups.edit')->with($data);
    }

    public function update(Request $request, $id)
    {
        $item = GeofenceGroup::find($id);

        $this->checkException('geofences_groups', 'edit', $item);

        GeofenceGroupFormValidator::validate('update', $request->all());

        $data = array_merge($request->all(), ['user_id' => $this->user->id]);

        $this->geofenceGroupService->update($item, $data);

        if (isset($data['geofences'])) {
            $this->geofenceGroupService->syncItems($item, $data['geofences']);
        }

        return response()->json([
            'id'     => $item->id,
            'status' => 1,
        ]);
    }

    public function changeStatus()
    {
        $id = $this->data['id'];

        if ($id) {
            $item = GeofenceGroup::find($id);

            $this->checkException('geofences_groups', 'active', $item);
        }

        (new UserOpenGroupService($this->user->geofenceGroups()))
            ->changeStatus($id);

        return response()->json([
            'status' => 1,
        ]);
    }
}
