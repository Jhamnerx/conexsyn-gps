<?php namespace ModalHelpers;

use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Validators\SendCommandFormValidator;
use Illuminate\Support\Arr;
use Tobuli\Entities\UserGprsTemplate;
use Tobuli\Entities\UserSmsTemplate;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Protocols\Commands;
use Tobuli\Services\Commands\CommandService;
use Tobuli\Services\Commands\SendCommandService;
use Tobuli\Services\EntityLoader\UserDevicesGroupLoader;
use Tobuli\Services\EntityLoader\UserDevicesLoader;
use Validator;

class SendCommandModalHelper extends ModalHelper
{
    /**
     * @var SendCommandService
     */
    private $sendCommandService;

    /**
     * @var CommandService
     */
    private $commandService;

    /**
     * @var UserDevicesGroupLoader
     */
    private $devicesLoader;

    public function __construct()
    {
        parent::__construct();

        $this->sendCommandService = new SendCommandService();
        $this->commandService = new CommandService($this->user);
        $this->devicesLoader = new UserDevicesGroupLoader($this->user);
    }

    public function createData()
    {
        $this->checkException('send_command', 'view');

        $sms_templates = UserSmsTemplate::userAccessible($this->user)
            ->orderBy('title')
            ->pluck('title', 'id')
            ->prepend(trans('front.no_template'), '0')
            ->all();

        $gprs_templates = UserGprsTemplate::userAccessible($this->user)
            ->orderBy('title')
            ->pluck('title', 'id')
            ->prepend(trans('front.no_template'), '0')
            ->all();

        $devices_gprs = [];
        $devices_sms = $this->user->devices_sms()->count();

        $device_id = request()->get('id');

        $command_schedules = $this->user->commandSchedules;

        return compact('devices_sms', 'devices_gprs', 'sms_templates', 'gprs_templates',
            'device_id', 'command_schedules');
    }

    public function create()
    {
        $this->checkException('send_command', 'view');

        if ( ! $this->user->sms_gateway)
            throw new ValidationException(['id' => trans('front.sms_gateway_disabled')]);

        SendCommandFormValidator::validate('sms', $this->data);


        $this->devicesLoader->setRequestKey('devices');

        if (!$this->devicesLoader->hasAttach()) {
            throw new ValidationException(['devices' => trans('global.not_found')]);
        }

        $devices = $this->devicesLoader->getQuerySelected()->get();

        if ($devices->isEmpty()) {
            throw new ValidationException(['devices' => trans('global.not_found')]);
        }

        $this->sendCommandService->sms($devices, $this->data, $this->user);

        return ['status' => 0, 'trigger' => 'send_command'];
    }

    public function gprsCreate()
    {
        $this->checkException('send_command', 'view');

        SendCommandFormValidator::validate('gprs', $this->data);

        $this->devicesLoader->setRequestKey('devices');

        if (!$this->devicesLoader->hasAttach()) {
            throw new ValidationException(['devices' => trans('global.not_found')]);
        }

        $devices = $this->devicesLoader->getQuerySelected()->get();

        if ($devices->isEmpty()) {
            throw new ValidationException(['devices' => trans('global.not_found')]);
        }

        $this->commandService->validateGPRS($devices, $this->data);

        $responses = $this->sendCommandService->gprs($devices, $this->data, $this->user);

        $errors = $responses
            ->filter(function ($response) {
                return $response['status'] == 0;
            })
            ->map(function ($response) {
                return "{$response['device']}: {$response['error']}";
            });

        if (count($errors) > 0) {
            return [
                'status'   => 0,
                'trigger'  => 'send_command',
                'warnings' => $errors,
                'results' => $responses,
            ];
        }

        return [
            'status'  => 0,
            'trigger' => 'send_command',
            'message' => trans('front.command_sent') . ' ' . trans('global.successful'),
            'results' => $responses,
        ];
    }

    function getDeviceSimNumber()
    {
        $id = array_key_exists('device_id', $this->data) ? $this->data['device_id'] : $this->data['id'];

        $item = DeviceRepo::find($id);

        $this->checkException('devices', 'own', $item);

        return ['sim_number' => $item->sim_number];
    }

    function getDeviceCommands()
    {
        $this->devicesLoader->setRequestKey('devices');

        if (!$this->devicesLoader->hasAttach())
            return [];

        return $this->commandService->getCommands($this->devicesLoader, true, Arr::get($this->data, 'connection'));
    }

    public function getCommands($devices)
    {
        return $this->commandService->getCommands($devices);
    }
}