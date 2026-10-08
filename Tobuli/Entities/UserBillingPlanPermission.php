<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBillingPlanPermission extends AbstractEntity
{
    protected $table = 'billing_plan_permissions';

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class);
    }
}
