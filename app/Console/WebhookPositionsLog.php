<?php

namespace App\Console;

use Illuminate\Support\Facades\Log;

/**
 * Traza del webhook de posiciones (storage/logs/integrations/webhook-positions-*.log):
 * recepcion en el endpoint, encolado en Redis y lo que insert:run hace con cada IMEI,
 * para saber en que paso se pierde una posicion.
 */
class WebhookPositionsLog
{
    public static function isWebhook($protocol): bool
    {
        return $protocol !== null && $protocol === config('server.webhook_positions.protocol');
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    protected static function write(string $level, string $message, array $context): void
    {
        try {
            Log::channel('webhook_positions')->{$level}($message, $context);
        } catch (\Throwable $e) {
            // el log nunca debe tumbar la recepcion ni el procesamiento de posiciones
        }
    }
}
