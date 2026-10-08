<?php

namespace App\Http\Controllers\Api\Tracker;

use GuzzleHttp\Exception\ClientException;
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

    public function login()
    {
        $url = null;

        if (empty($this->deviceInstance->protocol) || $this->deviceInstance->protocol == 'osmand') {
            $port = TrackerPort::active()->where('name', 'osmand')->value('port');
            $url = (new Tracker())->getUrl(true) . ($port ? ":$port" : "");
        }

        return response()->json([
            'success' => true,
            'data' => [
                'url'       => $url,
                'device_id' => $this->deviceInstance->id,
                'channel'   => $this->deviceInstance->getSocketChannel(),
            ]
        ]);
    }

    public function setFcmToken(FcmService $fcmService)
    {
        $input = request()->all();

        $validator = Validator::make($input, ['token' => 'required']);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $fcmService->setFcmToken($this->deviceInstance, $input['token'], $input['project_id'] ?? null);

        return response()->json(['status' => 1, 'data' => $this->deviceInstance->fcmTokens()]);
    }

    public function testFcmToken(FcmService $fcmService)
    {
        $input = request()->all();

        $validator = Validator::make($input, ['token' => 'required']);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $token = $this->deviceInstance->fcmTokens()->where('token', $input['token'])->first();

        try {
            $fcmService->sendToTokens([$token], 'Test title', 'Test body');
        } catch (ClientException $e) {
            $error = $e->getResponse()->getBody()->getContents();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return response()->json([
            'status' => empty($error)
        ]);
    }
}