<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Tobuli\Reports\ReportManager;

class UserReportTypePivot extends Pivot
{
    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'report_type_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function report(): string
    {
        return ReportManager::$types[$this->report_id];
    }
}
