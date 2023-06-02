<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Transmorpher;

class VersionCard extends Component
{
    public mixed $mediaType;

    public function __construct(public Transmorpher $motif)
    {
        $this->mediaType = $motif->getTransmorpherMedia()->type;
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
