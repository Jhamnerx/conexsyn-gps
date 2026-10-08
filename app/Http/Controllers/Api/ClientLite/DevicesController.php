<?php


namespace App\Http\Controllers\Api\ClientLite;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Transformers\ClientLite\DeviceFullTransformer;
use App\Transformers\ClientLite\DeviceGroupTransformer;
use App\Transformers\ClientLite\DeviceListTransformer;
use App\Transformers\ClientLite\DeviceMapTransformer;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\DeviceGroup;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\DeviceUsersService;
use Tobuli\Services\FractalTransformerService;

class DevicesController extends Controller
{
    const KEY_SEARCH = 'search';
    const KEY_TIME = 'time';

    protected $transformerService;

    public function __construct(FractalTransformerService $transformerService)
    {
        parent::__construct();

        $this->transformerService = $transformerService;
    }

    protected function afterAuth($user)
    {
        $this->checkException('devices', 'view');
    }

    public function groups(Request $request)
    {
        $search = $request->get(self::KEY_SEARCH);

        $groups = DeviceGroup::userOwned($this->user)
            ->when($search, function($query) use ($search) {
                $query->search($search);
            })
            ->whereHas('items')
            ->withCount(['items', 'itemsVisible'])
            ->orderBy('title')
            ->paginate();

        if ($request->get('page', 1) <= 1 && !$search) {
            $ungrouped = DeviceGroup::makeUngroupedWithCount($this->user);

            if ($ungrouped->items_count) {
                $groups->prepend($ungrouped);
            }
        }

        return response()->json(
            $this->transformerService->paginate($groups, DeviceGroupTransformer::class)->toArray()
        );
    }

    public function devices(Request $request)
    {
        $devices = $this->getDevicesQuery($request)
            ->paginate();

        return response()->json(
            $this->transformerService->paginate($devices, DeviceListTransformer::class)->toArray()
        );
    }

    public function map(Request $request)
    {
        $devices = $this->getDevicesQuery($request)
            ->wasConnected()
            ->visible()
            ->clearOrdersBy()
            ->cursorPaginate(100);

        return response()->json(
            $this->transformerService->cursorPaginate($devices, DeviceMapTransformer::class)->toArray()
        );
    }

    public function latest(Request $request)
    {
        $time = empty($this->data[self::KEY_TIME]) ? time() - 5 : intval($this->data[self::KEY_TIME]);

        $devices = $this->getDevicesQuery($request)
            ->updatedAfter(date('Y-m-d H:i:s', $time))
            ->clearOrdersBy()
            ->cursorPaginate(500);

        return response()->json(
            $this->transformerService->cursorPaginate($devices, DeviceMapTransformer::class)->toArray()
            +
            [self::KEY_TIME => time()]
        );
    }

    public function get(Request $request, $device_id)
    {
        $device = $this->user->devices()->find($device_id);

        $this->checkException('devices', 'show', $device);

        return response()->json(
            $this->transformerService->item($device, DeviceFullTransformer::class)->toArray()
        );
    }

    public function active(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required_without:group_id|numeric',
            'group_id' => 'required_without:device_id|numeric',
            'active' => 'required|boolean'
        ]);

        if ($validator && $validator->fails())
            throw new ValidationException($validator->errors());

        $service = new DeviceUsersService();

        if ($request->has('group_id')) {
            $service->setVisibleGroups(
                $this->user,
                $request->get('group_id'),
                $request->get('active')
            );
        } else {
            $service->setVisibleDevices(
                $this->user,
                $request->get('device_id'),
                $request->get('active')
            );
        }
        
        return response()->json(null, 201);
    }

    protected function getDevicesQuery(Request $request)
    {
        $search = $request->get(self::KEY_SEARCH);

        return $this->user
            ->devices()
            ->filter($request->all())
            ->when($search, function($query) use ($search) {
                $query->search($search);
            });
    }
}