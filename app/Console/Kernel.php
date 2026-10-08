<?php namespace App\Console;

use App\Jobs\DispatchMininterJobs;
use App\Jobs\DispatchSutranJobs;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\Inspire',
        'App\Console\Commands\BootTest',
        'App\Console\Commands\Tracker\RestartCommand',
        'App\Console\Commands\Tracker\ConfigCommand',
        'App\Console\Commands\Tracker\ConfigAliasCommand',
        'App\Console\Commands\Socket\SSLCertCommand',
        'App\Console\Commands\Socket\ServiceCommand',
        'App\Console\Commands\AutoCleanServerCommand',
        'App\Console\Commands\AutoCleanServerFilterCommand',
        'App\Console\Commands\BackupMysqlCommand',
        'App\Console\Commands\CheckAlertsCommand',
        'App\Console\Commands\CheckServerCommand',
        'App\Console\Commands\CheckServiceCommand',
        'App\Console\Commands\CheckServiceExpireCommand',
        'App\Console\Commands\CheckDeviceModelCacheCommand',
        'App\Console\Commands\CheckSchedulesCommand',
        'App\Console\Commands\CleanServerCommand',
        'App\Console\Commands\CleanReportLogCommand',
        'App\Console\Commands\CleanDevicesCommand',
        'App\Console\Commands\CleanEventsCommand',
        'App\Console\Commands\CleanUnregisteredDeviceLogCommand',
        'App\Console\Commands\CleanUserCommand',
        'App\Console\Commands\CleanDeviceCamerasCommand',
        'App\Console\Commands\CleanExpiredSharingsCommand',
        'App\Console\Commands\CleanModelCommand',
        'App\Console\Commands\ReportsDailyCommand',
        'App\Console\Commands\SendEventsCommand',
        'App\Console\Commands\ReportsCleanCommand',
        'App\Console\Commands\ServerTranslationsCommand',
        'App\Console\Commands\OptimizeServerDBCommand',
        'App\Console\Commands\CompressLogsCommand',
        'App\Console\Commands\UpdateIconsCommand',
        'App\Console\Commands\InsertCommand',
        'App\Console\Commands\CheckPositionsCommand',
        'App\Console\Commands\CheckTimeCommand',
        'App\Console\Commands\DevicesTimezoneResetCommand',
        'App\Console\Commands\DevicesTimezoneApplyCommand',
        'App\Console\Commands\CheckSubscriptionsCommand',
        'App\Console\Commands\CheckDevicesExpirationCommand',
        'App\Console\Commands\CheckOfflineDevices',
        'App\Console\Commands\CheckUsersExpirationCommand',
        'App\Console\Commands\SettingsCommand',
        'App\Console\Commands\ArchiveChecklistsCommand',
        'App\Console\Commands\KeyCreateCommand',
        'App\Console\Commands\GeoCacheSetupCommand',
        'App\Console\Commands\CopyDevicesCommand',
        'App\Console\Commands\CalcVirtualOdometerCommand',
        'App\Console\Commands\RecalculateDeviceHistoryCommand',
        'App\Console\Commands\SplitDevicesCommand',
        'App\Console\Commands\FakeDevicesCommand',
        'App\Console\Commands\DropDeviceFreeTablesCommand',
        'App\Console\Commands\RegenerateDeviceCamerasCommand',
        'App\Console\Commands\ServerPassportCommand',
        'App\Console\Commands\AlterPositionTablesCommand',
        'App\Console\Commands\BackupManageCommand',
        'App\Console\Commands\BackupImportCommand',
        'App\Console\Commands\DeleteInvalidFuelEventsCommand',
        'App\Console\Commands\DeviceServerReconfigCommand',
        'App\Console\Commands\Integrations\CleanIntegrationsCommand',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule
            ->command('queue:restart')
            ->hourly();

        $schedule->command('queue:work redis --sleep=3 --tries=1 --queue=tracker')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule
            ->exec('systemctl restart supervisord')
            ->hourlyAt(3);

        $schedule
            ->command('device_history:recalculate')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule
            ->command('schedules:check')
            ->everyMinute();

        $schedule
            ->command('subscriptions:check')
            ->hourly();

        $schedule
            ->command('devices_expiration:check')
            ->hourly();

        $schedule
            ->command('users_expiration:check')
            ->hourly();

        $schedule
            ->command('camera:clean')
            ->daily();

        $schedule
            ->command('sharing:clean')
            ->everyMinute();

        $schedule
            ->command('checklists:archive')
            ->withoutOverlapping();

        $schedule
            ->command('devices:check_offline')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule
            ->command('passport:purge')
            ->daily();

        $this->scheduleIntegrations($schedule);
    }

    /**
     * Retransmisión a SUTRAN / MININTER (config/integrations.php):
     * worker de las colas dedicadas, dispatchers cada minuto y limpieza diaria.
     */
    protected function scheduleIntegrations(Schedule $schedule)
    {
        $queues = implode(',', array_unique([
            config('integrations.sutran.queue', 'web-services-sutran'),
            config('integrations.mininter.queue', 'web-services-mininter'),
        ]));

        $schedule
            ->command("queue:work redis --sleep=3 --tries=3 --timeout=300 --queue={$queues}")
            ->everyMinute()
            ->withoutOverlapping();

        if (config('integrations.sutran.enabled')) {
            $schedule->job(new DispatchSutranJobs())->everyMinute();
        }

        if (config('integrations.mininter.enabled')) {
            $schedule->job(new DispatchMininterJobs())->everyMinute();
        }

        $schedule
            ->command('integrations:clean')
            ->dailyAt('03:30');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    // protected function commands()
    // {
    //     $this->load(__DIR__.'/Commands');

    //     require base_path('routes/console.php');
    // }
}
