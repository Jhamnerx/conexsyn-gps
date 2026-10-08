<?php

namespace App\Services\Integrations\Sutran;

use DateTime;
use DateTimeZone;
use Tobuli\Entities\Sutran;

/**
 * Construye la trama que espera el WS de SUTRAN a partir de una fila de la cola `sutran`.
 */
class SutranPayload
{
    const TIMEZONE = 'America/Lima';

    const EVENT_STOPPED = 'PA';
    const EVENT_MOVING  = 'ER';

    /** Velocidad (km/h) a partir de la cual la unidad se reporta en ruta. */
    const MOVING_SPEED = 5;

    public static function fromRow(Sutran $row): array
    {
        $date = (new DateTime())
            ->setTimestamp(strtotime($row->time_device))
            ->setTimezone(new DateTimeZone(self::TIMEZONE));

        return [
            'id'          => $row->id,
            'plate'       => self::normalizePlate($row->plate),
            'geo'         => [(float) $row->latitud, (float) $row->longitud],
            'direction'   => intval($row->direction ?? 0),
            'event'       => (float) $row->speed > self::MOVING_SPEED ? self::EVENT_MOVING : self::EVENT_STOPPED,
            'speed'       => intval($row->speed),
            'time_device' => $date->format('Y-m-d H:i:s'),
            'imei'        => intval($row->device->imei ?? 0),
        ];
    }

    /** SUTRAN espera la placa sin guiones ni espacios y en mayúsculas. */
    public static function normalizePlate(?string $plate): string
    {
        return strtoupper(trim(str_replace(['-', ' '], '', (string) $plate)));
    }

    /** SUTRAN rechaza rumbos fuera de 0..360. */
    public static function isValidDirection($direction): bool
    {
        return is_numeric($direction) && $direction >= 0 && $direction <= 360;
    }
}
