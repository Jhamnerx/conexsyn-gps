<?php


namespace App\Http\Controllers\Api\ClientLite;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\Commands\CommandService;
use Tobuli\Services\Commands\SendCommandService;


class SendCommandController extends Controller
{
    /**
     * @var CommandService;
     */
    protected $commandService;

    /**
     * @var SendCommandService
     */
    protected $sendCommandService;

    protected function afterAuth($user)
    {
        $this->commandService = new CommandService($user);
        $this->sendCommandService = new SendCommandService();
    }

    public function view(Request $request)
    {
        $this->checkException('send_command', 'view');

        $validator = Validator::make($request->all(), [
            'connection' => 'required|in:' . implode(',', [
                SendCommandService::CONNECTION_SMS,
                SendCommandService::CONNECTION_GPRS]),
            'device_id' => 'required_without:group_id',
            'group_id'  => 'required_without:device_id'
        ]);

        if ($validator->fails())
            throw new ValidationException($validator->messages());

        $devices = $this->user->devices()
            ->filter($request->all())
            ->get();

        return response()->json([
            'data' => $this->commandService->getCommands($devices, true, $request->get('connection'))
        ]);
    }

    public function store(Request $request)
    {
        $this->checkException('send_command', 'view');

        $validator = Validator::make($request->all(), [
            'connection' => 'required|in:' . implode(',', [
                    SendCommandService::CONNECTION_SMS,
                    SendCommandService::CONNECTION_GPRS]),
            'device_id' => 'required_without:group_id',
            'group_id'  => 'required_without:device_id',
            'type'      => 'required'
        ]);

        if ($validator->fails())
            throw new ValidationException($validator->messages());

        $devices = $this->user->devices()
            ->filter($request->all())
            ->get();

        if ($devices->isEmpty()) {
            throw new ValidationException(['device_id' => trans('global.not_found')]);
        }

        $this->commandService->validate($devices, $request->all(), $request->get('connection'));

        $responses = $this->sendCommandService->send($devices, $request->all(), $this->user, $request->get('connection'));

        $errors = $responses
            ->filter(function ($response) {
                return $response['status'] == 0;
            })
            ->map(function ($response) {
                return "{$response['device']}: {$response['error']}";
            })
            ->values();

        if ($errors->isNotEmpty()) {
            return ['status' => 0, 'errors' => $errors];
        }

        return ['status' => 1, 'message' => trans('front.command_sent')];
    }
}