<?php

namespace Cybex\Transmorpher;

use Cybex\Transmorpher\Models\MediaUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
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

        // Register the main class to use with the facade
        $this->app->singleton('transmorpher', function () {
            return new Transmorpher;
        });
    }

    protected function registerRoutes()
    {
        Route::post(config('transmorpher.api.callback_route'), function (Request $request) {
            //        file_put_contents('blabla', sodium_crypto_sign_open(sodium_hex2bin($request->get(0)), Http::get(config('transmorpher.api.url') . '/publickey')), true);
            $response = json_decode(sodium_crypto_sign_open(sodium_hex2bin($request->get(0)), Http::get(config('transmorpher.api.url') . '/publickey')), true);
            // TODO id_token has to belong to a protocol entry, else can't identify protocol entry to update
            $protocolEntry = MediaUpload::whereIdToken($response['id_token'])->first()->MediaUploadProtocols();

            $protocolEntry->update([
                'public_path' => $response['public_path'],
                'state'       => $response['success'] ? State::SUCCESS : State::ERROR,
            ]);

            return $response;
        })->name('transmorpherCallback');
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
