<?php

namespace Transmorpher\Enums;

enum ServerNotification: string
{
    case VIDEO_TRANSCODING = 'video_transcoding';
    case CACHE_INVALIDATION = 'cache_invalidation';
}
