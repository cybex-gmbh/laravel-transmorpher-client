<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\Transformation;
use Transmorpher\Enums\UploadState;
use Transmorpher\Topic;

class Dropzone extends Component
{
    public MediaType $mediaType;
    public bool $isProcessing;
    public bool $isUploading;
    public bool $isReady;
    public string $topicName;
    public string|int $transmorpherMediaKey;
    public ?string $latestUploadToken;
    public int $lastUpdated;
    public array $mediaTypes;
    public array $srcSetTransformations;
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

    public function __construct(public Topic $topic)
    {
        $this->mediaType = $topic->getTransmorpherMedia()->type;
        $this->isProcessing = $topic->getTransmorpherMedia()->latest_upload_state === UploadState::PROCESSING;
        $this->isUploading = $topic->getTransmorpherMedia()->latest_upload_state === UploadState::UPLOADING;
        $this->isReady = $topic->getTransmorpherMedia()->is_ready;
        $this->topicName = $topic->getTransmorpherMedia()->topic_name;
        $this->transmorpherMediaKey = $topic->getTransmorpherMedia()->getKey();
        $this->latestUploadToken = $topic->getTransmorpherMedia()->latest_upload_token;
        $this->lastUpdated = $topic->getTransmorpherMedia()->updated_at->timestamp;
        $this->mediaTypes = array_column(MediaType::cases(), 'value', 'name');
        $this->srcSetTransformations = [
            '150w' => Transformation::WIDTH->getUrlRepresentation(150),
            '300w' => Transformation::WIDTH->getUrlRepresentation(300),
            '600w' => Transformation::WIDTH->getUrlRepresentation(600),
            '900w' => Transformation::WIDTH->getUrlRepresentation(900),
        ];
        $this->translations = trans('transmorpher::dropzone');

        $this->stateRoute = route('transmorpherState', $this->transmorpherMediaKey);
        $this->uploadTokenRoute = route('transmorpherUploadToken', $this->transmorpherMediaKey);
        $this->handleUploadResponseRoute = route('transmorpherHandleUploadResponse', '');
        $this->getVersionsRoute = route('transmorpherGetVersions', $this->transmorpherMediaKey);
        $this->setVersionRoute = route('transmorpherSetVersion', $this->transmorpherMediaKey);
        $this->deleteRoute = route('transmorpherDelete', $this->transmorpherMediaKey);
        $this->getOriginalRoute = route('transmorpherGetOriginal', [$this->transmorpherMediaKey, '']);
        $this->getDerivativeForVersionRoute = route('transmorpherGetDerivativeForVersion', [$this->transmorpherMediaKey, '']);
        $this->setUploadingStateRoute = route('transmorpherSetUploadingState', '');
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
