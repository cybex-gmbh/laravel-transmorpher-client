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
            $this->publishConfigs();

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

    protected function publishConfigs(): void
    {
        foreach (['api', 'delivery', 'routes', 'upload', 'upload/image', 'upload/document', 'upload/video'] as $config) {
            $this->publishes([
                sprintf('%s/../config/transmorpher/%s.php', __DIR__, $config) => config_path(sprintf('transmorpher/%s.php', $config)),
            ], ['transmorpher', 'transmorpher.config', sprintf('transmorpher.config.%s', str_replace('/', '.', $config))]);
        }
    }

    protected function registerRoutes(): void
    {
        Route::prefix(config('transmorpher.routes.prefix'))->name('transmorpher.')->group(function () {
            Route::post(config('transmorpher.routes.notifications'), ApiController::class)->name('notifications');

            Route::middleware(array_merge(config('transmorpher.routes.middleware', ['web', 'auth']), [SubstituteBindings::class]))->group(function () {
                Route::prefix('media/{transmorpherMedia}')->group(function () {
                    Route::name('media.')->group(function () {
                        Route::post('state', [UploadStateController::class, 'getState'])->name('state');
                        Route::delete('', [MediaController::class, 'delete'])->name('delete');
                        Route::post('upload/reserve', [UploadController::class, 'getUploadToken'])->name('reserve');
                    });

                    Route::prefix('versions')->name('versions.')->group(function () {
                        Route::get('', [MediaController::class, 'getVersions'])->name('get');

                        Route::prefix('{version}')->group(function () {
                            Route::patch('', [MediaController::class, 'setVersion'])->name('set');
                            Route::get('original', [MediaController::class, 'getOriginal'])->name('original.get');
                            Route::get('derivative/{transformations?}', [MediaController::class, 'getDerivativeForVersion'])->name('derivative.get');
                        });
                    });
                });

                Route::prefix('uploads/{transmorpherUpload}')->name('uploads.')->group(function () {
                    Route::post('response/handle', [UploadController::class, 'handleUploadResponse'])->name('response');
                    Route::post('state/set', [UploadStateController::class, 'setUploadingState'])->name('state.set');
                    Route::get('chunkUrl/{chunkNumber}', [UploadController::class, 'getChunkUploadUrl'])->name('url');
                    Route::post('complete', [UploadController::class, 'completeUpload'])->name('complete');
                    Route::delete('abort', [UploadController::class, 'abortUpload'])->name('abort');
                });
            });
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
        $this->mergeConfigFrom(__DIR__ . '/../config/transmorpher/api.php', 'transmorpher.api');
        $this->mergeConfigFrom(__DIR__ . '/../config/transmorpher/delivery.php', 'transmorpher.delivery');
        $this->mergeConfigFrom(__DIR__ . '/../config/transmorpher/routes.php', 'transmorpher.routes');
        $this->mergeConfigFrom(__DIR__ . '/../config/transmorpher/upload.php', 'transmorpher.upload');
        $this->mergeConfigFrom(__DIR__ . '/../config/transmorpher/upload/image.php', 'transmorpher.upload.image');
        $this->mergeConfigFrom(__DIR__ . '/../config/transmorpher/upload/document.php', 'transmorpher.upload.document');
        $this->mergeConfigFrom(__DIR__ . '/../config/transmorpher/upload/video.php', 'transmorpher.upload.video');
    }
}
