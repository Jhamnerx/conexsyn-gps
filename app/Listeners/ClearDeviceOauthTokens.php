<?php

namespace App\Listeners;

use App\Events\DeviceImeiChanged;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

class ClearDeviceOauthTokens
{
    /**
     * Handle the event.
     *
     * @param  DeviceImeiChanged  $event
     * @return void
     */
    public function handle(DeviceImeiChanged $event)
    {
        $accessTokens = Passport::token()
            ->whereIn('client_id', Client::where('provider', 'devices')->select('id'))
            ->where('user_id', $event->device->id)
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
