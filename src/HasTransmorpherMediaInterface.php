<?php

namespace Transmorpher;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasTransmorpherMediaInterface
{
    /**
     * Return all transmorpher media.
     *
     * @return MorphMany
     */
    public function TransmorpherMedia(): MorphMany;
}
