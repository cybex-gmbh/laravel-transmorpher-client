<?php

namespace Transmorpher;

use Cybex\Transmorpher\Models\MediaUpload;
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

        $this->registerRoutes();
        $this->publishMigrations();
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

    /**
     * Publish the package's migrations.
     *
     * @return void
     */
    protected function publishMigrations()
    {
        $this->publishMigration('CreateTransmorpherMediaTable', 'create_transmorpher_media_table.php');
        // This has to be done after the CreateTransmorpherMediaTable migration.
        $this->publishMigration('CreateTransmorpherProtocolsTable', 'create_transmorpher_protocols_table.php', 1);
    }

    /**
     * Publish a single migration with a timestamp.
     *
     * @param string   $className
     * @param string   $migrationName
     * @param int|null $addSeconds
     *
     * @return void
     */
    protected function publishMigration(string $className, string $migrationName, int $addSeconds = null)
    {
        if (class_exists($className)) {
            return;
        }

        $timestamp = date('Y_m_d_His', time() + $addSeconds);
        $stub      = sprintf('%s/Migrations/%s', __DIR__, $migrationName);
        $target    = $this->app->databasePath(sprintf('migrations/%s_%s', $timestamp, $migrationName));

        $this->publishes([$stub => $target], 'transmorpher.migrations');
    }
}
