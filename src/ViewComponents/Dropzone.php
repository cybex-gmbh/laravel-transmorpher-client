<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\UploadState;
use Transmorpher\Transmorpher;

class Dropzone extends Component
{
    public bool $isImage;
    public bool $isProcessing;
    public bool $isReady;
    public string $differentiator;
    public string|int $transmorpherMediaKey;
    public ?string $latestUploadToken;
    public string $stateUpdateRoute;
    public string $uploadTokenRoute;
    public string $handleUploadResponseRoute;

    public function __construct(public Transmorpher $motif)
    {
        $this->isImage = $motif->getTransmorpherMedia()->type === MediaType::IMAGE;
        $this->isProcessing = $motif->getTransmorpherMedia()->latest_upload_state === UploadState::PROCESSING;
        $this->isReady = $motif->getTransmorpherMedia()->is_ready;
        $this->differentiator = $motif->getTransmorpherMedia()->differentiator;
        $this->transmorpherMediaKey = $motif->getTransmorpherMedia()->getKey();
        $this->latestUploadToken = $motif->getTransmorpherMedia()->latest_upload_token;

        $this->stateUpdateRoute = route('transmorpherStateUpdate', $this->transmorpherMediaKey);
        $this->uploadTokenRoute = route('transmorpherUploadToken', $this->transmorpherMediaKey);
        $this->handleUploadResponseRoute = route('transmorpherHandleUploadResponse', [$this->transmorpherMediaKey, '']);
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
