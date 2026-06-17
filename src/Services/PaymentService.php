<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Services;

use Mecxer713\GoPay\DTO\PaymentResponse;
use Mecxer713\GoPay\Exception\GoPayException;
use Mecxer713\GoPay\Http\GoPayClient;

class PaymentService
{
    public function __construct(private GoPayClient $client) {}

    /**
     * Initie une demande de paiement (Payment Init).
     *
     * @param  float       $amount    Le montant du paiement
     * @param  string      $devise    La devise (ex: USD, CDF)
     * @param  string      $telephone Le numéro de téléphone du client
     * @param  string      $myref     La référence interne de la transaction
     * @param  string|null $usersId   Optionnel. ID de l'utilisateur pour lier la transaction.
     *
     * @throws GoPayException
     */
    public function initPayment(float $amount, string $devise, string $telephone, string $myref, ?string $usersId = null): PaymentResponse
    {
        $payload = [
            'amount'    => $amount,
            'devise'    => $devise,
            'telephone' => $telephone,
            'myref'     => $myref,
        ];

        if ($usersId !== null) {
            $payload['users_id'] = $usersId;
        }

        return PaymentResponse::fromArray(
            $this->client->sendRequest('POST', '/api/v3/payment/init', $payload, 'payment')
        );
    }

    /**
     * Vérifie l'état de la transaction de paiement.
     *
     * @param  string $ref La référence de la transaction
     *
     * @throws GoPayException
     */
    public function checkPayment(string $ref): PaymentResponse
    {
        return PaymentResponse::fromArray(
            $this->client->sendRequest('GET', '/api/v3/payment/check/'.$ref, [], 'payment')
        );
    }
}
