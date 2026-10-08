<?php

namespace App\Handlers\Events;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Tobuli\Entities\SecondaryCredentialsInterface;
use Tobuli\Services\NotificationService;

class AuthLoginEventHandler {

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
     * @param  Login $event
     * @return void
     */
    public function handle(Login $event)
    {
        $user = $event->user;

        session()->put('hash', $user->password_hash);

        $notificationService = new NotificationService();
        $notificationService->check($user);

        $this->writeSecondaryCredentials($user);
        $this->log($event);
    }

    private function writeSecondaryCredentials(Authenticatable $user): void
    {
        if ($user instanceof SecondaryCredentialsInterface && $secondaryCred = $user->getLoginSecondaryCredentials()) {
            session()->put('secondary_cred_email', $secondaryCred->email);
            session()->put('secondary_cred_id', $secondaryCred->id);
        } else {
            session()->put('secondary_cred_email', false);
            session()->put('secondary_cred_id', false);
        }
    }

    private function log(Login $event)
    {
        $config = config('tobuli.model_change_log.login');

        if (empty($config['enable_successful'])) {
            return;
        }

        $user = $event->user;

        Cache::forget("login_failed_$user->email");

        $method = $this->resolveLoginMethod($event);

        if (isset($config['methods']) && !in_array($method, $config['methods'])) {
            return;
        }

        activity()
            ->performedOn($user)
            ->causedBy($this->resolveCausedBy($event, $method))
            ->withProperties(['attributes' => []])
            ->useLog("$user->email | $method")
            ->log('login_success');
    }

    private function resolveCausedBy(Login $event, string $method)
    {
        if ($method === 'login_as') {
            return session('previous_user');
        }

        return $event->user;
    }

    private function resolveLoginMethod(Login $event): string
    {
        if (str_starts_with(request()->getRequestUri(), '/api')) {
            return 'api';
        }

        if (auth()->guard()->viaRemember()) {
            return 'remember_me';
        }

        if ($event->user instanceof SecondaryCredentialsInterface && $event->user->getLoginSecondaryCredentials()) {
            return 'secondary_credentials';
        }

        if (session()->has('previous_user')) {
            return 'login_as';
        }

        return 'simple';
    }
}