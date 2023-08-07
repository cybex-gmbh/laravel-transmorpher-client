<?php

namespace Transmorpher;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Transmorpher\Helpers\Callback;
use Transmorpher\Helpers\StateUpdate;
use Transmorpher\Helpers\UploadToken;
use Transmorpher\Helpers\VersionManagement;
use Transmorpher\ViewComponents\TransmorpherDropzone;

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
            ], ['transmorpher', 'transmorpher.config']);

            $this->publishes([
                __DIR__ . '/../dist' => public_path('vendor/transmorpher'),
            ], ['transmorpher', 'transmorpher.assets']);

            $this->publishes([
                __DIR__ . '/resources/views' => resource_path('views/vendor/transmorpher'),
            ], ['transmorpher', 'transmorpher.views']);

            $this->publishes([
                __DIR__ . '/lang' => $this->app->langPath('vendor/transmorpher'),
            ], ['transmorpher', 'transmorpher.lang']);
        }

        $this->loadMigrationsFrom(sprintf('%s/Migrations', __DIR__));
        $this->registerRoutes();
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'transmorpher');
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'transmorpher');

        Blade::componentNamespace('Transmorpher\\ViewComponents', 'transmorpher');
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
        Route::post(config('transmorpher.api.callback_route'), Callback::class)->name('transmorpherCallback');
        Route::middleware('web')->group(function () {
            Route::post('transmorpher/{transmorpherMedia}/token', [UploadToken::class, 'getUploadToken'])->name('transmorpherUploadToken');
            Route::post('transmorpher/handleUploadResponse/{transmorpherUpload}', [UploadToken::class, 'handleUploadResponse'])->name('transmorpherHandleUploadResponse');
            Route::post('transmorpher/{transmorpherMedia}/state', [StateUpdate::class, 'getState'])->name('transmorpherState');
            Route::get('transmorpher/{transmorpherMedia}/getVersions', [VersionManagement::class, 'getVersions'])->name('transmorpherGetVersions');
            Route::post('transmorpher/{transmorpherMedia}/setVersion', [VersionManagement::class, 'setVersion'])->name('transmorpherSetVersion');
            Route::post('transmorpher/{transmorpherMedia}/delete', [VersionManagement::class, 'delete'])->name('transmorpherDelete');
            Route::get('transmorpher/{transmorpherMedia}/getOriginal/{version}', [VersionManagement::class, 'getOriginal'])->name('transmorpherGetOriginal');
            Route::get('transmorpher/{transmorpherMedia}/getDerivativeForVersion/{version}/{transformations?}', [VersionManagement::class, 'getDerivativeForVersion'])->name('transmorpherGetDerivativeForVersion');
            Route::post('transmorpher/setUploadingState/{transmorpherUpload}', [StateUpdate::class, 'setUploadingState'])->name('transmorpherSetUploadingState');
        });
    }
}
