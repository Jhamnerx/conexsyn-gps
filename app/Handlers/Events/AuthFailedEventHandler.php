<?php

namespace App\Handlers\Events;

use Illuminate\Auth\Events\Failed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Tobuli\Entities\SecondaryCredentialsInterface;

class AuthFailedEventHandler
{
    /**
     * Handle the event.
     *
     * @param  Failed  $event
     * @return void
     */
    public function handle(Failed $event)
    {
        $this->log($event);
    }

    private function log(Failed $event)
    {
        $config = config('tobuli.model_change_log.login');

        if (empty($config['enable_failed'])) {
            return;
        }

        $user = $event->user;

        if (!$user instanceof Model) {
            return;
        }

        $method = $this->resolveLoginMethod($event);

        if (isset($config['methods']) && !in_array($method, $config['methods'])) {
            return;
        }

        $identifier = Arr::first($event->credentials, fn ($value, $key) => $key !== 'password');
        $cacheKey = "login_failed_$identifier";

        $attempt = Cache::get($cacheKey, 0) + 1;

        Cache::put($cacheKey, $attempt, 300);

        if ($attempt < $config['enable_failed']) {
            return;
        }

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['attributes' => []])
            ->useLog("$identifier | $method | $attempt attempt")
            ->log('login_fail');
    }

    private function resolveLoginMethod(Failed $event): string
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