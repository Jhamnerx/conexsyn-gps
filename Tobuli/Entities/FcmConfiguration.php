<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Tobuli\Traits\Searchable;

class FcmConfiguration extends AbstractEntity
{
    use Searchable;

    protected $table = 'fcm_configurations';

    protected $fillable = [
        'title',
        'is_default',
        'config',
    ];

    protected array $searchable = [
        'title',
        'project_id',
    ];

    public function tokens(): HasMany
    {
        return $this->hasMany(FcmToken::class, 'project_id', 'project_id');
    }
}
