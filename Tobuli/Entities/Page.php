<?php

namespace Tobuli\Entities;

use Tobuli\Traits\Searchable;

class Page extends AbstractEntity
{
    use Searchable;

    public $timestamps = false;

    protected $fillable = [
        'slug',
        'title',
        'content',
    ];

    protected array $searchable = [
        'slug',
        'title',
    ];
}
