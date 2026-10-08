<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Tobuli\Entities\Device;

class DevicesTimezoneApplyCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'devicetimezone:apply';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Display an inspiring quote';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
	    $total = Device::whereNotNull('timezone_id')->count();

        $bar = $this->output->createProgressBar($total);

        Device::whereNotNull('timezone_id')->chunk(100, function ($devices) use ($bar) {
            foreach ($devices as $device) {
                $device->applyPositionsTimezone();

                $bar->advance();
            }
        });

        $bar->finish();
	}

}
