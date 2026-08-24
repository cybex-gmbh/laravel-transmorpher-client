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
        Route::prefix('transmorpher')->name('transmorpher.')->group(function () {
            Route::post(config('transmorpher.routes.notifications'), ApiController::class)->name('notifications');

            Route::middleware(array_merge(config('transmorpher.routes.middleware', ['web', 'auth']), [SubstituteBindings::class]))->group(function () {
                # Media
                Route::post('media/{transmorpherMedia}/state', [UploadStateController::class, 'getState'])->name('media.state');
                Route::delete('media/{transmorpherMedia}', [MediaController::class, 'delete'])->name('media.delete');

                # Versions
                Route::get('media/{transmorpherMedia}/versions', [MediaController::class, 'getVersions'])->name('versions.get');
                Route::patch('media/{transmorpherMedia}/versions/{version}', [MediaController::class, 'setVersion'])->name('versions.set');
                Route::get('media/{transmorpherMedia}/versions/{version}/original', [MediaController::class, 'getOriginal'])->name('versions.original.get');
                Route::get('media/{transmorpherMedia}/versions/{version}/derivative/{transformations?}', [MediaController::class, 'getDerivativeForVersion'])->name('versions.derivative.get');

                # Upload
                Route::post('media/{transmorpherMedia}/uploads/reserve', [UploadController::class, 'getUploadToken'])->name('uploads.reserve');
                Route::post('uploads/{transmorpherUpload}/response/handle', [UploadController::class, 'handleUploadResponse'])->name('uploads.response');
                Route::post('uploads/{transmorpherUpload}/state/set', [UploadStateController::class, 'setUploadingState'])->name('uploads.state.set');
                Route::get('uploads/{transmorpherUpload}/chunkUrl/{chunkNumber}', [UploadController::class, 'getChunkUploadUrl'])->name('uploads.url');
                Route::post('uploads/{transmorpherUpload}/complete', [UploadController::class, 'completeUpload'])->name('uploads.complete');
                Route::delete('uploads/{transmorpherUpload}/abort', [UploadController::class, 'abortUpload'])->name('uploads.abort');
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
