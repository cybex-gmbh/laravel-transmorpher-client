<?php

namespace Transmorpher;

use Transmorpher\Enums\MediaType;

class Pdf extends StaticMedia
{
    public MediaType $type = MediaType::PDF;
}
