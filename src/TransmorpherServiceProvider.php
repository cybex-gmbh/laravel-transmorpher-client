<?php

namespace Transmorpher;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Transmorpher\Controller\ApiController;
use Transmorpher\Controller\MediaController;
use Transmorpher\Controller\UploadController;
use Transmorpher\Controller\UploadStateController;
use Transmorpher\Enums\SupportedApiVersion;
use Transmorpher\Exceptions\UnsupportedApiVersionException;

class TransmorpherServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @throws UnsupportedApiVersionException
     */
    public function boot(): void
    {
        if (!SupportedApiVersion::configuredVersionIsSupported()) {
            throw new UnsupportedApiVersionException();
        }

        $this->publishResources();

        $this->registerRoutes();
        $this->registerBladeNamespace();

        $this->loadMigrations();
        $this->loadViews();
        $this->loadTranslations();

    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigs();
    }

    protected function publishResources(): void
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
    }

    protected function registerRoutes(): void
    {
        Route::post(config('transmorpher.api.notifications_route'), ApiController::class)->name('transmorpherNotifications');

        Route::middleware(array_merge(config('transmorpher.routeMiddleware', ['web', 'auth']), [SubstituteBindings::class]))->group(function () {
            Route::post('transmorpher/{transmorpherMedia}/token', [UploadController::class, 'getUploadToken'])->name('transmorpherUploadToken');
            Route::post('transmorpher/handleUploadResponse/{transmorpherUpload}', [UploadController::class, 'handleUploadResponse'])->name('transmorpherHandleUploadResponse');
            Route::post('transmorpher/{transmorpherMedia}/state', [UploadStateController::class, 'getState'])->name('transmorpherState');
            Route::get('transmorpher/{transmorpherMedia}/getVersions', [MediaController::class, 'getVersions'])->name('transmorpherGetVersions');
            Route::post('transmorpher/{transmorpherMedia}/setVersion', [MediaController::class, 'setVersion'])->name('transmorpherSetVersion');
            Route::post('transmorpher/{transmorpherMedia}/delete', [MediaController::class, 'delete'])->name('transmorpherDelete');
            Route::get('transmorpher/{transmorpherMedia}/getOriginal/{version}', [MediaController::class, 'getOriginal'])->name('transmorpherGetOriginal');
            Route::get('transmorpher/{transmorpherMedia}/getDerivativeForVersion/{version}/{transformations?}', [MediaController::class, 'getDerivativeForVersion'])->name('transmorpherGetDerivativeForVersion');
            Route::post('transmorpher/setUploadingState/{transmorpherUpload}', [UploadStateController::class, 'setUploadingState'])->name('transmorpherSetUploadingState');
        });
    }

    protected function registerBladeNamespace(): void
    {
        Blade::componentNamespace('Transmorpher\\ViewComponents', 'transmorpher');
    }

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(sprintf('%s/Migrations', __DIR__));
    }

    protected function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'transmorpher');
    }

    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'transmorpher');
    }

    protected function mergeConfigs(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/transmorpher.php', 'transmorpher');
    }
}
