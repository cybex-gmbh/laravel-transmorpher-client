<?php

namespace Transmorpher\Exceptions;

use Exception;
use Throwable;
use Transmorpher\Enums\SupportedApiVersion;

class UnsupportedApiVersionException extends Exception
{
    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'The configured API version "%s" is not supported by this package version. Supported versions: %s',
                config('transmorpher.api.version'),
                implode(', ', array_column(SupportedApiVersion::cases(), 'value'))
            ), $code, $previous);
    }
}
