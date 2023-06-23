<?php

namespace Transmorpher\Enums;

enum Transformation: string
{
    case WIDTH = 'w';
    case HEIGHT = 'h';
    case FORMAT = 'f';
    case QUALITY = 'q';

    public function getUrlRepresentation(string|int $value): string
    {
        return sprintf('%s-%s', $this->value, $value);
    }
}
