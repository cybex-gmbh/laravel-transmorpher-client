<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Enums\MediaType;
use Transmorpher\Media;

class VersionCard extends Component
{
    public MediaType $mediaType;

    public function __construct(public Media $media)
    {
        $this->mediaType = $media->getTransmorpherMedia()->type;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render(): string|View
    {
        return view('transmorpher::version-card');
    }
}
