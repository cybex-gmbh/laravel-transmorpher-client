<?php

namespace Transmorpher\Enums;

use Transmorpher\ImageTransmorpher;
use Transmorpher\VideoTransmorpher;

enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';

    /**
     * Get the class name of the corresponding Transmorpher class.
     *
     * @return string
     */
    public function getTransmorpherClass(): string
    {
        if ($this === MediaType::IMAGE) {
            $className = ImageTransmorpher::class;
        } else {
            $className = VideoTransmorpher::class;
        }

        return $className;
    }
}
