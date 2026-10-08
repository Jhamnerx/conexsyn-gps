<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts()
    {
        return config('app.trust_hosts');
    }

    protected function shouldSpecifyTrustedHosts()
    {
        if (!parent::shouldSpecifyTrustedHosts())
            return false;

        return !empty(config('app.trust_hosts'));
    }
}