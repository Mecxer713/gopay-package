<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Facades;

use Illuminate\Support\Facades\Facade;
use Mecxer713\GoPay\DTO\PaymentResponse;
use Mecxer713\GoPay\DTO\PayoutBalanceResponse;
use Mecxer713\GoPay\DTO\PayoutTransferResponse;

/**
 * @method static PaymentResponse       initPayment(float $amount, string $devise, string $telephone, string $myref, ?string $usersId = null)
 * @method static PaymentResponse       checkPayment(string $ref)
 * @method static PayoutBalanceResponse getPayoutBalance()
 * @method static array                 getPayoutTransfers()
 * @method static PayoutTransferResponse sendPayoutTransfer(float $montant, string $devise, array $telephones, array $myrefs, ?string $dateDenvoi = null)
 * @method static PayoutTransferResponse getPayoutTransferStatus(string $transIdOrMyref)
 * @method static PayoutTransferResponse deletePayoutTransfer(string $transId)
 *
 * @see \Mecxer713\GoPay\GoPayService
 */
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
