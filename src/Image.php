<?php

namespace Transmorpher;

use Transmorpher\Enums\MediaType;

class Image extends StaticMedia
{
    public MediaType $type = MediaType::IMAGE;
}
