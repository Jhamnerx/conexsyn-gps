<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModelLogConfigProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        foreach (config('tobuli.model_change_log') ?: [] as $class => $config) {
            if (!class_exists($class)) {
                continue;
            }

            if (isset($config['attributes'])) {
                $class::$logAttributes = $config['attributes'];
            }

            if (isset($config['attributes_to_ignore'])) {
                $class::$logAttributesToIgnore = $config['attributes_to_ignore'];
            }

            if (empty($config['include_hidden'])
                && count($hidden = (new \ReflectionClass($class))->getDefaultProperties()['hidden'])
                // creating instance directly causes improper boot execution
            ) {
                $class::$logAttributesToIgnore = $class::$logAttributesToIgnore
                    ? array_unique(array_merge($class::$logAttributesToIgnore, $hidden))
                    : $hidden;
            }

            if (isset($config['fillable'])) {
                $class::$logFillable = $config['fillable'];
            }

            if (isset($config['unguarded'])) {
                $class::$logUnguarded = $config['unguarded'];
            }

            if (isset($config['only_dirty'])) {
                $class::$logOnlyDirty = $config['only_dirty'];
            }

            if (isset($config['submit_empty_logs'])) {
                $class::$submitEmptyLogs = $config['submit_empty_logs'];
            }
        }
    }
}
