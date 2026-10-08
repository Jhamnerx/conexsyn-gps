<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Tobuli\Entities\Device;
use Tobuli\Entities\Event;
use Tobuli\History\Actions\GroupStop;
use Tobuli\History\DeviceHistory;
use Tobuli\History\GroupContainer;

class DeleteInvalidFuelEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:fuel:delete-invalid {time_from?} {time_to?} {--debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete invalid fuel events';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $debug = $this->option('debug');

        $events = Event::with('device')
            ->whereIn('type', [Event::TYPE_FUEL_FILL, Event::TYPE_FUEL_THEFT]);

        if ($this->argument('time_from')) {
            $events = $events->where('time', '>=', $this->argument('time_from'));
        }

        if ($this->argument('time_to')) {
            $events = $events->where('time', '<=', $this->argument('time_to'));
        }

        $events->chunk(500, function ($events) use ($debug) {
            /** @var Event $event */
            foreach ($events as $event) {
                $time = Carbon::parse($event->time);
                $detectAfterStop = $event->device->fuel_detect_sec_after_stop;

                $from = (clone $time)->subMinutes(5);
                $to = (clone $time);

                if ($detectAfterStop) {
                    $to->addSeconds($detectAfterStop);
                }

                $history = $this->getHistory($event->device, $from, $to)->all();
                $delete = !empty($history);

                $this->line("{$event->device->imei} | $event->type | $event->time");

                foreach ($history as $group) {
                    $groupFrom = null;
                    $groupTo = null;

                    if ($position = $group->getStartPosition()) {
                        $groupFrom = Carbon::parse($position->time);

                        $this->output->write($groupFrom->format('Y-m-d H:i:s'));
                    }

                    $this->output->write(' - ');

                    if ($position = $group->getEndPosition()) {
                        $groupTo = Carbon::parse($position->time)->addSeconds($detectAfterStop);

                        $this->output->write($groupTo->format('Y-m-d H:i:s'));
                    }

                    $this->newLine();

                    if ($groupFrom && $groupFrom->gt($time)) {
                        continue;
                    }

                    if ($groupTo && $groupTo->lt($time)) {
                        continue;
                    }

                    $delete = false;
                    break;
                }

                if ($delete) {
                    $this->comment('DELETE');

                    if (!$debug) {
                        $event->delete();
                    }
                }

                $this->newLine();
            }
        });

        return 0;
    }

    private function getHistory(Device $device, $from, $to): GroupContainer
    {
        $history = new DeviceHistory($device);
        $history->setConfig([
            'stop_seconds'  => 180,
            'stop_speed'    => $device->min_moving_speed,
        ]);
        $history->setRange($from, $to);
        $history->registerActions([GroupStop::class]);

        if ($sensor = $device->getSpeedSensor()) {
            $history->setSensors([$sensor]);
        }

        return $history->get()['groups'];
    }
}
