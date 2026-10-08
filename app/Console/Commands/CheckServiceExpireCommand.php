<?php namespace App\Console\Commands;

use Bugsnag\BugsnagLaravel\Facades\Bugsnag as Bugsnag;
use Formatter;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Tobuli\Entities\DeviceService;
use Tobuli\Entities\EmailTemplate;
use Tobuli\Entities\SmsTemplate;


class CheckServiceExpireCommand extends Command
{
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'service:check_expire';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Check for service expired.';

    private ?Logger $logger = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (config('tobuli.check_service_expire_log')) {
            $this->logger = (new Logger('check_service_expire'))
                ->pushHandler(new StreamHandler(storage_path('logs/check_service_expire.log')));
        }

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DeviceService::$logPaused = true;

        $services = DeviceService::expireByDays('device_services.expires_date')
            ->notExpired()
            ->get();

        foreach ($services as $service)
            $this->processItem($service);


        $services = DeviceService::expireByOdometer('device_services.expires')
            ->notExpired()
            ->get();

        foreach ($services as $service)
            $this->processItem($service);


        $services = DeviceService::expireByEngineHours('device_services.expires')
            ->notExpired()
            ->get();

        foreach ($services as $service)
            $this->processItem($service);


        return 'DONE';
    }

    protected function processItem(DeviceService $service): void
    {
        if ($service->user)
            Formatter::byUser($service->user);
        else
            Formatter::byDefault();

        $emailTemplate = EmailTemplate::getTemplate('service_expired', $service->user);

        try {
            sendTemplateEmail($service->email, $emailTemplate, $service);
        } catch (\Exception $e) {
            Bugsnag::notifyException($e);
        }

        $smsTemplate = SmsTemplate::getTemplate('service_expired', $service->user);

        try {
            sendTemplateSMS($service->mobile_phone, $smsTemplate, $service, $service->user_id);
        } catch (\Exception $e) {
            Bugsnag::notifyException($e);
        }

        if ( ! $service->renew_after_expiration)
        {
            $service->update([
                'expired' => 1
            ]);

            return;
        }

        switch ($service->expiration_by) {
            case 'odometer':
                $sensor = $service->device->getOdometerSensor();

                if (!$sensor) {
                    $values = [];
                    break;
                }

                $currentValue = $sensor->shown_value_by === 'connected_odometer'
                    ? $sensor->value_formula
                    : $sensor->getValueCurrent($service->device)->getValue();

                $values = ['odometer' => $currentValue];
                break;

            case 'engine_hours':
                $sensor = $service->device->getEngineHoursSensor();
                $values = $sensor ? ['engine_hours' => $sensor->getValueCurrent($service->device)->getValue()] : [];
                break;

            default:
                $values = [];
        }

        if ($this->logger) {
            $msg = 'SERVICE -- ' . json_encode($service->attributesToArray());
            $msg .= ' VALUES -- ' . json_encode($values);
            $msg .= isset($sensor) ? ' SENSOR -- ' . json_encode($sensor->attributesToArray()) : '';

            $this->logger->info($msg);
        }

        $update = prepareServiceData($service->toArray(), $values);
        $update = Arr::only($update, $service->getFillable());

        $service->update($update);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array();
    }
}
