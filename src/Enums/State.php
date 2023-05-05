<?php

namespace Transmorpher\Enums;

enum State: string
{
    case INIT = 'initializing';
    case PROCESSING = 'processing';
    case ERROR = 'error';
    case SUCCESS = 'success';
    case DELETED = 'deleted';
}
