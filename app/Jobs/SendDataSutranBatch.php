<?php

namespace App\Jobs;

use App\Services\Integrations\IntegrationLogger;
use App\Services\Integrations\Sutran\SutranPayload;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Throwable;
use Tobuli\Entities\Sutran;

/**
 * Envía a SUTRAN un lote de filas de la cola `sutran` (por id) en grupos de
 * `request_chunk` tramas por request.
 *
 * - Aceptadas: se borran.
 * - Rechazadas por SUTRAN fila a fila (error_plates "F:<índice>"): se borran y se registran,
 *   porque son validaciones deterministas (placa no registrada, formato) que no cambian al reintentar.
 * - Error de transporte o respuesta no reconocida: se liberan (`queued_at = null`) para que
 *   el dispatcher las reintente; `integrations:clean` acota cuánto tiempo viven.
 */
class SendDataSutranBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public $timeout = 300;

    protected array $ids;

    private IntegrationLogger $log;

    public function __construct(array $ids)
    {
        $this->ids = array_values($ids);
        $this->onQueue(config('integrations.sutran.queue'));
    }

    public function handle()
    {
        $this->log = IntegrationLogger::sutran()->withContext([
            'job' => 'SendDataSutranBatch',
            'batch' => count($this->ids),
        ]);

        try {
            $rows = Sutran::whereIn('id', $this->ids)
                ->withPlate()
                ->with('device')
                ->get();

            if ($rows->isEmpty()) {
                return;
            }

            $tramas = $this->buildTramas($rows);

            foreach (array_chunk($tramas, (int) config('integrations.sutran.request_chunk', 150)) as $group) {
                $this->send($group);
            }
        } catch (Throwable $e) {
            $this->log->exception('Error general del lote', $e);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        // Reintentos agotados: liberar las filas para el siguiente dispatcher.
        $this->releaseRows($this->ids);
    }

    /**
     * @return array[] tramas listas para enviar; las filas inválidas se borran y registran.
     */
    protected function buildTramas(Collection $rows): array
    {
        $tramas = [];
        $discarded = [];

        foreach ($rows as $row) {
            if (! $row->device) {
                $discarded[$row->id] = 'sin dispositivo';
                continue;
            }

            if (! SutranPayload::isValidDirection($row->direction)) {
                $discarded[$row->id] = "rumbo inválido ({$row->direction})";
                continue;
            }

            $tramas[] = SutranPayload::fromRow($row);
        }

        if ($discarded) {
            Sutran::whereIn('id', array_keys($discarded))->delete();
            $this->log->warning('Descartadas filas inválidas', ['rows' => $discarded]);
        }

        return $tramas;
    }

    protected function send(array $tramas): void
    {
        $url = config('integrations.sutran.url');

        $client = new Client([
            'verify' => false,
            'timeout' => (int) config('integrations.sutran.timeout', 30),
        ]);

        try {
            $response = $client->post($url, [
                'headers' => [
                    'access-token' => config('integrations.sutran.token'),
                    'Content-Type' => 'application/json',
                ],
                'json' => $tramas,
            ]);

            $body = (string) $response->getBody();

            $this->handleResponse($tramas, json_decode($body, true), $body);
        } catch (RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : null;

            $this->log->error('Error HTTP al enviar', [
                'endpoint' => $url,
                'status'   => $status,
                'error'    => $e->getMessage(),
                'response' => $body,
                'count'    => count($tramas),
                'plates'   => $this->plates($tramas),
            ]);

            $this->releaseRows($this->ids($tramas));
        }
    }

    protected function handleResponse(array $tramas, $response, string $rawBody): void
    {
        $ids = $this->ids($tramas);

        if (! is_array($response) || ! array_key_exists('status', $response)) {
            $this->log->error('Respuesta no reconocida', ['response' => $rawBody, 'count' => count($tramas)]);
            $this->releaseRows($ids);

            return;
        }

        if ((int) $response['status'] !== 200) {
            $this->log->error('SUTRAN respondió con estado distinto de 200', [
                'response' => $rawBody,
                'count'    => count($tramas),
                'plates'   => $this->plates($tramas),
            ]);
            $this->releaseRows($ids);

            return;
        }

        $errorPlates = $response['error_plates'] ?? [];

        if (empty($errorPlates)) {
            Sutran::whereIn('id', $ids)->delete();
            $this->log->info('Tramas aceptadas', ['count' => count($tramas)]);

            return;
        }

        $rejectedIds = $this->rejectedIds($tramas, $errorPlates);

        $this->log->error('SUTRAN rechazó tramas', [
            'accepted'        => count($ids) - count($rejectedIds),
            'rejected'        => count($rejectedIds),
            'error_plates'    => $errorPlates,
            'rejected_tramas' => array_values(array_filter($tramas, fn ($t) => in_array($t['id'], $rejectedIds))),
        ]);

        // Aceptadas y rechazadas se borran: las rechazadas quedan registradas arriba.
        Sutran::whereIn('id', $ids)->delete();
    }

    /** SUTRAN señala cada trama rechazada con "F:<índice dentro del request>". */
    protected function rejectedIds(array $tramas, array $errorPlates): array
    {
        $ids = [];

        foreach ($errorPlates as $error) {
            $message = is_array($error) ? ($error['message'] ?? '') : (string) $error;

            if (preg_match('/F:(\d+)/', $message, $matches) && isset($tramas[(int) $matches[1]])) {
                $ids[] = $tramas[(int) $matches[1]]['id'];
            }
        }

        return array_values(array_unique($ids));
    }

    protected function releaseRows(array $ids): void
    {
        if ($ids) {
            Sutran::whereIn('id', $ids)->update(['queued_at' => null]);
        }
    }

    private function ids(array $tramas): array
    {
        return array_column($tramas, 'id');
    }

    private function plates(array $tramas): array
    {
        return array_values(array_unique(array_column($tramas, 'plate')));
    }
}
