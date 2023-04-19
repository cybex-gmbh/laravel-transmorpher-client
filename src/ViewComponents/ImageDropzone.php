<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\ImageTransmorpher;

class ImageDropzone extends Component
{
    public function __construct(public ImageTransmorpher $imageTransmorpher)
    {
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render(): string|View
    {
        return view('transmorpher::image-dropzone');
    }
}