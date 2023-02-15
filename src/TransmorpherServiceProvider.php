<?php

namespace Cybex\Transmorpher;

use Illuminate\Support\ServiceProvider;

class TransmorpherServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/transmorpher.php' => config_path('transmorpher.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/transmorpher.php', 'transmorpher');

        // Register the main class to use with the facade
        $this->app->singleton('transmorpher', function () {
            return new Transmorpher;
        });
    }
}
