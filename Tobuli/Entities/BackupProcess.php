<?php

namespace Tobuli\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupProcess extends AbstractEntity
{
    public const DURATION_ALIVE = 5;

    public const STATUS_COMPLETED = 10;
    public const STATUS_FAILED = 9;
    public const STATUS_EXPIRED = 7;
    public const STATUS_INTERRUPTED = 3;
    public const STATUS_RESERVED = 2;
    public const STATUS_NOT_STARTED = 1;

    private static array $statusTranslations;

    protected $fillable = [
        'backup_id',
        'type',
        'source',
        'options',
        'processed',
        'skipped',
        'total',
        'last_item_id',
        'duration_expire',
        'attempt',
        'reserved_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'options'       => 'json',
        'reserved_at'   => 'datetime',
        'completed_at'  => 'datetime',
        'failed_at'     => 'datetime',
    ];

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isFailed(): bool
    {
        return $this->failed_at !== null;
    }

    public function isInterrupted(): bool
    {
        return !$this->isCompleted() && $this->updated_at->diffInSeconds() >= self::DURATION_ALIVE;
    }

    public function isNotStarted(): bool
    {
        return !$this->isCompleted() && !$this->reserved_at;
    }

    public function isExpired(): bool
    {
        return $this->created_at->diffInSeconds() >= $this->duration_expire;
    }

    public function isReserved(): bool
    {
        return !$this->isCompleted() && $this->updated_at->diffInSeconds() < self::DURATION_ALIVE;
    }

    public function isRunnable(): bool
    {
        $status = $this->getStatus();

        return $status === self::STATUS_INTERRUPTED
            || $status === self::STATUS_NOT_STARTED;
    }

    public function getTranslatedStatus(): string
    {
        $statuses = self::getStatusTranslations();

        return $statuses[$this->getStatus()];
    }

    public function getStatus(): int
    {
        if ($this->isCompleted()) {
            return self::STATUS_COMPLETED;
        }

        if ($this->isFailed()) {
            return self::STATUS_FAILED;
        }

        if ($this->isExpired()) {
            return self::STATUS_EXPIRED;
        }

        if ($this->isInterrupted()) {
            return self::STATUS_INTERRUPTED;
        }

        if ($this->isNotStarted()) {
            return self::STATUS_NOT_STARTED;
        }

        return self::STATUS_RESERVED;
    }

    public function scopeWhereUnreserved(Builder $query, ?string $date = null): Builder
    {
        if ($date === null) {
            $date = date('Y-m-d H:i:s');
        }

        return $query->where(fn (Builder $query) => $query
            ->whereNull('reserved_at')
            ->orWhereRaw("DATE_ADD(updated_at, INTERVAL " . self::DURATION_ALIVE . " SECOND) < '$date'")
        );
    }

    public function scopeWhereReserved(Builder $query, ?string $date = null): Builder
    {
        if ($date === null) {
            $date = date('Y-m-d H:i:s');
        }

        return $query->where(fn (Builder $query) => $query
            ->whereNotNull('reserved_at')
            ->whereRaw("DATE_ADD(updated_at, INTERVAL " . self::DURATION_ALIVE . " SECOND) > '$date'")
        );
    }

    public function scopeWhereStatusReserved(Builder $query, ?string $date = null): Builder
    {
        if ($date === null) {
            $date = date('Y-m-d H:i:s');
        }

        return $query->whereNull('completed_at')
            ->whereReserved($date)
            ->whereUnexpired($date);
    }

    public function scopeWhereUnexpired(Builder $query, ?string $date = null): Builder
    {
        if ($date === null) {
            $date = date('Y-m-d H:i:s');
        }

        return $query->whereRaw("DATE_ADD(created_at, INTERVAL duration_expire SECOND) > '$date'");
    }

    public function scopeWhereUnfinished(Builder $query, ?string $date = null): Builder
    {
        if ($date === null) {
            $date = date('Y-m-d H:i:s');
        }

        return $query->whereNull('completed_at')
            ->whereNull('failed_at')
            ->whereUnexpired($date);
    }

    public function updateReservation(): bool
    {
        $this->reserved_at = date('Y-m-d H:i:s');
        return $this->update();
    }

    private static function getStatusTranslations(): array
    {
        return self::$statusTranslations ?? self::$statusTranslations = [
            self::STATUS_COMPLETED   => trans('front.completed'),
            self::STATUS_FAILED      => trans('global.failed'),
            self::STATUS_EXPIRED     => trans('front.expired'),
            self::STATUS_INTERRUPTED => trans('front.interrupted'),
            self::STATUS_NOT_STARTED => trans('front.not_started'),
            self::STATUS_RESERVED    => trans('front.reserved'),
        ];
    }
}