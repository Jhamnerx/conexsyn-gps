<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tobuli\Services\DeviceIconRestoreService;

class DeviceIconsRestoreCommand extends Command
{
    protected $signature = 'device-icons:restore
        {--from= : Carpeta con los iconos perdidos (p. ej. public/images/device_icons de otra instalacion o backup); copia solo los archivos que la base referencia y faltan en disco}
        {--dry-run : Solo informa; no copia archivos ni crea registros}';

    protected $description = 'Recupera los iconos de dispositivos: copia los archivos que faltan desde una carpeta origen y registra en device_icons los iconos de v2/rotating sin fila (equivale a DeviceIconsTableSeeder).';

    public function handle(DeviceIconRestoreService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo simulacion: no se copia ni se escribe nada.');
        }

        $missing = $service->missingRows();
        $copied = [];

        $this->info(sprintf(
            'Iconos en la base: %d. Sin archivo en disco: %d (usados por %d dispositivos).',
            $service->allRows()->count(),
            $missing->count(),
            $service->devicesUsing($missing)
        ));

        if ($from = $this->option('from')) {
            if (!is_dir($from)) {
                $this->error("La carpeta origen no existe: {$from}");

                return 1;
            }

            if ($missing->isEmpty()) {
                $this->line('No falta ningun archivo; no hay nada que copiar.');
            } else {
                $result = $service->restore($from, $missing, $dryRun);
                $copied = $result['copied'];

                $this->info(sprintf(
                    'Copiados desde %s: %d. No encontrados en el origen: %d.',
                    $from,
                    count($result['copied']),
                    count($result['notFound'])
                ));

                $this->listPaths($result['notFound'], 'No encontrados');
            }
        }

        $created = $service->register($dryRun);
        $this->info(sprintf('Archivos de v2/rotating registrados como iconos nuevos: %d.', count($created)));
        $this->listPaths($created, 'Registrados');

        if ($service->ensureArrowIcon($dryRun)) {
            $this->info('Creado el icono flecha por defecto (id 0).');
        }

        // En simulacion no se copio nada: se descuenta lo que se habria copiado.
        $stillMissing = $dryRun
            ? $missing->reject(function ($row) use ($copied) { return in_array($row->path, $copied); })
            : $service->missingRows();

        if ($stillMissing->isEmpty()) {
            $this->info('Todos los iconos de la base tienen archivo en disco.');
        } else {
            $this->warn(sprintf(
                '%d iconos siguen sin archivo. Ejecuta de nuevo con --from=<carpeta> que contenga esos archivos.',
                $stillMissing->count()
            ));
            $this->listPaths($stillMissing->pluck('path')->all(), 'Sin archivo');
        }

        $orphans = $service->orphanFiles();

        if ($orphans) {
            $this->line(sprintf(
                'Aviso: %d archivos en %s no estan registrados en la base (se suben desde Admin > Iconos).',
                count($orphans),
                DeviceIconRestoreService::ICONS_DIR
            ));
            $this->listPaths($orphans, 'Sin registrar');
        }

        return 0;
    }

    /**
     * Lista rutas; sin -v recorta a las primeras $limit.
     */
    private function listPaths(array $paths, string $title, int $limit = 20): void
    {
        if (!$paths) {
            return;
        }

        if (!$this->getOutput()->isVerbose() && count($paths) > $limit) {
            $this->line("  {$title} (primeros {$limit} de " . count($paths) . ', usa -v para ver todos):');
            $paths = array_slice($paths, 0, $limit);
        } else {
            $this->line("  {$title}:");
        }

        foreach ($paths as $path) {
            $this->line("    {$path}");
        }
    }
}
