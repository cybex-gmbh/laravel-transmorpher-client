<?php

namespace Transmorpher\Exceptions;

use Exception;
use Throwable;

class InvalidConfigurationValueException extends Exception
{

    public function __construct($configKey, $value, $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('The provided value %s for the configuration key %s is not valid.', $value, $configKey), $code, $previous);
    }
}
