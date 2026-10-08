<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermission extends AbstractEntity
{
    protected $table = 'user_permissions';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
