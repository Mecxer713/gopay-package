<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Http;

use Mecxer713\GoPay\Exception\GoPayException;

/**
 * Signal interne : la tentative a échoué de manière transitoire et peut être rejouée.
 *
 * @internal
 */
final class RetryableRequestException extends \RuntimeException
{
    /** @var \Closure(): array<string, mixed> */
    private \Closure $fallback;

    /**
     * @param  \Closure(): array<string, mixed>  $fallback  Résultat (ou exception) à produire si plus aucune tentative n'est permise.
     */
    public function __construct(\Closure $fallback)
    {
        parent::__construct('Transient GoPAY request failure.');
        $this->fallback = $fallback;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws GoPayException
     */
    public function fallback(): array
    {
        return ($this->fallback)();
    }
}

