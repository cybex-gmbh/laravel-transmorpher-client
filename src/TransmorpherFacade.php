<?php

namespace Cybex\Transmorpher;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Cybex\Transmorpher\Skeleton\SkeletonClass
 */
class TransmorpherFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'transmorpher';
    }
}
