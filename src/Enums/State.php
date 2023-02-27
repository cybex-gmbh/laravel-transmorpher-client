<?php

namespace Transmorpher\Enums;

enum State: string
{
    case PROCESSING = 'processing';
    case ERROR = 'error';
    case SUCCESS = 'success';
    case DELETED = 'deleted';
}
