<?php

namespace Mecxer713\GoPay\Facades;

use Illuminate\Support\Facades\Facade;

class GoPay extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'gopay';
    }
}
