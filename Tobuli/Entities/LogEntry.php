<?php

namespace Tobuli\Entities;

use Formatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use ModalHelpers\AlertModalHelper;
use Tobuli\Traits\Searchable;
use Tobuli\Traits\SentCommandActor;

class LogEntry extends AbstractEntity
{
    use Searchable;

    protected array $searchable = [
        'placa',
    ];

    protected $table = 'logs';

    protected $fillable = [
        'service_name',
        'plate_number',
        'level',
        'message',
        'additional_data',
        'created_at',
    ];

    protected $casts = [
        'additional_data' => 'array',
    ];
}
