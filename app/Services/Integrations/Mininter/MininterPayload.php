<?php

namespace App\Services\Integrations\Mininter;

use DateTime;
use DateTimeZone;
use Tobuli\Entities\Mininter;

/**
 * Construye la trama que espera MININTER a partir de una fila de la cola `mininter`.
 *
 * Serenazgo y policial comparten los campos base; cambian los identificadores:
 * - serenazgo: idMunicipalidad + idTransmision (único por trama).
 * - policial:  codigoComisaria + idTransmision (id asignado a la comisaría).
 */
class MininterPayload
{
    const TIMEZONE = 'America/Lima';

    public static function fromRow(Mininter $row): array
    {
        $date = (new DateTime('@' . (int) $row->timestamp))
            ->setTimezone(new DateTimeZone(self::TIMEZONE));

        $base = [
            'id'              => $row->id,
            'alarma'          => (string) $row->alarma,
            'altitud'         => (float) $row->altitud,
            'angulo'          => (int) $row->angulo,
            'distancia'       => (float) $row->distancia,
            'fechaHora'       => $date->format('d/m/Y H:i:s'),
            'horasMotor'      => (float) $row->horasMotor,
            'ignition'        => (int) $row->ignition,
            'imei'            => (string) $row->imei,
            'latitud'         => (float) $row->latitud,
            'longitud'        => (float) $row->longitud,
            'motion'          => (int) $row->motion,
            'placa'           => (string) $row->placa,
            'totalDistancia'  => (float) $row->totalDistancia,
            'totalHorasMotor' => (float) $row->totalHorasMotor,
            'ubigeo'          => (string) $row->ubigeo,
            'valid'           => (bool) $row->valid,
            'velocidad'       => (float) $row->velocidad,
        ];

        if ($row->isPolicial()) {
            return $base + [
                'codigoComisaria' => (string) $row->codigoComisaria,
                'idTransmision'   => (string) $row->idMunicipalidad,
            ];
        }

        return $base + [
            'idMunicipalidad' => (string) $row->idMunicipalidad,
            'idTransmision'   => $row->id . $row->placa,
        ];
    }
}
