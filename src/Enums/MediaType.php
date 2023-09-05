<?php

namespace Transmorpher\Enums;

use Transmorpher\Image;
use Transmorpher\Video;

enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';

    /**
     * Get the class name of the corresponding Media class.
     *
     * @return string
     */
    public function getMediaClass(): string
    {
        return match ($this) {
            MediaType::IMAGE => Image::class,
            MediaType::VIDEO => Video::class,
        };
    }
}
