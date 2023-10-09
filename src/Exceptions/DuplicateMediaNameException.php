<?php

namespace Transmorpher\Exceptions;

use Exception;
use Throwable;

class DuplicateMediaNameException extends Exception
{

    public function __construct($model, $duplicates, $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Duplicate media names where found in %s: %s.', $model, $duplicates->implode(', ')), $code, $previous);
    }
}
