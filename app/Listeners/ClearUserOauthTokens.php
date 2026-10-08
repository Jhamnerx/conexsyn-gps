<?php

namespace App\Listeners;

use Illuminate\Auth\Events\PasswordReset;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

class ClearUserOauthTokens
{
    /**
     * Handle the event.
     *
     * @param  PasswordReset  $event
     * @return void
     */
    public function handle(PasswordReset $event)
    {
        $accessTokens = Passport::token()
            ->whereIn('client_id', Client::where('provider', 'users')->select('id'))
            ->where('user_id', $event->user->getAuthIdentifier())
            ->pluck('id');

        if ($accessTokens->isEmpty()) {
            return;
        }

        Passport::refreshToken()
            ->whereIn('access_token_id', $accessTokens)
            ->update(['revoked' => true]);

        Passport::token()
            ->whereIn('id', $accessTokens)
            ->update(['revoked' => true]);
    }
}
