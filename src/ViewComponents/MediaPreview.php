<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Topic;

class MediaPreview extends Component
{
    public bool $isReady;
    public string $topicName;

    public function __construct(public Topic $topic)
    {
        $this->isReady = $topic->getTransmorpherMedia()->is_ready;
        $this->topicName = $topic->getTransmorpherMedia()->topic_name;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render(): string|View
    {
        return view(sprintf('transmorpher::%s-preview', $this->topic->getTransmorpherMedia()->type->value));
    }
}
