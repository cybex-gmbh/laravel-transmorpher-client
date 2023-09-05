<?php

namespace Transmorpher\Exceptions;

use Exception;
use Throwable;
use Transmorpher\HasTransmorpherMediaInterface;

class InvalidIdentifierException extends Exception
{
    public function __construct(HasTransmorpherMediaInterface $model, string $topicName, $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Alias (%s) and topic name (%s) may only contain alphanumeric characters, underscores and hyphens (regex: /^\w(-?\w)*$/).',
                $model->getTransmorpherAlias(),
                $topicName
            ), $code, $previous);
    }
}

