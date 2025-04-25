<?php

namespace Transmorpher;

use Transmorpher\Enums\MediaType;

class Document extends OnDemandDerivativeMedia
{
    public MediaType $type = MediaType::DOCUMENT;

    /**
     * Get the default transformations for the preview image displayed as thumbnail on the dropzone component.
     *
     * An image format needs to be applied by default, else the media server would return a document.
     *
     * @return array
     */
    public function getThumbnailDefaultTransformations(): array
    {
        return ['format' => 'jpg'];
    }
}
