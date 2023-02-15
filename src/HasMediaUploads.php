<?php

namespace Cybex\Transmorpher;

use Cybex\Transmorpher\Models\MediaUpload;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMediaUploads
{
    public function MediaUploads(): MorphMany
    {
        return $this->morphMany(MediaUpload::class, 'uploadable');
    }
}