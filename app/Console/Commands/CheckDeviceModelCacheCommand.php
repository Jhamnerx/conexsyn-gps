<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Tobuli\Entities\Device;
use Tobuli\Services\DeviceModelCache;


class CheckDeviceModelCacheCommand extends Command
{
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'service:check_device_model';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Check if all devices with model have Redis.';

    private Connection $redis;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->redis = Redis::connection('process');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $countModels = Device::whereNotNull('model_id')->count();

        if ($countModels === 0) {
            return 'DONE';
        }

        $cacheKeys = $this->redis->keys('model.*');

        if (count($cacheKeys) === $countModels) {
            return 'DONE';
        }

        DeviceModelCache::reload();

        return 'DONE';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
