<?php

namespace Transmorpher\Enums;

use Transmorpher\ImageTopic;
use Transmorpher\VideoTopic;

enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';

    /**
     * Get the class name of the corresponding Topic class.
     *
     * @return string
     */
    public function getTopicClass(): string
    {
        return match ($this) {
            MediaType::IMAGE => ImageTopic::class,
            MediaType::VIDEO => VideoTopic::class,
        };
    }
}
