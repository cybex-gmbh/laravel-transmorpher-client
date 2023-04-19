<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\VideoTransmorpher;

class VideoDropzone extends Component
{
    public function __construct(public VideoTransmorpher $videoTransmorpher)
    {
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render(): string|View
    {
        return view('transmorpher::video-dropzone');
    }
}