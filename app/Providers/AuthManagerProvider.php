<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Tobuli\Services\Auth\AzureAuth;
use Tobuli\Services\Auth\EmailAuth;
use Tobuli\Services\Auth\PhoneAuth;
use Tobuli\Services\AuthManager;

class AuthManagerProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AzureAuth::class, function () {
            return new AzureAuth();
        });

        $this->app->tag([
            PhoneAuth::class,
            AzureAuth::class,
            EmailAuth::class,
        ], 'auths');

        $this->app->singleton(AuthManager::class, function () {
            return new AuthManager($this->app->tagged('auths'));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            AzureAuth::class,
            'auths',
            AuthManager::class,
        ];
    }
}
