<?php

namespace App\Http\Middleware;

use PragmaRX\Google2FALaravel\Middleware;

class Check2fa extends Middleware
{
    /**
     * @param  \Illuminate\Http\Request  $request
     */
    public function handle($request, \Closure $next)
    {
        if (!config('google2fa.enabled')) {
            return $next($request);
        }

        if ($request->session()->has('google2fa_secret')) {
            return $next($request);
        }

        if ($request->session()->has('previous_user') && config('google2fa.skip_on_login_as')) {
            return $next($request);
        }

        if (auth()->user() && !auth()->user()->is2faSetup()) {
            return redirect()->route('google_2fa_setup.create');
        }

        return parent::handle($request, $next);
    }
}
