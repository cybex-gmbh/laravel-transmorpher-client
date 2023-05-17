<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\State;
use Transmorpher\Transmorpher;

class TransmorpherDropzone extends Component
{
    public bool $isImage;
    public bool $isProcessing;
    public bool $isUploading;
    public bool $isReady;
    public string $differentiator;
    public $transmorpherMediaKey;
    public ?string $latestUploadToken;

    public string $stateRoute;
    public string $uploadTokenRoute;
    public string $handleUploadResponseRoute;
    public string $getVersionsRoute;
    public string $setVersionRoute;
    public string $deleteRoute;
    public string $getOriginalRoute;
    public string $setUploadingStateRoute;
    public mixed $lastUpdated;

    public function __construct(public Transmorpher $motif)
    {
        $this->isImage = $motif->getTransmorpherMedia()->type === MediaType::IMAGE;
        $this->isProcessing = $motif->getTransmorpherMedia()->latest_upload_state === State::PROCESSING;
        $this->isUploading = $motif->getTransmorpherMedia()->latest_upload_state === State::UPLOADING;
        $this->isReady = $motif->getTransmorpherMedia()->is_ready;
        $this->differentiator = $motif->getTransmorpherMedia()->differentiator;
        $this->transmorpherMediaKey = $motif->getTransmorpherMedia()->getKey();
        $this->latestUploadToken = $motif->getTransmorpherMedia()->latest_upload_token;
        $this->lastUpdated = $motif->getTransmorpherMedia()->updated_at->timestamp;

        $this->stateRoute = route('transmorpherState', $this->transmorpherMediaKey);
        $this->uploadTokenRoute = route('transmorpherUploadToken', $this->transmorpherMediaKey);
        $this->handleUploadResponseRoute = route('transmorpherHandleUploadResponse', '');
        $this->getVersionsRoute = route('transmorpherGetVersions', $this->transmorpherMediaKey);
        $this->setVersionRoute = route('transmorpherSetVersion', $this->transmorpherMediaKey);
        $this->deleteRoute = route('transmorpherDelete', $this->transmorpherMediaKey);
        $this->getOriginalRoute = route('transmorpherGetOriginal', [$this->transmorpherMediaKey, '']);
        $this->setUploadingStateRoute = route('transmorpherSetUploadingState', '');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render(): string|View
    {
        return view('transmorpher::transmorpher-dropzone');
    }
}
