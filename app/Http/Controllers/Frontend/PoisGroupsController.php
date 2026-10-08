<?php namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Transformers\ApiV1\AbstractGroupTransformer;
use CustomFacades\Validators\PoiGroupFormValidator;
use Illuminate\Http\Request;
use Tobuli\Entities\Poi;
use Tobuli\Entities\PoiGroup;
use Tobuli\Services\FractalSerializers\WithoutDataArraySerializer;
use Tobuli\Services\PoiGroupService;
use Tobuli\Services\UserOpenGroupService;

class PoisGroupsController extends Controller
{
    /**
     * @var PoiGroupService
     */
    protected $poiGroupService;

    public function __construct(PoiGroupService $poiGroupService)
    {
        parent::__construct();

        $this->poiGroupService = $poiGroupService;
    }

    public function index()
    {
        $this->checkException('pois_groups', 'view');

        $items = PoiGroup::userOwned($this->user)
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
        $this->checkException('pois_groups', 'create');

        $data = [
            'pois' => Poi::where('user_id', $this->user->id)->get(),
        ];

        return view('front::PoisGroups.create')->with($data);
    }

    public function store(Request $request)
    {
        $this->checkException('pois_groups', 'store');

        $data = array_merge($request->all(), ['user_id' => $this->user->id]);

        PoiGroupFormValidator::validate('create', $request->all());

        $item = $this->poiGroupService->create($data);

        if (isset($data['pois'])) {
            $this->poiGroupService->syncItems($item, $data['pois']);
        }

        return response()->json(['status' => 1, 'id' => $item->id]);
    }

    public function edit($id)
    {
        $item = PoiGroup::find($id);

        $this->checkException('pois_groups', 'edit', $item);

        $data = [
            'item' => $item,
            'pois' => Poi::where('user_id', $this->user->id)->get(),
        ];

        return view('front::PoisGroups.edit')->with($data);
    }

    public function update(Request $request, $id)
    {
        $item = PoiGroup::find($id);

        $this->checkException('pois_groups', 'edit', $item);

        $data = array_merge($request->all(), ['user_id' => $this->user->id]);

        PoiGroupFormValidator::validate('update', $request->all());

        $this->poiGroupService->update($item, $data);

        if (isset($data['pois'])) {
            $this->poiGroupService->syncItems($item, $data['pois']);
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
            $item = PoiGroup::find($request->get('id'));

            $this->checkException('pois_groups', 'active', $item);
        }

        (new UserOpenGroupService($this->user->poiGroups()))
            ->changeStatus($id);

        return response()->json([
            'status' => 1,
        ]);
    }
}
