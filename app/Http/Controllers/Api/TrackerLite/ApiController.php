<?php

namespace App\Http\Controllers\Api\TrackerLite;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\Device;
use Tobuli\Entities\TrackerPort;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\Tracker;
use Tobuli\Services\FcmService;

class ApiController extends Controller
{
    /**
     * @var Device
     */
    protected $deviceInstance;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->deviceInstance = app()->make(Device::class);

            return $next($request);
        });
    }

    public function config(Request $request)
    {
        $url = null;

        if (empty($this->deviceInstance->protocol) || $this->deviceInstance->protocol == 'osmand') {
            $port = TrackerPort::active()->where('name', 'osmand')->value('port');
            $url = (new Tracker())->getUrl(true) . ($port ? ":$port" : "");
        }

        return response()->json([
            'data' => [
                'url'       => $url,
                'device_id' => $this->deviceInstance->id,
                'imei'      => $this->deviceInstance->imei,
                'channel'   => $this->deviceInstance->getSocketChannel(),
                'socket'    => [
                    'url'     => url('/') . ':' . ($request->isSecure() ? '9002' : '9001'),
                    'channel' => $this->deviceInstance->getSocketChannel()
                ]
            ]
        ]);
    }

    public function setFcmToken(Request $request, FcmService $fcmService)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'project_id' => 'required',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $fcmService->setFcmToken($this->deviceInstance, $request->token, $request->project_id);

        return response()->json(['status' => 1, 'data' => $this->deviceInstance->fcmTokens()]);
    }
}