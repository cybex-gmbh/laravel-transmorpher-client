<?php

namespace Transmorpher\Enums;

use InvalidArgumentException;
use Transmorpher\Exceptions\InvalidTransformationValueException;
use Transmorpher\Exceptions\TransformationNotFoundException;

enum Transformation: string
{
    case WIDTH = 'w';
    case HEIGHT = 'h';
    case FORMAT = 'f';
    case QUALITY = 'q';

    public function getUrlRepresentation(string|int $value): string
    {
        return sprintf('%s-%s', $this->value, $this->validate($value));
    }

    public function validate(string|int $value): string|int
    {
        $valid = match ($this) {
            self::WIDTH,
            self::HEIGHT => filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
            self::FORMAT => is_string($value),
            self::QUALITY => filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]])
        };

        if (!$valid) {
            throw new InvalidTransformationValueException($value, $this->name);
        }

        return $value;
    }
}
