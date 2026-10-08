<?php namespace App\Http\Controllers\Frontend;

use App\Exceptions\ResourseNotFoundException;
use App\Http\Controllers\Controller;
use CustomFacades\Validators\SchedulesValidator;
use CustomFacades\Validators\SendCommandFormValidator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tobuli\Entities\CommandSchedule;
use Tobuli\Entities\Device;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Protocols\Commands;
use Tobuli\Services\Commands\CommandService;
use Tobuli\Services\Commands\SendCommandService;
use Tobuli\Services\EntityLoader\UserDevicesLoader;
use Tobuli\Services\Schedule\Scheduler;

class CommandSchedulesController extends Controller
{
    private $scheduler;

    private UserDevicesLoader $devicesLoader;

    public function __construct(Scheduler $scheduler)
    {
        parent::__construct();

        $this->scheduler = $scheduler;

        $this->middleware(function ($request, $next) {
            $this->checkException('send_command', 'view');

            return $next($request);
        });
    }


    protected function afterAuth($user)
    {
        $this->devicesLoader = new UserDevicesLoader($user);
        $this->devicesLoader->setRequestKey(request('request_key', 'devices'));
    }

    public function index()
    {
        return view('front::SendCommand.schedule.index', [
            'command_schedules' => $this->user->commandSchedules,
        ]);
    }

    public function table()
    {
        return view('front::SendCommand.schedule.table', [
            'command_schedules' => $this->user->commandSchedules,
        ]);
    }

    public function create()
    {
        return view('front::SendCommand.schedule.create')
            ->with([
                'connections' => [
                    'gprs' => trans('front.gprs'),
                    'sms'  => trans('front.sms'),
                ],
                'devices_sms' => $this->user->devices_sms()->count(),
            ]);
    }

    public function store(Request $request)
    {
        SchedulesValidator::validate('create', $request->all());

        beginTransaction();
        try {
            if ($this->isCommandGprs(request('connection'))) {
                $command_schedule = $this->createGprs($request->all());
            } else {
                $command_schedule = $this->createSms($request->all());
            }

            $command_schedule->devices()->syncLoader($this->devicesLoader);

            $this->scheduler->create($command_schedule, $request->all(), $this->user);
        } catch (ValidationException $e) {
            rollbackTransaction();
            throw $e;
        }

        commitTransaction();

        return ['status' => 1];
    }

    public function edit($schedule)
    {
        $commandSchedule = CommandSchedule::with(['schedule'])->find($schedule);

        if ( ! $commandSchedule)
            throw new ResourseNotFoundException('front.command_schedule');

        $this->checkException(CommandSchedule::class, 'own', $commandSchedule);

        return view('front::SendCommand.schedule.edit', [
            'command_schedule' => $commandSchedule,
            'connections'      => [
                'gprs' => trans('front.gprs'),
                'sms'  => trans('front.sms'),
            ],
            'devices_sms' => $this->user->devices_sms()->count(),
        ]);
    }

    public function update(Request $request, $schedule)
    {
        SchedulesValidator::validate('update', $request->all());

        $command_schedule = CommandSchedule::find($schedule);

        if (is_null($command_schedule))
            throw new ResourseNotFoundException('front.command_schedule');

        $this->checkException(CommandSchedule::class, 'own', $command_schedule);

        beginTransaction();
        try {
            if ($this->isCommandGprs(request('connection'))) {
                $this->updateGprs($command_schedule, $request->all());
            } else {
                $this->updateSms($command_schedule, $request->all());
            }

            $command_schedule->devices()->syncLoader($this->devicesLoader);

            $this->scheduler->update($command_schedule, $request->all());

        } catch (ValidationException $e) {
            rollbackTransaction();
            throw $e;
        }

        commitTransaction();

        return ['status' => 1];
    }

    public function destroy($schedule)
    {
        if (is_null($command_schedule = CommandSchedule::find($schedule)))
            throw new ResourseNotFoundException('front.command_schedule');

        $this->checkException(CommandSchedule::class, 'own', $command_schedule);

        if ($command_schedule->schedule)
            $command_schedule->schedule->delete();
        
        $command_schedule->delete();

        return ['status' => 1];
    }

