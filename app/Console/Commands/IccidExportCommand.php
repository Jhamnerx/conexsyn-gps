<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IccidExportCommand extends Command
{
    protected $signature = 'iccid:export {--output= : Ruta del archivo CSV de salida (por defecto storage/app/gt06_iccids.csv)}';

    protected $description = 'Exporta los ICCIDs de dispositivos GT06 almacenados en BD a un archivo CSV.';

    public function handle(): int
    {
        $outputPath = $this->option('output') ?: storage_path('app/gt06_iccids.csv');

        $rows = DB::table('gt06_iccids')->orderBy('name')->get();

        if ($rows->isEmpty()) {
            $this->warn('No hay registros en la tabla gt06_iccids.');
            return 0;
        }

        $file = fopen($outputPath, 'w');

        if ($file === false) {
            $this->error("No se pudo abrir el archivo para escritura: {$outputPath}");
            return 1;
        }

        // Encabezado
        fputcsv($file, ['name', 'imei', 'iccid', 'created_at']);

        foreach ($rows as $row) {
            fputcsv($file, [
                $row->name,
                $row->imei,
                $row->iccid,
                $row->created_at,
            ]);
        }

        fclose($file);

        $this->info("Exportados {$rows->count()} registros a: {$outputPath}");

        return 0;
    }
}
