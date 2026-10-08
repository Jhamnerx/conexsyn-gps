<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Tobuli\Entities\Device;
use Tobuli\Entities\SentCommand;
use Tobuli\Entities\User;
use Tobuli\Entities\UserGprsTemplate;
use Tobuli\Protocols\Protocols\BaseProtocol;
use Tobuli\Services\Commands\SendCommandService;

class DeviceServerReconfigCommand extends Command
{
    private const LIFESPAN = 24 * 3600;
    private const PERIOD = 3600;
    private const MAX_COMMANDS = 10;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devices:server_reconfig';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send server reconfiguration GPRS commands to devices.';

    private SendCommandService $sendCommandService;
    private BaseProtocol $baseProtocol;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->sendCommandService = new SendCommandService();
        $this->baseProtocol = new BaseProtocol();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        setActingUser(User::find(User::getGodID()));

        $devices = Device::whereIn('imei', fn (Builder $q) => $q
            ->select('dip.imei')
            ->from('device_ip_log', 'dip')
            ->whereRaw('DATE_ADD(dip.created_at, INTERVAL ' . self::LIFESPAN . ' SECOND) >= NOW()')
        )->get()
            ->filter(fn (Device $device) => $device->isConnected());

        if ($devices->isEmpty()) {
            $this->line('No devices');

            return self::SUCCESS;
        }

        $templates = UserGprsTemplate::where('title', 'LIKE', '!SERVER_RECONFIG%')
            ->where('user_id', User::getGodID())
            ->byDevices($devices)
            ->get();

        $this->line('Devices count: ' . $devices->count());
        $this->line('Command templates count: ' . $templates->count());
        $this->newLine(2);

        foreach ($templates as $template) {
            foreach ($devices as $device) {
                $this->comment("$device->name - $template->title");

                if ($this->isPeriodicallyValid($template, $device)) {
                    $this->send($template, $device);
                }

                $this->newLine();
            }
        }

        return self::SUCCESS;
    }

    private function send(UserGprsTemplate $template, Device $device): void
    {
        $command = $this->baseProtocol->initGprsTemplateCommand($template, false);

        $responses = $this->sendCommandService->gprs($device, $command, $template->user);

        foreach ($responses as $response) {
            $response['status']
                ? $this->info('Command sent')
                : $this->error('Command sending failed');

            $this->line(json_encode($response));
        }
    }

    private function isPeriodicallyValid(UserGprsTemplate $template, Device $device): bool
    {
        if (!$template->isAdaptedFromDevice($device)) {
            $this->line('Template not supported by device');

            return false;
        }

        $sentCommands = $device->sentCommands()
            ->where('status', 1)
            ->where('template_id', $template->id)
            ->where('created_at', '>=', Carbon::now()->subSeconds(self::LIFESPAN))
            ->get();

        if ($sentCommands->count() >= self::MAX_COMMANDS) {
            $this->line('Max amount of commands already sent');

            return false;
        }

        $periodStart = Carbon::now()->subSeconds(self::PERIOD);

        $recentlyDelivered = $sentCommands->filter(
            fn (SentCommand $sent) => $sent->created_at->gte($periodStart)
        )->count();

        if ($recentlyDelivered) {
            $this->line('This command was recently delivered');

            return false;
        }

        return true;
    }
}
