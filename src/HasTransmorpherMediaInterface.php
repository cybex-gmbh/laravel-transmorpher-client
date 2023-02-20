<?php

namespace Transmorpher;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasTransmorpherMediaInterface
{
    /**
     * Return all media uploads.
     *
     * @return MorphMany
     */
    public function TransmorpherMedia(): MorphMany;
}
