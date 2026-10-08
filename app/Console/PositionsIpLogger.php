<?php

namespace App\Console;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PositionsIpLogger
{
    protected $enabled;

    protected $ips = [];

    public function __construct($config)
    {
        switch (true) {
            case is_bool($config):
                $this->enabled = $config;
                break;

            case is_string($config):
                $this->enabled = true;
                $this->ips = explode(';', $config);
                break;

            case is_array($config):
                $this->enabled = true;
                $this->ips = $config;
                break;
        }
    }

    public function add($imei, $ip)
    {
        if (!$this->enabled)
            return;

        if ($this->ips && !in_array($ip, $this->ips))
            return;

        $this->insert($imei, $ip);
    }


    public function insert($imei, $ip)
    {
        $data = [
            'imei' => $imei,
            'ip' => $ip,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];

        DB::table('device_ip_log')->upsert($data, ['imei', 'ip'], ['updated_at']);
    }
}