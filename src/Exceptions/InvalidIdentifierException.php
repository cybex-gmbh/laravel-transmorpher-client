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
                'Morph alias (%s) and differentiator (%s) must match the pattern /^[\w][\w\-]*$/.',
                $model->getMorphClass(),
                $differentiator
            ), $code, $previous);
    }
}

