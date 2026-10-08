<?php

namespace App\Services\Integrations;

use App\Services\Integrations\Sutran\SutranPayload;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Tobuli\Entities\Device;
use Tobuli\Entities\Mininter;
use Tobuli\Entities\TraccarPosition as Position;

/**
 * Encola una posición recién recibida en las tablas `sutran` / `mininter`
 * según los flags del dispositivo. Los jobs Dispatch* se encargan del envío.
 *
 * Lo invoca PositionsWriter en el hilo del tracker: por eso captura todas
 * las excepciones y solo las registra en el log de la integración.
 */
class IntegrationPositionRecorder
{
    const TIMEZONE = 'America/Lima';

    /** Filtro SUTRAN: salto de velocidad (km/h) en muy poco tiempo (s) => posición implausible. */
    const SUTRAN_SPEED_JUMP = 30;
    const SUTRAN_TIME_GAP   = 5;

    /** Filtro SUTRAN: de detenido (<= km/h) a rápido (>= km/h) directamente => posición implausible. */
    const SUTRAN_STOPPED_MAX = 10;
    const SUTRAN_FAST_MIN    = 50;

    private IntegrationLogger $sutranLog;

    private IntegrationLogger $mininterLog;

    public function __construct()
    {
        $this->sutranLog = IntegrationLogger::sutran()->withContext(['origin' => 'PositionsWriter']);
        $this->mininterLog = IntegrationLogger::mininter()->withContext(['origin' => 'PositionsWriter']);
    }

    public function record(Device $device, Position $position, ?Position $prevPosition = null): void
    {
        if ($device->mtc && config('integrations.sutran.enabled')) {
            try {
                $this->recordSutran($device, $position, $prevPosition);
            } catch (Throwable $e) {
                $this->sutranLog->exception('No se pudo encolar la posición', $e, $this->deviceContext($device));
            }
        }

        if ($device->mininter && config('integrations.mininter.enabled')) {
            try {
                $this->recordMininter($device, $position);
            } catch (Throwable $e) {
                $this->mininterLog->exception('No se pudo encolar la posición', $e, $this->deviceContext($device));
            }
        }
    }

    public function recordSutran(Device $device, Position $position, ?Position $prevPosition): void
    {
        $plate = trim((string) $device->plate_number);

        if (SutranPayload::normalizePlate($plate) === '') {
            return;
        }

        if ($this->isTooOld($position, (int) config('integrations.sutran.max_position_age_days', 15))) {
            return;
        }

        if ($prevPosition && $this->isImplausibleJump($position, $prevPosition)) {
            // Regla heredada: un salto implausible invalida la posición también en la plataforma.
            $position->valid = 0;

            return;
        }

        if (! $position->valid) {
            return;
        }

        $device->sutranPositions()->create([
            'plate'       => $plate,
            'latitud'     => $position->latitude,
            'longitud'    => $position->longitude,
            'direction'   => $position->course,
            'speed'       => $position->speed,
            'time_device' => $position->device_time,
            'other'       => $position->parameters,
        ]);
    }

    public function recordMininter(Device $device, Position $position): void
    {
        if ($this->isTooOld($position, (int) config('integrations.mininter.max_position_age_days', 15))) {
            return;
        }

        $user = $device->mininterUser();

        if (! $user) {
            $this->warnOnce(
                "mininter.no_user.{$device->id}",
                $this->mininterLog,
                'El objeto no tiene ningún usuario con retransmisión MININTER habilitada; no se encola',
                $this->deviceContext($device)
            );

            return;
        }

        $type = $device->mininter_type ?: Device::MININTER_TYPE_SERENAZGO;
        $params = $position->parameters;

        $device->mininterPositions()->create([
            'status'          => Mininter::STATUS_PENDING,
            'tipo'            => $type,
            'alarma'          => '0',
            'altitud'         => (float) $position->altitude,
            'angulo'          => (int) $position->course,
            'distancia'       => (float) ($params['distance'] ?? $position->distance ?? 0),
            'fechaHora'       => $position->time,
            'timestamp'       => (string) strtotime($position->device_time),
            'horasMotor'      => (float) $position->getParameter(Position::VIRTUAL_ENGINE_HOURS_KEY, 0),
            'idMunicipalidad' => (string) $user->token_muni,
            'ubigeo'          => (string) $user->ubigeo_muni,
            'codigoComisaria' => $type === Device::MININTER_TYPE_POLICIAL ? (string) $user->codigo_comisaria : null,
            'ignition'        => $this->toInt($params['ignition'] ?? false),
            'imei'            => (string) $device->imei,
            'latitud'         => (float) $position->latitude,
            'longitud'        => (float) $position->longitude,
            'motion'          => $this->toInt($params['motion'] ?? false),
            'placa'           => (string) $device->plate_number,
            'totalDistancia'  => (float) ($params['totaldistance'] ?? 0),
            'totalHorasMotor' => (float) ($params['enginehours'] ?? 0),
            'valid'           => (bool) $position->valid,
            'velocidad'       => (float) $position->speed,
            'other'           => $params,
        ]);
    }

    /**
     * Salto implausible entre dos posiciones consecutivas (filtro heredado de SUTRAN).
     */
    public function isImplausibleJump(Position $position, Position $prev): bool
    {
        $speed = (float) $position->speed;
        $prevSpeed = (float) $prev->speed;

        if ($prevSpeed <= self::SUTRAN_STOPPED_MAX && $speed >= self::SUTRAN_FAST_MIN) {
            return true;
        }

        $timeDiff = strtotime($position->device_time) - strtotime($prev->device_time);
        $speedDiff = abs($speed - $prevSpeed);

        return $timeDiff <= self::SUTRAN_TIME_GAP && $speedDiff > self::SUTRAN_SPEED_JUMP;
    }

    private function isTooOld(Position $position, int $maxDays): bool
    {
        $timezone = new DateTimeZone(self::TIMEZONE);
        $deviceTime = (new DateTime($position->device_time))->setTimezone($timezone);
        $now = new DateTime('now', $timezone);

        return $now->diff($deviceTime)->days > $maxDays;
    }

    private function toInt($value): int
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    /** Evita repetir el mismo aviso en cada posición: una vez por hora y clave. */
    private function warnOnce(string $key, IntegrationLogger $log, string $message, array $context): void
    {
        try {
            $first = Cache::add("integrations.warn.{$key}", 1, 3600);
        } catch (Throwable $e) {
            $first = true;
        }

        if ($first) {
            $log->warning($message, $context);
        }
    }

    private function deviceContext(Device $device): array
    {
        return ['device_id' => $device->id, 'imei' => $device->imei, 'plate' => $device->plate_number];
    }
}
