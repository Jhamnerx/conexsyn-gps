<?php namespace App\Console\Commands;

use App\Console\PositionsIpLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputArgument;
use CustomFacades\Repositories\DeviceRepo;
use App\Console\PositionsStack;
use App\Console\PositionsStats;
use App\Console\ProcessManager;
use App\Console\PositionsWriter;
use Tobuli\Entities\UnregisteredDevice;

/**
 * Worker que vuelca las posiciones acumuladas en Redis a la base de datos.
 *
 * Varios procesos corren a la vez (limite tobuli.process.insert_limit); cada
 * uno vive insert_timeout segundos. Los IMEIs se toman de la lista de reparto
 * en lotes (tobuli.process.insert_batch) en vez de uno a uno: menos viajes a
 * Redis y sin KEYS bloqueantes al rellenarla.
 */
class InsertCommand extends Command
{
    protected $debug = false;

    /**
     * @var ProcessManager
     */
    protected $processManager;

    /**
     * @var PositionsStack
     */
    protected $positionsStack;

    /**
     * @var PositionsIpLogger
     */
    protected $positionsIpLog;

    /**
     * @var PositionsStats
     */
    protected $stats;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'insert:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa las posiciones acumuladas en Redis y las inserta en la base de datos.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->debug = ! empty($this->argument('debug'));

        DB::disableQueryLog();

        $timeout = config('tobuli.process.insert_timeout');
        $limit = config('tobuli.process.insert_limit');
        $batchSize = (int) config('tobuli.process.insert_batch', 200);

        $this->positionsStack = new PositionsStack();
        $this->processManager = new ProcessManager($this->name, $timeout, $limit);
        $this->positionsIpLog = new PositionsIpLogger(config('tobuli.positions_ip_log'));
        $this->stats = new PositionsStats();

        if ( ! $this->processManager->canProcess()) {
            echo "Cant process.";
            return;
        }

        while ($this->processManager->canContinue())
        {
            $start = microtime(true);

            $taken = $this->processBatch($batchSize);

            if ($this->debug)
                $this->line(sprintf('Batch: %d imeis in %.3fs', $taken, microtime(true) - $start));

            // nada pendiente: espera corta antes de volver a mirar
            if ( ! $taken)
                usleep(100000);
        }
    }

    /**
     * Toma un lote de IMEIs y los procesa uno a uno. Devuelve cuantos tomo.
     */
    private function processBatch(int $limit): int
    {
        $imeis = $this->positionsStack->nextBatch($limit);

        if ( ! $imeis)
            return 0;

        foreach ($imeis as $index => $imei)
        {
            // se agoto el tiempo del proceso: lo que queda vuelve a la lista
            if ( ! $this->processManager->canContinue()) {
                $this->positionsStack->requeue(array_slice($imeis, $index));
                break;
            }

            if ( ! $this->isValidImei($imei)) {
                $this->discard($imei, 'discarded_invalid_imei');
                continue;
            }

            // otro proceso ya lo tiene
            if ( ! $this->processManager->lock($imei))
                continue;

            try {
                $this->processImei($imei);
            } finally {
                $this->processManager->unlock($imei);
            }
        }

        return count($imeis);
    }

    private function processImei($imei)
    {
        $start = microtime(true);

        $data = null;

        $device = DeviceRepo::whereImei($imei);

        if ( ! $device)
        {
            $data = $this->positionsStack->getData($imei, false);

            if ( ! $data) {
                $this->discard($imei, 'discarded_invalid_imei');
                return;
            }

            $device = DeviceRepo::getByImeiProtocol($data['imei'], $data['protocol']);
        }

        if($this->debug)
            $this->line('Getting device: '.(microtime(true) - $start));

        if ( ! $device) {
            UnregisteredDevice::increase(
                Arr::get($data, 'imei'),
                Arr::get($data, 'protocol'),
                Arr::get($data, 'attributes.ip'),
                $this->positionsStack->count($imei)
            );

            $this->positionsIpLog->add(
                Arr::get($data, 'imei'),
                Arr::get($data, 'attributes.ip')
            );

            $this->discard($imei, 'discarded_unregistered');

        } elseif ($device->active) {
            $start = microtime(true);
            $writer = new PositionsWriter($device, $this->debug);
            $writer->runList($imei);
            if($this->debug)
                $this->line('All writer: '.(microtime(true) - $start));

        } else {
            $this->discard($imei, 'discarded_inactive');
        }
    }

    /**
     * Borra la cola de un IMEI contando cuantas posiciones se pierden y por que.
     */
    private function discard($imei, string $counter): void
    {
        $this->stats->incr($counter, max(1, (int) $this->positionsStack->count($imei)));
        $this->positionsStack->deleteImei($imei);
    }

    private function isValidImei($imei)
    {
        if (empty($imei))
            return false;

        return !preg_match('/[^A-Za-z0-9.#_\\-$]/', $imei);
    }

    protected function getArguments()
    {
        return array(
            array('debug', InputArgument::OPTIONAL, 'Debug')
        );
    }
}
