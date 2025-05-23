<?php

namespace Transmorpher\Enums;

use Illuminate\Support\Arr;
use Transmorpher\Image;
use Transmorpher\Document;
use Transmorpher\Video;

enum MediaType: string
{
    case IMAGE = 'image';
    case DOCUMENT = 'document';
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
            MediaType::DOCUMENT => Document::class,
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

    public static function asArray(): array
    {
        return Arr::mapWithKeys(self::cases(), fn(MediaType $case) => [$case->name => $case->value]);
    }
}
