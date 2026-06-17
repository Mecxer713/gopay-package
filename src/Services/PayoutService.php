<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Services;

use Mecxer713\GoPay\DTO\PayoutBalanceResponse;
use Mecxer713\GoPay\DTO\PayoutTransferResponse;
use Mecxer713\GoPay\Exception\GoPayException;
use Mecxer713\GoPay\Http\GoPayClient;

class PayoutService
{
    public function __construct(private GoPayClient $client) {}

    /**
     * Récupère le solde de votre Wallet Payout.
     *
     * @throws GoPayException
     */
    public function getPayoutBalance(): PayoutBalanceResponse
    {
        return PayoutBalanceResponse::fromArray(
            $this->client->sendRequest('GET', '/api/payout/v3/balance', [], 'payout')
        );
    }

    /**
     * Affiche la liste de vos transferts d'argent (Payouts).
     *
     * @return array<string, mixed>
     *
     * @throws GoPayException
     */
    public function getPayoutTransfers(): array
    {
        return $this->client->sendRequest('GET', '/api/payout/v3/transfer', [], 'payout');
    }

    /**
     * Permet d'envoyer l'argent à une liste de comptes mobile money.
     *
     * @param  float       $montant     Le montant (minimum 500 CDF ou 0.5 USD)
     * @param  string      $devise      La devise (CDF|USD)
     * @param  array       $telephones  Tableau de numéros (ex. ['0991234567','0811234567'])
     * @param  array       $myrefs      Tableau de références de transaction
     * @param  string|null $dateDenvoi  Optionnel, planifie l'envoi à une date précise (Y/m/d H:i)
     *
     * @throws GoPayException
     */
    public function sendPayoutTransfer(float $montant, string $devise, array $telephones, array $myrefs, ?string $dateDenvoi = null): PayoutTransferResponse
    {
        $payload = [
            'montant'   => $montant,
            'devise'    => $devise,
            'telephone' => $telephones,
            'myref'     => $myrefs,
        ];

        if ($dateDenvoi !== null) {
            $payload['date_denvoi'] = $dateDenvoi;
        }

        return PayoutTransferResponse::fromArray(
            $this->client->sendRequest('POST', '/api/payout/v3/transfer', $payload, 'payout')
        );
    }

    /**
     * Affiche le statut d'un transfert d'argent (Payout).
     *
     * @param  string $transIdOrMyref L'identifiant de la transaction (TRANS_ID ou myref)
     *
     * @throws GoPayException
     */
    public function getPayoutTransferStatus(string $transIdOrMyref): PayoutTransferResponse
    {
        return PayoutTransferResponse::fromArray(
            $this->client->sendRequest('GET', '/api/payout/v3/transfer/'.$transIdOrMyref, [], 'payout')
        );
    }

    /**
     * Supprime une transaction (Seules les transactions 'EN ATTENTE' peuvent être supprimées).
     *
     * @param  string $transId L'identifiant de la transaction
     *
     * @throws GoPayException
     */
    public function deletePayoutTransfer(string $transId): PayoutTransferResponse
    {
        return PayoutTransferResponse::fromArray(
            $this->client->sendRequest('DELETE', '/api/payout/v3/transfer/'.$transId, [], 'payout')
        );
    }
}
