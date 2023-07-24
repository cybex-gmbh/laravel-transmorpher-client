<?php

namespace Transmorpher\Enums;

enum SupportedApiVersion: int
{
    case VERSION_1 = 1;

    /**
     * Checks whether the configured API version is supported.
     *
     * @return bool
     */
    public static function isSupported(): bool
    {
        return in_array(config('transmorpher.api.version', 1), array_column(self::cases(), 'value'));
    }
}
