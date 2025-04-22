<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Media;

class Thumbnail extends Component
{
    public bool $isReady;
    public string $mediaName;
    public array $defaultTransformations;

    public function __construct(public Media $media)
    {
        $this->isReady = $media->getTransmorpherMedia()->is_ready;
        $this->mediaName = $media->getTransmorpherMedia()->media_name;
        $this->defaultTransformations = $media->getThumbnailDefaultTransformations();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render(): string|View
    {
        return view('transmorpher::thumbnail');
    }
}
