<?php

namespace Cybex\Transmorpher;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasMediaUploadsInterface
{
    /**
     * Return all media uploads.
     *
     * @return MorphMany
     */
    public function MediaUploads(): MorphMany;
}
