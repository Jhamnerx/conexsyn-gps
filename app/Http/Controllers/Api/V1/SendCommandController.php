<?php namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\CommandTemplate;
use Tobuli\Entities\UserGprsTemplate;
use Tobuli\Entities\UserSmsTemplate;
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

    public function index(Request $request)
    {
        $this->checkException('send_command', 'view');

        $noTemplate = new CommandTemplate();
        $noTemplate->id = 0;
        $noTemplate->title = trans('front.no_template');
        $noTemplate->message = null;

        $sms_templates = UserSmsTemplate::userAccessible($this->user)
            ->orderBy('title')
            ->get()
            ->prepend($noTemplate)
            ->map(function ($template) {
                return ['id' => $template->id, 'title' => $template->title, 'message' => $template->message];
            })
            ->all();

        $gprs_templates = UserGprsTemplate::userAccessible($this->user)
            ->orderBy('title')
            ->get()
            ->prepend($noTemplate)
            ->map(function ($template) {
                return ['id' => $template->id, 'title' => $template->title, 'message' => $template->message];
            })
            ->all();

        $devices_sms = $this->user->devices_sms
            ->map(function ($device) {
                return ['id' => $device->id, 'value' => $device->name];
            })
            ->all();

        $devices_gprs = $this->user->devices
            ->map(function ($device) {
                return ['id' => $device->id, 'value' => $device->name];
            })
            ->all();


        /**
         * @deprecated
         */
        $commands = apiArray([
            'engineStop' => trans('front.engine_stop'),
            'engineResume' => trans('front.engine_resume'),
            'alarmArm' => trans('front.alarm_arm'),
            'alarmDisarm' => trans('front.alarm_disarm'),
            'positionSingle' => trans('front.position_single'),
            'positionPeriodic' => trans('front.periodic_reporting'),
            'positionStop' => trans('front.stop_reporting'),
            'movementAlarm' => trans('front.movement_alarm'),
            'setTimezone' => trans('front.set_timezone'),
            'rebootDevice' => trans('front.reboot_device'),
            'sendSms' => trans('front.send_sms'),
            'requestPhoto' => trans('front.request_photo'),
            'custom' => trans('front.custom_command'),
        ]);

        $units = apiArray([
            'second' => trans('front.second'),
            'minute' => trans('front.minute'),
            'hour' => trans('front.hour')
        ]);

        $number_index = apiArray([
            '1' => trans('front.first'),
            '2' => trans('front.second'),
            '3' => trans('front.third'),
            '0' => trans('front.three_sos_numbers'),
        ]);

        $actions = apiArray([
            '1' => trans('front.on'),
            '0' => trans('front.off'),
        ]);

        return compact('devices_sms', 'devices_gprs', 'sms_templates',
            'gprs_templates', 'commands', 'units', 'number_index', 'actions');
    }

    public function sendSMS(Request $request)
    {
        $this->checkException('send_command', 'view');

        if (!$this->user->sms_gateway)
            throw new ValidationException(['id' => trans('front.sms_gateway_disabled')]);

        $data = $request->all();

        $data['message'] = $data['message_sms'] ?? ($data['message'] ?? '');

        $limit = config('tobuli.limits.command_sms_devices');

        $validator = Validator::make($data, [
            'devices' => 'required|array' . ($limit ? "|array_max:$limit" : ""),
            'message' => 'required_if:type,custom',
            'gprs_template_id' => 'required_if:type,template'
        ]);

        if ($validator->fails())
            throw new ValidationException($validator->messages());

        $devices = $this->user->devices()->with('users')->findMany($this->data['devices']);

        if ($devices->isEmpty()) {
            throw new ValidationException(['device_id' => trans('global.not_found')]);
        }

        $this->sendCommandService->sms($devices, $this->data, $this->user);

        return ['status' => 1];
    }

    public function sendGPRS(Request $request)
    {
        $this->checkException('send_command', 'view');

        $data = $request->all();

        if (isset($data['device_id']) && !is_array($data['device_id']))
            $data['device_id'] = [$data['device_id']];

        $limit = config('tobuli.limits.command_gprs_devices');

        $validator = Validator::make($data, [
            'device_id' => 'required|array' . ($limit ? "|array_max:$limit" : ""),
            'type'      => 'required'
        ]);

        if ($validator->fails())
            throw new ValidationException($validator->messages());


        $devices = $this->user->devices()->findMany($data['device_id']);

        if ($devices->isEmpty()) {
            throw new ValidationException(['device_id' => trans('global.not_found')]);
        }

        $this->commandService->validateGPRS($devices, $data);

        $responses = $this->sendCommandService->gprs($devices, $data, $this->user);

        $errors = $responses
            ->filter(function ($response) {
                return $response['status'] == 0;
            })
            ->map(function ($response) {
                return "{$response['device']}: {$response['error']}";
            });

        if ($errors->isNotEmpty()) {
            return ['status' => 0, 'error' => $errors->first()];
        }

        return ['status' => 1, 'message' => trans('front.command_sent')];
    }

    public function getCommands(Request $request)
    {
        $data = $request->all();

        if (isset($data['device_id']) && !is_array($data['device_id']))
            $data['device_id'] = [$data['device_id']];

        $limit = config('tobuli.limits.command_gprs_devices');

        $validator = Validator::make($data, [
            'device_id' => 'required|array' . ($limit ? "|array_max:$limit" : ""),
        ]);

        if ($validator->fails())
            throw new ValidationException($validator->messages());

        $devices = $this->user->devices()->findMany($request->get('device_id'));

        if ($request->get('connection') == SendCommandService::CONNECTION_SMS)
            return $this->commandService->getSmsCommands($devices, true);

        return $this->commandService->getGprsCommands($devices, true);
    }

}
