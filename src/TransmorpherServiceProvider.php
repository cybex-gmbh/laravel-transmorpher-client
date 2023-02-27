<?php

namespace Transmorpher;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Transmorpher\Helpers\Callback;

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
            ], 'transmorpher.config');
        }

        $this->loadMigrationsFrom(sprintf('%s/Migrations', __DIR__));
        $this->registerRoutes();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/transmorpher.php', 'transmorpher');
    }

    protected function registerRoutes()
    {
        Route::post(config('transmorpher.api.callback_route'), [Callback::class, 'handle'])->name('transmorpherCallback');
    }
}
