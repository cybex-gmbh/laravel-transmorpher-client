<?php

namespace Transmorpher\Exceptions;

use Exception;
use Throwable;

class UnknownServerNotificationException extends Exception
{
    public function __construct(string $serverNotification, $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Received an unknown server notification of the type %s. This might be due to an outdated package version.',
                $serverNotification
            ), $code, $previous
        );
    }
}
