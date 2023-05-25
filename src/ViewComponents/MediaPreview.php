<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Transmorpher;

class MediaPreview extends Component
{
    public bool $isReady;
    public string $differentiator;

    public function __construct(public Transmorpher $motif)
    {
        $this->isReady = $motif->getTransmorpherMedia()->is_ready;
        $this->differentiator = $motif->getTransmorpherMedia()->differentiator;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render(): string|View
    {
        return view(sprintf('transmorpher::%s-preview', $this->motif->getTransmorpherMedia()->type->value));
    }
}