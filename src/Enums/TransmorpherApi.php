<?php

namespace Transmorpher\Enums;

enum TransmorpherApi
{
    case S2S;
    case WEB;

    /**
     * @param string|null $path
     * @return string
     */
    public function getUrl(string $path = null): string
    {
        return match ($this) {
            self::S2S => sprintf('%s/v%d/%s', config('transmorpher.api.s2s_url'), config('transmorpher.api.version'), $path),
            self::WEB => sprintf('%s/v%d/%s', config('transmorpher.api.web_url'), config('transmorpher.api.version'), $path)
        };
    }
}
