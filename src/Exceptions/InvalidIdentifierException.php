<?php

namespace Transmorpher\Exceptions;

use Exception;
use Throwable;
use Transmorpher\HasTransmorpherMediaInterface;

class InvalidIdentifierException extends Exception
{
    public function __construct(HasTransmorpherMediaInterface $model, string $differentiator, $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Invalid identifier for model "%s" and differentiator "%s". Please make sure your morph alias doesn\'t contain any special characters',
                $model::class,
                $differentiator
            ), $code, $previous);
    }
}

