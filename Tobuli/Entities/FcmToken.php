<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FcmToken extends AbstractEntity
{
    protected $table = 'fcm_tokens';

    protected $fillable = ['token', 'project_id'];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(FcmConfiguration::class, 'project_id', 'project_id');
    }
}
