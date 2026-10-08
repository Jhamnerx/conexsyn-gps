<?php

namespace Tobuli\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Process\Process;

class Server {

    const SPACE_PERCENTAGE_WARNING = 98;

    public function ip()
    {
        $ip = config('server.floating_ip');

        if ($ip)
            return $ip;

        try {
            $prefix = php_sapi_name() . '.server.';

            $ip = Cache::get($prefix.'ip');

            if ($ip)
                return $ip;

            //$ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null;

            if (!$ip || $this->isPrivateIP($ip))
                $ip = @exec('curl -s ipinfo.io/ip');

            $ip = trim($ip);

            if (ip2long($ip) && !$this->isPrivateIP($ip))
                Cache::put($prefix.'ip', $ip, 15 * 60);

        } catch (\Exception $e){};

        return $ip;
    }

    public function isPrivateIP($value) {
        if ($value == '127.0.0.1')
            return true;

        if (strpos($value, '192.168.') === 0)
            return true;

        if (strpos($value, '10.') === 0)
            return true;

        return false;
    }

    public function hostname()
    {
        $hostname = null;

        try {
            $prefix = php_sapi_name() . '.server.';

            $hostname = Cache::get($prefix.'hostname');

            if ($hostname)
                return $hostname;

            $hostname = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;

            if (empty($hostname))
                $hostname = gethostname();

            if ($hostname && !$this->isPrivateIP($hostname))
                Cache::put($prefix.'hostname', $hostname, 5 * 60);

        } catch (\Exception $e){};

        return $hostname;
    }

    public function url()
    {
        $url = config('app.url');

        if ( !empty($url) && $url != 'http://localhost' )
            return $url;

        $hostname = $this->hostname();

        if (!$hostname)
            $hostname = $this->ip();

        $protocol = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';

        return $protocol . $hostname;
    }

    public function lastUpdate()
    {
        return date('Y-m-d H:i:s', File::lastModified(base_path('server.php')));
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        $file = storage_path('messages');

        if (!File::exists($file)) {
            return [];
        }

        $messages = json_decode(File::get($file), true);

        if (empty($messages)) {
            return [];
        }

        return array_map(function ($message) {
            return $message['text'] ?? null;
        }, $messages);
    }

    public function isAutoDeploy()
    {
        return ! File::exists(storage_path('autodeploy'));
    }

    public function isDisabled()
    {
        return file_exists('/var/www/html/disabled.txt');
    }

    public function isApiDisabled()
    {
        return file_exists('/var/www/html/apidisabled');
    }

    public function isSpacePercentageWarning()
    {
        return ($this->wwwSpacePercentage() > self::SPACE_PERCENTAGE_WARNING ||
            $this->traccarSpacePercentage() > self::SPACE_PERCENTAGE_WARNING ||
            $this->databaseSpacePercentage() > self::SPACE_PERCENTAGE_WARNING) ? true : false;
    }

    public function hasDeviceLimit()
    {
        return config('server.device_limit', 0) > 1;
    }

    public function getDeviceLimit()
    {
        if ($this->hasDeviceLimit())
            return config('server.device_limit');

        return null;
    }

    public function setMemoryLimit($limit, $force = false) {
        if (!$force && $limit < $this->getMemoryLimit())
            return;

        ini_set('memory_limit', $limit);
    }

    public function getMemoryLimit() {
        return ini_get('memory_limit');
    }

    public function databaseSpacePercentage()
    {
        try {
            $directory = exec('mysql -u root -p'. config('database.connections.mysql.password') .' -Bse "select @@datadir;"');

            return $this->spaceUsePercentage($directory);

        } catch (\Exception $e) {
            return 0;
        }
    }

    public function wwwSpacePercentage()
    {
        try {
            $directory = storage_path();

            return $this->spaceUsePercentage($directory);

        } catch (\Exception $e) {
            return 0;
        }
    }

    public function traccarSpacePercentage()
    {
        try {
            $directory = config('tobuli.logs_path');

            return $this->spaceUsePercentage($directory);

        } catch (\Exception $e) {
            return 0;
        }
    }

    private function spaceUsePercentage($directory)
    {
        $total = disk_total_space($directory);

        if (empty($total))
            return null;

        $free = disk_free_space($directory);

        return 100 - round($free / $total * 100, 1);
    }

    public function statusServices()
    {
        $services = [
            'db'         => false,
            'http'       => false,
            'redis'      => false,
            'traccar'    => false,
            'supervisor' => false
        ];

        $services['http'] = $this->process('sudo service httpd status', 'is running');
        $services['traccar'] = $this->process('sudo service traccar status', 'is running');
        $services['supervisor'] = $this->process('sudo service supervisord status', 'is running');

        try {
            \DB::raw('SELECT 1+1');

            $services['db'] = true;
        }
        catch (\Exception $e) {}

        try {
            $redis = Redis::connection();

            $services['redis'] = true;
        }
        catch (\Exception $e) {}

        return $services;
    }

    protected function process($command, $expect = null)
    {
        $process = Process::fromShellCommandline($command);
        $process->run();

        while ($process->isRunning()) {
            // waiting for process to finish
        }

        echo $process->getOutput() . '<br>';

        if ( ! $process->isSuccessful())
            return false;

        if (is_null($expect))
            return true;

        echo $expect . '<br>';

        return strpos($process->getOutput(), $expect) !== false;
    }

    public function getCPUCores()
    {
        return shell_exec("nproc");
    }

    public function getCPUUsage()
    {
        $stat1 = file_get_contents('/proc/stat');
        sleep(1);
        $stat2 = file_get_contents('/proc/stat');

        preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $stat1, $cpu1);
        preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $stat2, $cpu2);

        if (!$cpu1 || !$cpu2) {
            return null;
        }

        $total1 = array_sum(array_slice($cpu1, 1, 5));
        $total2 = array_sum(array_slice($cpu2, 1, 5));

        if ($total2 - $total1 == 0) {
            return 0;
        }

        $cpuUsage = ($cpu2[1] + $cpu2[3] - ($cpu1[1] + $cpu1[3])) * 100 / ($total2 - $total1);

        return round($cpuUsage, 2);
    }

    public function getOS()
    {
        $os = shell_exec("awk -F= '$1==\"PRETTY_NAME\" { print $2 ;}' /etc/os-release");

        return $os ? trim($os, "\"\n") : null;
    }

    public function getJavaVersion()
    {
        $java = shell_exec("java -version 2>&1 | head -n 1 | awk -F '\"' '{print $2}'");

        return $java ? trim($java, "\"\n") : null;
    }

    public function getDisksUsage()
    {
        $result = shell_exec("df --exclude-type=devtmpfs --exclude-type=tmpfs | sed 1d");

        $rows = explode("\n", $result);

        $disks = array_map(function($row) {
            preg_match('/^(\S+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+%)\s+(.+)/', $row, $match);

            if (empty($match))
                return null;

            $mount = trim($match[6]);

            if (strpos($mount, '/var/lib/snapd/snap') === 0)
                return null;

            if (strpos($mount, '/boot') === 0)
                return null;

            return [
                'mount' => $mount,
                'total' => $match[2],
                'used'  => $match[3],
                'free'  => $match[4],
            ];
        }, $rows);

        return array_filter($disks);
    }
}