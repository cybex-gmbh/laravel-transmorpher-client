<?php

namespace Transmorpher\Exceptions;

use Exception;
use Throwable;

class MissingMorphAliasException extends Exception
{
    public function __construct($class, $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Missing alias for class %s. Please provide a morph alias in the AppServiceProvider or set the transmorpherAlias property.', $class), $code, $previous);
    }
}

