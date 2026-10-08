<?php

namespace App\Services;

use Tobuli\Entities\LogEntry;



class LogService
{
    /**
     * Guardar un log en la base de datos.
     *
     * @param string $service El nombre del servicio web.
     * @param string $plate El número de la placa.
     * @param string $message El mensaje del log.
     * @param string $level El nivel del log (por defecto: info).
     * @param array $additionalData Datos adicionales opcionales.
     * @return void
     */
    public function logToDatabase($service, $plate, $message, $level = 'info', $additionalData = [])
    {
        LogEntry::create([
            'service_name' => $service,
            'plate_number' => $plate,
            'level' => $level,
            'message' => $message,
            'additional_data' => $additionalData,
        ]);
    }
}
