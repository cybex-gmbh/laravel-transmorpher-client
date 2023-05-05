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
        return match ($this) {
            MediaType::IMAGE => ImageTransmorpher::class,
            MediaType::VIDEO => VideoTransmorpher::class,
        };
    }
}
