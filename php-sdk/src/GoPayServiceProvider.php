<?php

declare(strict_types=1);

namespace Mecxer713\GoPay;

use Mecxer713\GoPay\Laravel\GoPayServiceProvider as LaravelGoPayServiceProvider;

/**
 * Backward compatible service provider.
 * Recommended: use Mecxer713\GoPay\Laravel\GoPayServiceProvider
 */
class GoPayServiceProvider extends LaravelGoPayServiceProvider
{
}
