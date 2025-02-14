<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Media;

class MediaPreview extends Component
{
    public bool $isReady;
    public string $mediaName;

    public function __construct(public Media $media)
    {
        $this->isReady = $media->getTransmorpherMedia()->is_ready;
        $this->mediaName = $media->getTransmorpherMedia()->media_name;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render(): string|View
    {
        return view(sprintf('transmorpher::%s-preview', $this->media->type->value));
    }
}
