<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tobuli\Traits\Searchable;

class DeviceModel extends AbstractEntity
{
    use HasFactory, Searchable;

    protected $table = 'device_models';

    protected $fillable = [
        'title',
        'protocol',
        'model',
        'active',
    ];

    protected array $searchable = [
        'title',
        'protocol',
        'model',
    ];
}
