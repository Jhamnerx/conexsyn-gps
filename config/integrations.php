<?php

/*
|--------------------------------------------------------------------------
| Retransmisión de posiciones a servicios web del Estado (SUTRAN / MININTER)
|--------------------------------------------------------------------------
|
| Todos los valores pueden sobreescribirse desde .env. URLs de pruebas:
|
|   SUTRAN_URL=https://ws03.sutran.ehg.pe/api/v1.0/transmisiones
|   MININTER_SERENAZGO_URL=https://transmision.mininter.gob.pe/retransmisionGPS/puntosGPS
|   MININTER_POLICIAL_URL=https://transmision.mininter.gob.pe/retransmisionpolicial/puntosGPS
|
| Flujo: PositionsWriter -> tablas `sutran` / `mininter` -> Dispatch*Jobs (cada minuto)
| -> SendData* (colas dedicadas) -> HTTP. `integrations:clean` limpia logs y colas.
|
*/

return [

    'sutran' => [
        'enabled' => (bool) env('SUTRAN_ENABLED', true),
        'url'     => env('SUTRAN_URL', 'https://ws03.sutran.gob.pe/api/v1.0/transmisiones'),
        'token'   => env('SUTRAN_TOKEN', '90ae223f-183a-42ed-ad58-f3af76d51743'),
        'queue'   => env('SUTRAN_QUEUE', 'web-services-sutran'),

        // Filas por job SendDataSutranBatch y máximo de tramas por request (documentación SUTRAN: 150).
        'job_chunk'     => (int) env('SUTRAN_JOB_CHUNK', 2000),
        'request_chunk' => (int) env('SUTRAN_REQUEST_CHUNK', 150),
        'timeout'       => (int) env('SUTRAN_TIMEOUT', 30),

        // Posiciones más antiguas no se encolan (SUTRAN las rechaza).
        'max_position_age_days' => (int) env('SUTRAN_MAX_AGE_DAYS', 15),
        // Una fila marcada `queued_at` hace más de N minutos se considera huérfana y se vuelve a despachar.
        'queued_ttl_minutes'    => (int) env('SUTRAN_QUEUED_TTL', 30),
        // `integrations:clean` borra filas con más de N días.
        'retention_days'        => (int) env('SUTRAN_RETENTION_DAYS', 7),
    ],

    'mininter' => [
        'enabled'       => (bool) env('MININTER_ENABLED', true),
        'serenazgo_url' => env('MININTER_SERENAZGO_URL', 'https://transmision.mininter.gob.pe/retransmisionGPS/ubicacionGPS'),
        'policial_url'  => env('MININTER_POLICIAL_URL', 'https://transmision.mininter.gob.pe/retransmisionpolicial/ubicacion/gps-policial'),
        'queue'         => env('MININTER_QUEUE', 'web-services-mininter'),

        'job_chunk'       => (int) env('MININTER_JOB_CHUNK', 500),
        'concurrency'     => (int) env('MININTER_CONCURRENCY', 10),
        'timeout'         => (int) env('MININTER_TIMEOUT', 15),
        'connect_timeout' => (int) env('MININTER_CONNECT_TIMEOUT', 5),

        // Reintentos por trama antes de marcarla `failed`, y espera entre reintentos.
        'max_retries'         => (int) env('MININTER_MAX_RETRIES', 3),
        'retry_after_minutes' => (int) env('MININTER_RETRY_AFTER', 5),
        'queued_ttl_minutes'  => (int) env('MININTER_QUEUED_TTL', 30),

        'max_position_age_days' => (int) env('MININTER_MAX_AGE_DAYS', 15),
        'retention_days'        => (int) env('MININTER_RETENTION_DAYS', 7),
    ],

    'logs' => [
        // Carpeta de los canales `sutran` y `mininter` (config/logging.php).
        'path'  => storage_path('logs/integrations'),
        'days'  => (int) env('INTEGRATIONS_LOG_DAYS', 14),
        'level' => env('INTEGRATIONS_LOG_LEVEL', 'info'),
        // Cuerpos de request/response más largos se truncan en el log.
        'max_body_length' => (int) env('INTEGRATIONS_LOG_MAX_BODY', 2000),
    ],

];
