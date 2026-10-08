<?php

namespace App\Http\Controllers;

use App\Exceptions\PermissionException;
use App\Exceptions\ResourseNotFoundException;
use App\Http\Controllers\Frontend\AddressController;
use App\Transformers\Device\DeviceMapFullTransformer;
use App\Transformers\Device\DeviceMapTransformer;
use Illuminate\Support\Facades\Config;
use Tobuli\Entities\Sharing;
use Tobuli\Services\FractalSerializers\DataArraySerializer;
use Tobuli\Services\FractalSerializers\WithoutDataArraySerializer;
use Tobuli\Services\FractalTransformerService;
use FractalTransformer;

class SharingController extends Controller
{
    private $sharing;

    /**
     * @var FractalTransformerService
     */
    private $transformerService;

    public function __construct(FractalTransformerService $transformerService)
    {
        parent::__construct();

        $this->sharing = Sharing::where('hash', request()->route()->parameter('hash'))->first();

        if (!$this->sharing || !$this->sharing->isActive()) {
            throw new ResourseNotFoundException(trans('admin.map'));
        }

        if ( ! $this->sharing->user) {
            throw new ResourseNotFoundException(trans('global.user'));
        }

        if ( ! $this->sharing->user->perm('sharing', 'view')) {
            throw new PermissionException();
        }

        setActingUser($this->sharing->user);

        $this->transformerService = $transformerService->setSerializer(WithoutDataArraySerializer::class);
    }

    public function index($hash)
    {
        $devices = $this->transformerService
            ->collection($this->sharing->activeDevices, DeviceMapFullTransformer::class)
            ->toArray();

        return view('front::Layouts.sharing')->with(compact('devices'));
    }

    public function devices()
    {
        $cursor = request()->get('cursor');

        $devices = $this->sharing->activeDevices()
            ->filter(request()->all())
            ->wasConnected()
            ->clearOrdersBy()
            //optizime first page load
            ->when(!$cursor, function($query) {
                $query->where('devices.id', '>', 0);
            })
            ->cursorPaginate(500);

        $transformer = request()->get('full') ? DeviceMapFullTransformer::class : DeviceMapTransformer::class;

        return response()->json(
            $this->transformerService->cursorPaginate($devices, $transformer)->toArray()
        );
    }

    public function devicesLatest()
    {
        $time = intval(($time = request()->get('time')) ? $time : time() - 5);

        $items = $this->sharing->activeDevices()
            ->connectedAfter(date('Y-m-d H:i:s', $time))
            ->clearOrdersBy()
            ->cursorPaginate(1000)
            ->appends(['time' => $time]);

        return [
            'items' => $this->transformerService
                ->setSerializer(new DataArraySerializer())
                ->cursorPaginate($items, DeviceMapTransformer::class)->toArray(),
            'events' => [],
            'time' => $time,
            'version' => Config::get('tobuli.version')
        ];
    }

    public function address()
    {
        return app(AddressController::class)->get();
    }
}
