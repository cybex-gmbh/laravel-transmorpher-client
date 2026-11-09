<?php

namespace Transmorpher\ViewComponents;

use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Enums\Transformation;
use Transmorpher\Enums\UploadState;
use Transmorpher\Media;

class Dropzone extends Component
{
    public bool $isProcessing;
    public bool $isUploading;
    public bool $isReady;
    public string $mediaName;
    public string|int $transmorpherMediaKey;
    public ?string $latestUploadToken;
    public int $lastUpdated;
    public array $srcSetTransformations;
    public ?float $acceptedCalculatedRatio;
    public array $translations;
    public ?string $uploadHandler = null;
    public bool $hasConnectionError = false;
    public array $routes;

    protected array $routesByName;

    public function __construct(
        public Media $media,
        public ?string $width = null,
        public ?int $acceptedMinWidth = null,
        public ?int $acceptedMaxWidth = null,
        public ?int $acceptedMinHeight = null,
        public ?int $acceptedMaxHeight = null,
        public ?string $acceptedDisplayRatio = null,
    )
    {
        $thumbnailDefaultTransformations = $media->getThumbnailDefaultTransformations() ?? [];
        $thumbnailDefaultTransformationsUrlRepresentation = $media->getTransformations($thumbnailDefaultTransformations);

        $this->isProcessing = $media->getTransmorpherMedia()->latest_upload_state === UploadState::PROCESSING;
        $this->isUploading = $media->getTransmorpherMedia()->latest_upload_state === UploadState::UPLOADING;
        $this->isReady = $media->getTransmorpherMedia()->is_ready;
        $this->mediaName = $media->getTransmorpherMedia()->media_name;
        $this->transmorpherMediaKey = $media->getTransmorpherMedia()->getKey();
        $this->latestUploadToken = $media->getTransmorpherMedia()->latest_upload_token;
        $this->lastUpdated = $media->getTransmorpherMedia()->updated_at->timestamp;
        $this->srcSetTransformations = [
            '150w' => implode('+', array_filter([$thumbnailDefaultTransformationsUrlRepresentation, Transformation::WIDTH->getUrlRepresentation(150)])),
            '300w' => implode('+', array_filter([$thumbnailDefaultTransformationsUrlRepresentation, Transformation::WIDTH->getUrlRepresentation(300)])),
            '600w' => implode('+', array_filter([$thumbnailDefaultTransformationsUrlRepresentation, Transformation::WIDTH->getUrlRepresentation(600)])),
            '900w' => implode('+', array_filter([$thumbnailDefaultTransformationsUrlRepresentation, Transformation::WIDTH->getUrlRepresentation(900)])),
        ];
        $this->acceptedMinWidth ??= $this->media->getMinWidth();
        $this->acceptedMaxWidth ??= $this->media->getMaxWidth();
        $this->acceptedMinHeight ??= $this->media->getMinHeight();
        $this->acceptedMaxHeight ??= $this->media->getMaxHeight();
        $this->acceptedDisplayRatio ??= $this->media->getDisplayRatio();
        $this->acceptedCalculatedRatio = $this->media->getCalculatedRatio($this->acceptedDisplayRatio);
        $this->translations = trans('transmorpher::dropzone', [
            'minWidth' => $this->acceptedMinWidth ?? 'none',
            'maxWidth' => $this->acceptedMaxWidth ?? 'none',
            'minHeight' => $this->acceptedMinHeight ?? 'none',
            'maxHeight' => $this->acceptedMaxHeight ?? 'none',
            'ratio' => $this->acceptedDisplayRatio,
        ]);

        $uploadHandlerResponse = $this->media->getUploadHandler();

        if ($uploadHandlerResponse['state'] === UploadState::ERROR->value) {
            $this->hasConnectionError = true;
        } else {
            $this->uploadHandler = $uploadHandlerResponse['uploadHandler'];
        }

        $this->assembleRoutes();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render(): string|View
    {
        return view('transmorpher::dropzone');
    }

    protected function assembleRoutes(): void
    {
        $this->routes = [
            'media' => [
                'delete' => $this->assembleRoute('media.delete'),
                'getState' => $this->assembleRoute('media.state'),
                'reserveUpload' => $this->assembleRoute('media.reserve'),
            ],
            'uploads' => [
                'abort' => $this->assembleRoute('uploads.abort'),
                'complete' => $this->assembleRoute('uploads.complete'),
                'handleResponse' => $this->assembleRoute('uploads.response'),
                'setState' => $this->assembleRoute('uploads.state.set'),
                'url' => $this->assembleRoute('uploads.url'),
            ],
            'versions' => [
                'get' => $this->assembleRoute('versions.get'),
                'getDerivative' => $this->assembleRoute('versions.derivative.get'),
                'getOriginal' => $this->assembleRoute('versions.original.get'),
                'set' => $this->assembleRoute('versions.set'),
            ],
        ];
    }

    protected function assembleRoute(string $routeName): string
    {
        $domain = config('app.url');
        $this->routesByName ??= Route::getRoutes()->getRoutesByName();

        return sprintf('%s/%s', $domain, $this->routesByName[sprintf('transmorpher.%s', $routeName)]->uri);
    }
}
