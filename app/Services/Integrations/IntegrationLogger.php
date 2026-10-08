<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Logger de archivo para las integraciones (canales `sutran` / `mininter` de config/logging.php).
 *
 * - Prefija cada línea con la integración y añade un contexto común (job, ids...).
 * - Trunca cuerpos de request/response largos para que los archivos sigan siendo legibles.
 * - Nunca lanza: si el canal no existe cae al log por defecto de Laravel.
 */
class IntegrationLogger
{
    private string $channel;

    private array $context = [];

    public function __construct(string $channel)
    {
        $this->channel = $channel;
    }

    public static function sutran(): self
    {
        return new self('sutran');
    }

    public static function mininter(): self
    {
        return new self('mininter');
    }

    /** Devuelve una copia con contexto adicional que se añade a todas las líneas. */
    public function withContext(array $context): self
    {
        $clone = clone $this;
        $clone->context = array_merge($this->context, $context);

        return $clone;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function exception(string $message, Throwable $e, array $context = []): void
    {
        $this->error($message, $context + [
            'exception' => get_class($e),
            'error'     => $e->getMessage(),
            'at'        => $e->getFile() . ':' . $e->getLine(),
        ]);
    }

    private function log(string $level, string $message, array $context): void
    {
        $message = strtoupper($this->channel) . ': ' . $message;
        $context = $this->sanitize(array_merge($this->context, $context));

        try {
            $this->logger()->{$level}($message, $context);
        } catch (Throwable $e) {
            try {
                Log::{$level}($message, $context + ['log_channel_error' => $e->getMessage()]);
            } catch (Throwable $ignored) {
                // Sin salida de log disponible: no hay nada más que hacer.
            }
        }
    }

    private function logger(): LoggerInterface
    {
        return Log::channel($this->channel);
    }

    /** Serializa y trunca valores largos (payloads, respuestas HTML de error, etc.). */
    private function sanitize(array $context): array
    {
        $max = (int) config('integrations.logs.max_body_length', 2000);

        foreach ($context as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if ($encoded !== false && strlen($encoded) > $max) {
                    $value = $encoded;
                }
            }

            if (is_string($value) && strlen($value) > $max) {
                $value = substr($value, 0, $max) . '...[' . strlen($value) . ' bytes]';
            }

            $context[$key] = $value;
        }

        return $context;
    }
}
