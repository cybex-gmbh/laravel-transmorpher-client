<?php

namespace Transmorpher;

use Transmorpher\Enums\MediaType;

class Document extends StaticMedia
{
    public MediaType $type = MediaType::DOCUMENT;
}
