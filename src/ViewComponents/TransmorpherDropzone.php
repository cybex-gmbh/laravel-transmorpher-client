<?php

namespace Transmorpher\ViewComponents;

use Illuminate\View\Component;
use Illuminate\View\View;
use Transmorpher\Transmorpher;

class TransmorpherDropzone extends Component
{
    public function __construct(public Transmorpher $transmorpher)
    {
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render(): string|View
    {
        return view('transmorpher::transmorpher-dropzone');
    }
}
