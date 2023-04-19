<?php

namespace Transmorpher;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Transmorpher\Helpers\Callback;
use Transmorpher\Helpers\UploadToken;
use Transmorpher\ViewComponents\ImageDropzone;
use Transmorpher\ViewComponents\VideoDropzone;

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
        $this->loadViewsFrom(__DIR__ . '/Views', 'transmorpher');

        Blade::component('image-dropzone', ImageDropzone::class);
        Blade::component('video-dropzone', VideoDropzone::class);
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
            Route::post('transmorpher/image/token', [UploadToken::class, 'getImageUploadToken'])->name('transmorpherImageToken');
            Route::post('transmorpher/video/token', [UploadToken::class, 'getVideoUploadToken'])->name('transmorpherVideoToken');;
            Route::post('transmorpher/handleUploadResponse', [UploadToken::class, 'handleUploadResponse'])->name('transmorpherHandleUploadResponse');;
        });
    }
}
