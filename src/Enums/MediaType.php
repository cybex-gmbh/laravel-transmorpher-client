<?php

namespace Transmorpher\Enums;

use Transmorpher\Image;
use Transmorpher\Pdf;
use Transmorpher\Video;

enum MediaType: string
{
    case IMAGE = 'image';
    case PDF = 'pdf';
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
            MediaType::PDF => Pdf::class,
            MediaType::VIDEO => Video::class,
        };
    }

    /**
     * Get the "upload in progress" translation for the media type.
     *
     * @return string
     */
    public function getUploadInProgressTranslation(): string
    {
        return trans(sprintf('transmorpher::dropzone.%s_in_process', $this->value));
    }
}
