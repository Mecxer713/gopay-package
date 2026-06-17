<?php

declare(strict_types=1);

namespace Mecxer713\GoPay;

use GuzzleHttp\ClientInterface;
use Mecxer713\GoPay\DTO\PaymentResponse;
use Mecxer713\GoPay\DTO\PayoutBalanceResponse;
use Mecxer713\GoPay\DTO\PayoutTransferResponse;
use Mecxer713\GoPay\Exception\GoPayException;
use Mecxer713\GoPay\Http\GoPayClient;
use Mecxer713\GoPay\Services\PaymentService;
use Mecxer713\GoPay\Services\PayoutService;

class GoPayService implements GoPayServiceInterface
{
    protected GoPayClient $client;
    protected PaymentService $paymentService;
    protected PayoutService $payoutService;

    public function __construct(
        protected string $baseUrl = 'https://gopay.gooomart.com',
        protected string $paymentApiKey = '',
        protected string $paymentSecretKey = '',
        protected string $payoutApiKey = '',
        ?ClientInterface $guzzleClient = null
    ) {
        $this->client = new GoPayClient(
            $this->baseUrl,
            $this->paymentApiKey,
            $this->paymentSecretKey,
            $this->payoutApiKey,
            $guzzleClient
        );
        
        $this->paymentService = new PaymentService($this->client);
        $this->payoutService = new PayoutService($this->client);
    }

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
        return $this->paymentService->initPayment($amount, $devise, $telephone, $myref, $usersId);
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
        return $this->paymentService->checkPayment($ref);
    }

    /**
     * Récupère le solde de votre Wallet Payout.
     *
     * @throws GoPayException
     */
    public function getPayoutBalance(): PayoutBalanceResponse
    {
        return $this->payoutService->getPayoutBalance();
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
        return $this->payoutService->getPayoutTransfers();
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
        return $this->payoutService->sendPayoutTransfer($montant, $devise, $telephones, $myrefs, $dateDenvoi);
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
        return $this->payoutService->getPayoutTransferStatus($transIdOrMyref);
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
        return $this->payoutService->deletePayoutTransfer($transId);
    }
    
    /**
     * Accès direct au service de paiement
     */
    public function payment(): PaymentService
    {
        return $this->paymentService;
    }

    /**
     * Accès direct au service de reversement
     */
    public function payout(): PayoutService
    {
        return $this->payoutService;
    }
}
