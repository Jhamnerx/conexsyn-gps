<?php

namespace App\Console\Commands\Integrations;

use App\Services\Integrations\IntegrationLogger;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Tobuli\Entities\Mininter;
use Tobuli\Entities\Sutran;

/**
 * Limpieza automática de las integraciones SUTRAN / MININTER (programada a diario en el Kernel):
 *
 * - Borra los archivos de storage/logs/integrations con más de N días.
 * - SUTRAN: borra filas con más de `retention_days` (el servicio ya no las acepta).
 * - MININTER: borra filas `failed` con más de `retention_days` y cualquier fila más antigua
 *   que `max_position_age_days`.
 */
class CleanIntegrationsCommand extends Command
{
    protected $signature = 'integrations:clean
        {--log-days= : Días de logs a conservar (por defecto integrations.logs.days)}
        {--dry-run : Solo informa lo que se borraría}';

    protected $description = 'Limpia logs de integraciones y filas antiguas o fallidas de las colas SUTRAN y MININTER.';

    const DELETE_CHUNK = 5000;

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo --dry-run: no se borra nada.');
        }

        $summary = [
            'logs'     => $this->cleanLogFiles($dryRun),
            'sutran'   => $this->cleanSutran($dryRun),
            'mininter' => $this->cleanMininter($dryRun),
        ];

        if (! $dryRun) {
            IntegrationLogger::sutran()->info('Limpieza ejecutada', ['job' => 'integrations:clean'] + $summary);
            IntegrationLogger::mininter()->info('Limpieza ejecutada', ['job' => 'integrations:clean'] + $summary);
        }

        $this->info('Limpieza terminada.');

        return self::SUCCESS;
    }

    protected function cleanLogFiles(bool $dryRun): array
    {
        $days = (int) ($this->option('log-days') ?: config('integrations.logs.days', 14));
        $path = config('integrations.logs.path');

        if (! is_dir($path)) {
            $this->line("Logs: la carpeta {$path} no existe todavía.");

            return ['deleted' => 0, 'bytes' => 0, 'days' => $days];
        }

        $limit = time() - $days * 86400;
        $deleted = 0;
        $bytes = 0;

        foreach (File::glob($path . DIRECTORY_SEPARATOR . '*.log*') as $file) {
            if (File::lastModified($file) >= $limit) {
                continue;
            }

            $bytes += File::size($file);
            $deleted++;

            $this->line('  - ' . basename($file));

            if (! $dryRun) {
                File::delete($file);
            }
        }

        $mb = round($bytes / 1048576, 2);
        $this->info("Logs: {$deleted} archivo(s) con más de {$days} días ({$mb} MB).");

        return ['deleted' => $deleted, 'bytes' => $bytes, 'days' => $days];
    }

    protected function cleanSutran(bool $dryRun): array
    {
        $days = (int) config('integrations.sutran.retention_days', 7);

        $deleted = $this->deleteInChunks(
            Sutran::where('created_at', '<', now()->subDays($days)),
            $dryRun
        );

        $this->info("SUTRAN: {$deleted} fila(s) con más de {$days} días.");

        return ['deleted' => $deleted, 'days' => $days];
    }

    protected function cleanMininter(bool $dryRun): array
    {
        $retention = (int) config('integrations.mininter.retention_days', 7);
        $maxAge = (int) config('integrations.mininter.max_position_age_days', 15);

        $failed = $this->deleteInChunks(
            Mininter::where('status', Mininter::STATUS_FAILED)->where('updated_at', '<', now()->subDays($retention)),
            $dryRun
        );

        $stale = $this->deleteInChunks(
            Mininter::where('created_at', '<', now()->subDays($maxAge)),
            $dryRun
        );

        $this->info("MININTER: {$failed} fila(s) fallidas con más de {$retention} días, {$stale} fila(s) con más de {$maxAge} días.");

        return ['failed' => $failed, 'stale' => $stale, 'retention_days' => $retention, 'max_age_days' => $maxAge];
    }

    /** Borra por bloques para no bloquear la tabla con un DELETE masivo. */
    protected function deleteInChunks(Builder $query, bool $dryRun): int
    {
        if ($dryRun) {
            return $query->count();
        }

        $total = 0;

        do {
            $deleted = (clone $query)->limit(self::DELETE_CHUNK)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        return $total;
    }
}
