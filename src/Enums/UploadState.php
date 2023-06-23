<?php

namespace Transmorpher\Enums;

enum UploadState: string
{
    case INITIALIZING = 'initializing';
    case PROCESSING = 'processing';
    case ERROR = 'error';
    case SUCCESS = 'success';
    case DELETED = 'deleted';
}
