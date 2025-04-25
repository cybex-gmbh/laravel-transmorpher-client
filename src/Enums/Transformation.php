<?php

namespace Transmorpher\Enums;

use Transmorpher\Exceptions\InvalidTransformationValueException;

enum Transformation: string
{
    case WIDTH = 'w';
    case HEIGHT = 'h';
    case FORMAT = 'f';
    case PAGE = 'p';
    case PPI = 'ppi';
    case QUALITY = 'q';

    /**
     * @param string|int $value
     * @return string
     */
    public function getUrlRepresentation(string|int $value): string
    {
        return sprintf('%s-%s', $this->value, $this->validate($value));
    }

    /**
     * @throws InvalidTransformationValueException
     */
    public function validate(string|int $value): string|int
    {
        $valid = match ($this) {
            self::WIDTH,
            self::HEIGHT,
            self::PAGE,
            self::PPI => filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]),
            self::FORMAT => is_string($value),
            self::QUALITY => filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]])
        };

        if (!$valid) {
            throw new InvalidTransformationValueException($value, $this->name);
        }

        return $value;
    }
}
