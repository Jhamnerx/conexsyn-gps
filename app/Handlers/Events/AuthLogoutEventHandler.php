<?php

namespace App\Handlers\Events;

use App\Http\Middleware\OneSessionPerUser;
use Illuminate\Auth\Events\Logout;
use PragmaRX\Google2FALaravel\Support\Authenticator as Google2fa;
use Tobuli\Entities\User;
use Tobuli\Services\Auth\AuthInterface;


class AuthLogoutEventHandler {

    /**
     * Create the event handler.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Logout $event
     * @return void
     */
    public function handle(Logout $event)
    {
        $this->handleAuthMethods($event);
        $this->handleOneSessionPerUser($event);
        $this->handle2fa($event);

        session()->forget('hash');

        User::clearBootedModels();
    }

    private function handleAuthMethods(Logout $event): void
    {
        /** @var AuthInterface $auth */
        foreach (app()->tagged('auths') as $auth) {
            $auth->prepareLogout($event->user);
        }
    }

    private function handleOneSessionPerUser(Logout $event): void
    {
        if (!config('addon.one_session_per_user')) {
            return;
        }

        if (!$event->user instanceof User) {
            return;
        }

        if (OneSessionPerUser::hasOtherSession($event->user)) {
            return;
        }

        OneSessionPerUser::forgetSession($event->user);
    }

    private function handle2fa(Logout $event): void
    {
        if (!config('google2fa.enabled')) {
            return;
        }

        if (!$event->user instanceof User) {
            return;
        }

        $key = config('google2fa.session_var');
        $prevUserKey = "previous_user_$key";

        if (session()->has($prevUserKey)) {
            session()->put($key, session($prevUserKey));
            session()->forget($prevUserKey);

            return;
        }

        if (session()->has('previous_user') && session()->has($key)) {
            session()->put($prevUserKey, session($key));
        }

        (new Google2fa(request()))->logout();
    }
}