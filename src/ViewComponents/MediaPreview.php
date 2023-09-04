<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Transmorpher;

class MediaPreview extends Component
{
    public bool $isReady;
    public string $topic;

    public function __construct(public Transmorpher $topicHandler)
    {
        $this->isReady = $topicHandler->getTransmorpherMedia()->is_ready;
        $this->topic = $topicHandler->getTransmorpherMedia()->topic;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render(): string|View
    {
        return view(sprintf('transmorpher::%s-preview', $this->topicHandler->getTransmorpherMedia()->type->value));
    }
}
