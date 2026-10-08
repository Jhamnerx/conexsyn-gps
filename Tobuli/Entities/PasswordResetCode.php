<?php

namespace Tobuli\Entities;

class PasswordResetCode extends AbstractEntity
{
    public const LIFESPAN_SECONDS = 3600;

    public $timestamps = false;

    protected $fillable = [
        'email',
        'code',
        'created_at',
    ];
}
