<?php

namespace Transmorpher;

use Transmorpher\Enums\MediaType;

class Image extends OnDemandDerivativeMedia
{
    public MediaType $type = MediaType::IMAGE;
}
