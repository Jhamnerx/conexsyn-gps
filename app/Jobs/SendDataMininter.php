<?php

namespace App\Jobs;

use App\Services\Integrations\IntegrationLogger;
use App\Services\Integrations\Mininter\MininterPayload;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Tobuli\Entities\Mininter;

/**
 * Envía a MININTER un lote de tramas (una request por trama, en paralelo con GuzzleHttp\Pool).
 *
 * - Serenazgo y policial van a endpoints distintos; el tipo lo fija el dispositivo.
 * - 2xx: la trama se borra. Otro caso: `error` con reintento, o `failed` al agotar `max_retries`.
 */
class SendDataMininter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public $timeout = 300;

    protected array $ids;

    private IntegrationLogger $log;

    private array $stats = ['sent' => 0, 'accepted' => 0, 'rejected' => 0];

    public function __construct(array $ids)
    {
        $this->ids = array_values($ids);
        $this->onQueue(config('integrations.mininter.queue'));
    }

    public function handle()
    {
        $this->log = IntegrationLogger::mininter()->withContext([
            'job' => 'SendDataMininter',
            'batch' => count($this->ids),
        ]);

        $start = microtime(true);

        try {
            $rows = Mininter::whereIn('id', $this->ids)
                ->where('status', Mininter::STATUS_QUEUED)
                ->with('device.users')
                ->get();

            if ($rows->isEmpty()) {
                return;
            }

            $groups = [Mininter::TYPE_SERENAZGO => [], Mininter::TYPE_POLICIAL => []];

            foreach ($rows as $row) {
                $this->completeFromDevice($row);

                $groups[$row->tipo][] = ['row' => $row, 'payload' => MininterPayload::fromRow($row)];
            }

            foreach ($groups as $type => $items) {
                if ($items) {
                    $this->sendGroup($items, $this->urlFor($type), $type);
                }
            }
        } catch (Throwable $e) {
            $this->log->exception('Error general del lote', $e);

            throw $e;
        }

        $this->log->info('Lote procesado', $this->stats + ['seconds' => round(microtime(true) - $start, 2)]);
    }

    public function failed(Throwable $e): void
    {
        // Reintentos agotados: devolver las tramas a `pending` para el siguiente dispatcher.
        Mininter::whereIn('id', $this->ids)
            ->where('status', Mininter::STATUS_QUEUED)
            ->update(['status' => Mininter::STATUS_PENDING]);
    }

    /**
     * Filas heredadas (sin tipo o sin datos de municipalidad) se completan desde el dispositivo.
     */
    protected function completeFromDevice(Mininter $row): void
    {
        $device = $row->device;

        if (! $row->tipo) {
            $row->tipo = ($device ? $device->mininter_type : null)
                ?: ($row->codigoComisaria ? Mininter::TYPE_POLICIAL : Mininter::TYPE_SERENAZGO);
        }

        if (! in_array($row->tipo, [Mininter::TYPE_SERENAZGO, Mininter::TYPE_POLICIAL])) {
            $row->tipo = Mininter::TYPE_SERENAZGO;
        }

        $needsUser = empty($row->idMunicipalidad) || empty($row->ubigeo)
            || ($row->isPolicial() && empty($row->codigoComisaria));

        if (! $needsUser || ! $device || ! ($user = $device->mininterUser())) {
            return;
        }

        $row->idMunicipalidad = $row->idMunicipalidad ?: (string) $user->token_muni;
        $row->ubigeo = $row->ubigeo ?: (string) $user->ubigeo_muni;

        if ($row->isPolicial()) {
            $row->codigoComisaria = $row->codigoComisaria ?: (string) $user->codigo_comisaria;
        }
    }

    protected function urlFor(string $type): string
    {
        return $type === Mininter::TYPE_POLICIAL
            ? config('integrations.mininter.policial_url')
            : config('integrations.mininter.serenazgo_url');
    }

    protected function sendGroup(array $items, string $url, string $type): void
    {
        $client = new Client([
            'verify'          => false,
            'timeout'         => (int) config('integrations.mininter.timeout', 15),
            'connect_timeout' => (int) config('integrations.mininter.connect_timeout', 5),
        ]);

        $requests = function () use ($items, $url) {
            foreach ($items as $item) {
                yield new Request('POST', $url, ['Content-Type' => 'application/json'], json_encode($item['payload']));
            }
        };

        $pool = new Pool($client, $requests(), [
            'concurrency' => (int) config('integrations.mininter.concurrency', 10),
            'fulfilled' => function (ResponseInterface $response, $index) use ($items, $type, $url) {
                $this->stats['sent']++;
                $this->handleResponse($items[$index], $response, $type, $url);
            },
            'rejected' => function ($reason, $index) use ($items, $type, $url) {
                $this->stats['sent']++;
                $this->handleRejected($items[$index], $reason, $type, $url);
            },
        ]);

        $pool->promise()->wait();
    }

    protected function handleResponse(array $item, ResponseInterface $response, string $type, string $url): void
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($status >= 200 && $status < 300) {
            $this->stats['accepted']++;
            Mininter::whereKey($item['row']->id)->delete();

            $this->log->debug('Trama aceptada', [
                'id' => $item['row']->id, 'placa' => $item['payload']['placa'], 'tipo' => $type, 'status' => $status,
            ]);

            return;
        }

        $this->markFailure($item, "HTTP {$status}: {$body}", $type, $url, $status, $body);
    }

    protected function handleRejected(array $item, $reason, string $type, string $url): void
    {
        $status = null;
        $body = null;

        if ($reason instanceof RequestException && $reason->hasResponse()) {
            $status = $reason->getResponse()->getStatusCode();
            $body = (string) $reason->getResponse()->getBody();
        }

        $message = $reason instanceof Throwable ? $reason->getMessage() : (string) $reason;

        $this->markFailure($item, $body ? "HTTP {$status}: {$body}" : $message, $type, $url, $status, $body ?? $message);
    }

    /**
     * Incrementa `retry_count`; pasa a `error` (reintentable) o a `failed` al agotar reintentos.
     */
    protected function markFailure(array $item, string $error, string $type, string $url, ?int $status, ?string $response): void
    {
        $this->stats['rejected']++;

        $row = $item['row'];
        $retries = (int) $row->retry_count + 1;
        $maxRetries = (int) config('integrations.mininter.max_retries', 3);
        $exhausted = $retries >= $maxRetries;

        $update = [
            'status'      => $exhausted ? Mininter::STATUS_FAILED : Mininter::STATUS_ERROR,
            'retry_count' => $retries,
            'last_error'  => Str::limit($error, 1000, ''),
        ];

        if ($exhausted) {
            $update['failed_at'] = now();
        }

        Mininter::whereKey($row->id)->update($update);

        $this->log->error($exhausted ? 'Trama fallida definitivamente' : 'Trama rechazada, se reintentará', [
            'id'       => $row->id,
            'placa'    => $item['payload']['placa'],
            'tipo'     => $type,
            'endpoint' => $url,
            'status'   => $status,
            'attempt'  => "{$retries}/{$maxRetries}",
            'response' => $response,
            'payload'  => $item['payload'],
        ]);
    }
}
