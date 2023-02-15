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
            ], 'transmorpher.config');
        }

        $this->publishMigrations();
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

    /**
     * Publish the package's migrations.
     *
     * @return void
     */
    protected function publishMigrations()
    {
        $this->publishMigration('CreateMediaUploadsTable', 'create_media_uploads_table.php');
        // This has to be done after the CreateMediaUploadsTable migration.
        $this->publishMigration('CreateMediaUploadProtocolsTable', 'create_media_upload_protocols_table.php', 1);
    }

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
