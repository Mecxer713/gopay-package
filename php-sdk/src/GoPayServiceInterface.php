<?php

declare(strict_types=1);

namespace Mecxer713\GoPay;

use Mecxer713\GoPay\DTO\PaymentResponse;
use Mecxer713\GoPay\DTO\PayoutBalanceResponse;
use Mecxer713\GoPay\DTO\PayoutTransferResponse;

interface GoPayServiceInterface
{
    public function initPayment(float $amount, string $devise, string $telephone, string $myref, ?string $usersId = null): PaymentResponse;

    public function checkPayment(string $ref): PaymentResponse;

    public function getPayoutBalance(): PayoutBalanceResponse;

    /**
     * @return array<string, mixed>
     */
    public function getPayoutTransfers(): array;

    public function sendPayoutTransfer(float $montant, string $devise, array $telephones, array $myrefs, ?string $dateDenvoi = null): PayoutTransferResponse;

    public function getPayoutTransferStatus(string $transIdOrMyref): PayoutTransferResponse;

    public function deletePayoutTransfer(string $transId): PayoutTransferResponse;
}
