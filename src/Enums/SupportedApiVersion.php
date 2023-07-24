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
        return in_array(self::getConfiguredVersion(), array_column(self::cases(), 'value'));
    }

    public static function getConfiguredVersion(): int
    {
        return config('transmorpher.api.version', 1);
    }
}
