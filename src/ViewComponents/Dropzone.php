<?php

namespace Transmorpher\ViewComponents;

use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Enums\MediaType;
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
    public array $mediaTypes;
    public array $srcSetTransformations;
    public ?float $acceptedCalculatedRatio;
    public array $translations;
    public string $stateRoute;
    public string $uploadTokenRoute;
    public string $handleUploadResponseRoute;
    public string $getVersionsRoute;
    public string $setVersionRoute;
    public string $deleteRoute;
    public string $getOriginalRoute;
    public string $getDerivativeForVersionRoute;
    public string $setUploadingStateRoute;

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
        $this->mediaTypes = array_column(MediaType::cases(), 'value', 'name');
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

        $domain = config('app.url');
        $routes =  Route::getRoutes()->getRoutesByName();

        $this->stateRoute = sprintf('%s/%s', $domain, $routes['transmorpherState']->uri);
        $this->uploadTokenRoute = sprintf('%s/%s', $domain, $routes['transmorpherUploadToken']->uri);
        $this->handleUploadResponseRoute = sprintf('%s/%s', $domain, $routes['transmorpherHandleUploadResponse']->uri);
        $this->getVersionsRoute = sprintf('%s/%s', $domain, $routes['transmorpherGetVersions']->uri);
        $this->setVersionRoute = sprintf('%s/%s', $domain, $routes['transmorpherSetVersion']->uri);
        $this->deleteRoute = sprintf('%s/%s', $domain, $routes['transmorpherDelete']->uri);
        $this->getOriginalRoute = sprintf('%s/%s', $domain, $routes['transmorpherGetOriginal']->uri);
        $this->getDerivativeForVersionRoute = sprintf('%s/%s', $domain, $routes['transmorpherGetDerivativeForVersion']->uri);
        $this->setUploadingStateRoute = sprintf('%s/%s', $domain, $routes['transmorpherSetUploadingState']->uri);
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
}
