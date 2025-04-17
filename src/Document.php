<?php

namespace Transmorpher;

use Transmorpher\Enums\MediaType;

class Document extends OnDemandDerivativeMedia
{
    public MediaType $type = MediaType::DOCUMENT;
}
