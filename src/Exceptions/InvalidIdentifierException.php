<?php

namespace Transmorpher\Exceptions;

use Exception;
use Throwable;
use Transmorpher\HasTransmorpherMediaInterface;

class InvalidIdentifierException extends Exception
{
    public function __construct(HasTransmorpherMediaInterface $model, string $mediaName, $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Alias (%s) and media name (%s) may only contain alphanumeric characters, underscores and hyphens (regex: /^\w(-?\w)*$/).',
                $model->getTransmorpherAlias(),
                $mediaName
            ), $code, $previous);
    }
}