    public function logs($schedule)
    {
        $command_schedule = CommandSchedule::find($schedule);

        if (is_null($command_schedule))
            throw new ResourseNotFoundException('front.command_schedule');

        $this->checkException(CommandSchedule::class, 'own', $command_schedule);

        return view('front::SendCommand.schedule.' . (request()->filled('page') ? 'logs_table' : 'logs'), [
            'logs'             => $command_schedule->sentCommands()->latest()->paginate(15),
            'command_schedule' => $command_schedule,
        ]);
    }

    public function devices(Request $request, $schedule = null)
    {
        if ($commandSchedule = CommandSchedule::with(['schedule'])->find($schedule)) {
            $this->checkException(CommandSchedule::class, 'own', $commandSchedule);

            $this->devicesLoader->setQueryStored($commandSchedule->devices());
        }

        if ($request->get('type') === 'sms') {
            $this->devicesLoader->setQueryItems(
                $this->devicesLoader->getQueryItems()->where('sim_number', '!=', '')
            );
        }

        $this->devicesLoader->setOrderStored(true);

        return response()->json($this->devicesLoader->get());
    }

    public function commands(Request $request, $schedule = null)
    {
        if ($commandSchedule = CommandSchedule::with(['schedule'])->find($schedule)) {
            $this->checkException(CommandSchedule::class, 'own', $commandSchedule);

            $this->devicesLoader->setQueryStored($commandSchedule->devices());
        }

        if (!$this->devicesLoader->getQuerySelected()->count())
            return [];

        $commandService = new CommandService($this->user);

        return $commandService->getCommands($this->devicesLoader, true, $request->get('connection'));
    }

    private function createGprs($data)
    {
        SendCommandFormValidator::validate('gprs', $data);

        $command_schedule = CommandSchedule::create([
            'user_id'    => $this->user->id,
            'connection' => SendCommandService::CONNECTION_GPRS,
            'command'    => $data['type'],
            'parameters' => $this->getGprsParameters($data),
        ]);

        return $command_schedule;
    }

    private function createSms($data)
    {
        SendCommandFormValidator::validate('sms', $data);

        return CommandSchedule::create([
            'user_id'    => $this->user->id,
            'connection' => SendCommandService::CONNECTION_SMS,
            'command'    => request('type'),
            'parameters' => $this->getSmsParameters($data),
        ]);
    }

    private function updateGprs($command_schedule, $data)
    {
        SendCommandFormValidator::validate('gprs', $data);

        $command_schedule->update([
            'connection' => SendCommandService::CONNECTION_GPRS,
            'command'    => request('type'),
            'parameters' => $this->getGprsParameters($data),
        ]);
    }

    private function updateSms($command_schedule, $data)
    {
        $data['message'] = Arr::get($data, 'message_sms');

        SendCommandFormValidator::validate('sms', $data);

        $command_schedule->update([
            'connection' => SendCommandService::CONNECTION_SMS,
            'command'    => request('type'),
            'parameters' => $this->getSmsParameters($data),
        ]);
    }

    private function getGprsParameters($data)
    {
        if (Str::startsWith($data['type'], 'template_'))
            list($data['type'], $data['template_id']) = explode('_', $data['type']);

        $command = (new Commands())->get($data['type']);

        $parameters = [];

        if (isset($command['attributes']))
            $parameters = $command['attributes']->map(function ($attribute) {
                return $attribute->getName();
            })->all();

        if ($data['type'] == 'template')
            $parameters[] = 'data';

        $keys = array_intersect(array_keys($data), [
            Commands::KEY_DATA,
            Commands::KEY_INDEX,
            Commands::KEY_DEVICE_PASSWORD,
            Commands::KEY_ENABLE,
            Commands::KEY_FREQUENCY,
            Commands::KEY_MESSAGE,
            Commands::KEY_PHONE,
            Commands::KEY_PORT,
            Commands::KEY_RADIUS,
            Commands::KEY_TIMEZONE,
            Commands::KEY_UNIT,
        ]);

        if ($keys)
            $parameters = array_merge($parameters, $keys);

        return empty($parameters) ? null : Arr::only($data, $parameters) ;
    }

    private function getSmsParameters($data)
    {
        return Arr::only($data, 'message');
    }

    private function isCommandGprs($connection)
    {
        return $connection == SendCommandService::CONNECTION_GPRS;
    }

    private function validDeviceIds($device_ids)
    {
        $devices = Device::findMany($device_ids);

        foreach ($devices as $device)
        {
            if ( ! $this->user->own($device))
                unset($device_ids[$device->id]);
        }

        if (empty($device_ids))
            throw new AuthorizationException();

        return $device_ids;
    }
}
