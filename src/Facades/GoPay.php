<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Facades;

use Illuminate\Support\Facades\Facade;

class GoPay extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'gopay';
    }
}
