<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Posición encolada para retransmitir a MININTER (serenazgo o policial).
 *
 * Ciclo de `status`: pending -> queued -> (borrada al éxito | error -> reintento | failed).
 */
class Mininter extends AbstractEntity
{
    const STATUS_PENDING = 'pending';
    const STATUS_QUEUED  = 'queued';
    const STATUS_ERROR   = 'error';
    const STATUS_FAILED  = 'failed';

    const TYPE_SERENAZGO = 'serenazgo';
    const TYPE_POLICIAL  = 'policial';

    protected $table = 'mininter';

    protected $fillable = [
        'device_id',
        'tipo',
        'alarma',
        'altitud',
        'angulo',
        'distancia',
        'fechaHora',
        'timestamp',
        'horasMotor',
        'idMunicipalidad',
        'codigoComisaria',
        'ignition',
        'imei',
        'latitud',
        'longitud',
        'motion',
        'placa',
        'totalDistancia',
        'totalHorasMotor',
        'ubigeo',
        'valid',
        'velocidad',
        'other',
        'status',
        'last_error',
        'retry_count',
        'failed_at',
    ];

    protected $casts = [
        'other'       => 'array',
        'valid'       => 'boolean',
        'retry_count' => 'integer',
        'failed_at'   => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'id');
    }

    public function isPolicial(): bool
    {
        return $this->tipo === self::TYPE_POLICIAL;
    }

    /** Nuevas (o heredadas sin estado). */
    public function scopePending(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', self::STATUS_PENDING)->orWhereNull('status');
        });
    }

    /** Con error, sin agotar reintentos y con la espera cumplida. */
    public function scopeRetryable(Builder $query, int $maxRetries, int $afterMinutes): Builder
    {
        return $query->where('status', self::STATUS_ERROR)
            ->where('retry_count', '<', $maxRetries)
            ->where('updated_at', '<', now()->subMinutes($afterMinutes));
    }

    public function scopeReadyToDispatch(Builder $query, int $maxRetries, int $afterMinutes): Builder
    {
        return $query->where(function (Builder $q) use ($maxRetries, $afterMinutes) {
            $q->where(fn (Builder $pending) => $pending->pending())
                ->orWhere(fn (Builder $retry) => $retry->retryable($maxRetries, $afterMinutes));
        });
    }

    /** Marcadas `queued` hace más de $ttlMinutes: el job se perdió y hay que reencolarlas. */
    public function scopeStuckQueued(Builder $query, int $ttlMinutes): Builder
    {
        return $query->where('status', self::STATUS_QUEUED)
            ->where('updated_at', '<', now()->subMinutes($ttlMinutes));
    }
}
