<?php namespace App\Console\Commands;

use App\Console\PositionsStack;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tobuli\Entities\Config;
use Exception;
use App\Console\ProcessManager;
use File;
use Tobuli\Entities\Device;
use Tobuli\Entities\TraccarDevice;
use Tobuli\Helpers\Hive;
use Tobuli\Helpers\Server;
use Tobuli\Helpers\Tracker;
use Tobuli\Services\DatabaseService;
use Tobuli\Services\DeviceConfigUpdateService;

class CheckServerCommand extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'server:check';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

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
	public function handle(Config $config)
	{
	    $tracker = new Tracker();

		$curl = new \Curl;
		$curl->follow_redirects = false;
		$curl->options['CURLOPT_SSL_VERIFYPEER'] = false;
		$curl->options['CURLOPT_TIMEOUT'] = 30;

        $hive = new Hive();
        $server = new Server();

		$traccar_restart = '';
		try {
			$autodetect = ini_get('auto_detect_line_endings');
			ini_set('auto_detect_line_endings', '1');
			$lines = file('/var/spool/cron/root', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			ini_set('auto_detect_line_endings', $autodetect);
			foreach ($lines as $key => $line) {
				if (strpos($line, 'tracker:restart') !== false) {
					list($time) = explode('php', $line);
					$traccar_restart = trim($time);
					break;
				}
				//$text .= $line."\r\n";
			}
		}
		catch(\Exception $e) {

		}

		$ram_used = round(exec("free | awk 'FNR == 2 {print $3/1000000}'"), 2);
		$ram_all = round(exec("free | awk 'FNR == 2 {print ($3+$4)/1000000}'"), 2);
		$disk_total = disk_total_space("/");
		$disk_free = disk_free_space("/");
		$disk_used = $disk_total - $disk_free;

        try {
            $redis = Redis::connection();
        }
        catch (\Exception $e) {
            $redis = FALSE;
        }

        //cooldown
        sleep(5);

        try {
            $response = json_decode('{"status":0,"autodeploy":false}');
        } catch (Exception $e) {
            $response = false;
        }

        $this->processManager = new ProcessManager($this->name, $timeout = 3600, $limit = 1);

        if ( ! $this->processManager->canProcess())
        {
            echo "Cant process \n";
            return -1;
        }

        if ($response && array_key_exists('messages', $response))
        {
            $messagesFile = storage_path('messages');

            if (empty($response['messages'])) {
                File::delete($messagesFile);
            } else {
                File::put($messagesFile, json_encode($response['messages']));
            }
        }

		if (empty(settings('last_ports_modification'))) {
            settings('last_ports_modification', 0);
		}

        if (empty(settings('last_config_modification'))) {
            settings('last_config_modification', 0);
        }

        $last_ports_modification = settings('last_ports_modification');
        $last_config_modification = settings('last_config_modification');


        $configUpdateService = new DeviceConfigUpdateService();
        $last_apns_modification = settings('last_apns_modification');
        $last_device_configs_modification = settings('last_device_configs_modification');
        $last_device_models_modification = settings('last_device_models_modification');

        if ((isset($response['apns']) && $response['apns']['last'] > $last_apns_modification)) {
            $configUpdateService->updateApnConfigs( $hive->getApns() );
            settings('last_apns_modification', $response['apns']['last']);
        }

        if ((isset($response['device_configs']) && $response['device_configs']['last'] > $last_device_configs_modification)) {
            $configUpdateService->updateDeviceConfigs( $hive->getDeviceConfigs() );
            settings('last_device_configs_modification', $response['device_configs']['last']);
        }

        if ((isset($response['device_models']) && $response['device_models']['last'] > $last_device_models_modification)) {
            $configUpdateService->updateDeviceModels($hive->getDeviceModels());
            settings('last_device_models_modification', $response['device_models']['last']);
        }

		if (isset($response['ports']) && $response['ports']['last'] > $last_ports_modification) {
			parsePorts($response['ports']['items']);

            settings('last_ports_modification', $response['ports']['last']);
            settings('last_config_modification', $response['configs']['last']);
		}
		else {
			if (isset($response['configs']) && $response['configs']['last'] > $last_config_modification) {
                settings('last_config_modification', $response['configs']['last']);
			}
		}

		if ((isset($response['ports']) && $response['ports']['last'] > $last_ports_modification) || (isset($response['configs']) && $response['configs']['last'] > $last_config_modification)) {
			$tracker->config()->update();
            $tracker->restart();
		}

		if (!empty($response['status']) && !empty($response['url'])) {
			try {
                if ($tracker->upgrade($response['url']))
                    settings('jar_version', $response['version']);
            } catch (Exception $exception) {
			    $this->error($exception->getMessage());
            }
		}

		$date = date('Y-m-d H:i:s', strtotime('-1 days'));
		DB::statement("DELETE FROM sms_events_queue WHERE created_at < '{$date}'");

        $this->line('Ok');

        return 0;
	}

	protected function getDBS()
    {
        $dbs = [];
        $dbService = new DatabaseService();

        foreach ($dbService->getDatabases() as $database) {
            if (!$database->id)
                continue;

            $sizes = $dbService->getDatabaseSizes($database->id);

            $dbs[] = [
                'active'   => $database->active,
                'driver'   => $database->driver,
                'host'     => $database->host,
                'port'     => $database->port,
                'database' => $database->database,
                'sizes'    => $sizes ? $sizes->values()->toArray() : null
            ];
        }

        return $dbs;
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
