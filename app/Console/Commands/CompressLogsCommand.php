<?php namespace App\Console\Commands;

use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputOption;
use Tobuli\Helpers\TrackerConfig;
use Illuminate\Console\Command;

class CompressLogsCommand extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'logs:compress';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Updates server database and configuration to the newest version.';

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
	 * @return mixed
	 */
	public function handle()
	{
	    $tracker_days = $this->option('tracker_days') ?? 30;
        $http_days = $this->option('http_days') ?? 30;

        $path = pathinfo(config('tracker')['logger.file'], PATHINFO_DIRNAME );
		$this->compress("$path/*.log.*", ['gz', date('Ymd')]);
		$this->clean("$path/*.log.*.gz", $tracker_days);

        $this->compress('/var/log/httpd/access_log-*');
        $this->clean('/var/log/httpd/access_log-*.gz', $http_days);

        $this->compress('/var/log/httpd/error_log-*');
        $this->clean('/var/log/httpd/error_log-*.gz', $http_days);
	}

	protected function compress(string $pattern, array $exclude = ['gz'])
    {
        $files = glob($pattern);

        foreach ($files as $file) {
            $arr = explode('.', $file);
            $ex = end($arr);

            if (in_array($ex, $exclude))
                continue;

            @exec('gzip '.$file);
        }
    }

    protected function clean(string $pattern, int $days)
    {
        if (empty($days))
            return;

        $seconds = $days * 24 * 60 * 60;

        $files = glob($pattern);

        foreach ($files as $file) {
            if (time() - filemtime($file) > $seconds)
                @unlink($file);
        }
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
        return [
            ['tracker_days', null, InputOption::VALUE_OPTIONAL, 'Tracker log days leave option.', null],
            ['http_days', null, InputOption::VALUE_OPTIONAL, 'Http log days leave option.', null],
        ];
	}
}
