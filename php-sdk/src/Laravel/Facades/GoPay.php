<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Mecxer713\GoPay\GoPayServiceInterface;

class GoPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GoPayServiceInterface::class;
    }
}
