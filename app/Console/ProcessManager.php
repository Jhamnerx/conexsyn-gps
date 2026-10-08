<?php namespace App\Console;

use App;
use Illuminate\Support\Facades\Redis;
use Predis\Pipeline\Pipeline;

class ProcessManager {

    protected $redis;

    protected $process_limit = 5;

    protected $timeout = 60;

    protected $timeover;

    protected $group;
    protected $subgroup;

    public $unlocking = true;

    public $key;

    public function __construct($group, $timeout = 60, $limit = 1, $subgroup = null)
    {
        $this->redis = Redis::connection('process');

        $this->group = $group;
        $this->subgroup = $subgroup;

        $this->timeout = $timeout;

        $this->timeover = time() + $this->timeout;

        $this->process_limit = $limit;

        if ($this->process_limit)
            $this->cleanKilledProcess();

        $this->register();
    }

    function __destruct()
    {
        $this->unregister($this->key);
    }

    public function canContinue() {
        if ($this->timeover < time())
            return false;

        if (App::isDownForMaintenance())
            return false;

        return true;
    }

    public function canProcess() {
        return $this->canContinue() && ! $this->reachedLimit();
    }

    public function lock($id)
    {
        $key = $this->keyProcessing($id);

        return $this->redis->set($key, $this->key, 'ex', $this->timeout, 'nx') ? true : false;
    }

    public function unlock($id)
    {
        $key = $this->keyProcessing($id);

        $this->redis->del($key);
    }

    public function prolongLock($id): bool
    {
        $key = $this->keyProcessing($id);

        $pipeData = $this->redis->pipeline(function(Pipeline $pipe) use ($key) {
            $pipe->del($key);
            $pipe->set($key, $this->key, 'ex', $this->timeout, 'nx');
        });

        return (bool)last($pipeData);
    }

    private function unlockKeys($process_key = null)
    {
        if ( ! $process_key)
            $process_key = $this->key;

        $keys = $this->redis->keys($this->keyProcessing());

        foreach($keys as $key) {
            $process = $this->redis->get($key);

            if ($process != $process_key)
                continue;

            $this->redis->del($key);
        }
    }

    private function reachedLimit()
    {
        if ( ! $this->process_limit)
            return false;

        $processes = $this->redis->keys($this->keyProcess());

        return $processes && (count($processes) > $this->process_limit);
    }

    private function cleanKilledProcess()
    {
        $keys = $this->redis->keys($this->keyProcess());

        foreach ($keys as $key) {
            $process = $this->redis->get($key);

            $process = json_decode($process);

            if ( ! $process) {
                $this->redis->del($key);
                continue;
            }

            // 6h
            if (time() - $process->timeover > ($this->timeout * 5)) {
                $this->unregister($process->key);
                continue;
            }

            // process is running?
            if (file_exists('/proc/'.$process->pid))
                continue;

            $this->unregister($process->key);
        }
    }

    private function register()
    {
        $this->key = md5( $this->group . time() . str_shuffle('QWERTYUIOOPASDFGHJKLZXCVBNMQWERTYUIOPASD') );

        $this->redis->set($this->keyProcess($this->key), json_encode([
            'pid'      => getmypid(),
            'key'      => $this->key,
            'timeover' => $this->timeover
        ]));
    }

    public function unregister($process_key)
    {
        $this->redis->del($this->keyProcess($process_key));

        if ($this->unlocking)
            $this->unlockKeys($process_key);
    }

    public function disableUnlocking()
    {
        $this->unlocking = false;
    }

    public function killProcesses()
    {
        $keys = $this->redis->keys($this->keyProcess());

        foreach ($keys as $key) {
            $process = $this->redis->get($key);

            $process = json_decode($process);

            if ( ! $process) {
                $this->redis->del($key);
                continue;
            }

            if (getmypid() == $process->pid)
                continue;

            shell_exec("kill -9 {$process->pid}");

            $this->unregister($process->key);
        }
    }

    private function keyProcess($id = '*')
    {
        $group = $this->group . ($this->subgroup ? ".$this->subgroup" : "");

        return "process.{$group}.{$id}";
    }

    private function keyProcessing($id = '*')
    {
        return "processing.{$this->group}.{$id}";
    }
}