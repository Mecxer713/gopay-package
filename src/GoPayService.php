<?php

declare(strict_types=1);

namespace Mecxer713\GoPay;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Mecxer713\GoPay\DTO\PaymentResponse;
use Mecxer713\GoPay\DTO\PayoutBalanceResponse;
use Mecxer713\GoPay\DTO\PayoutTransferResponse;
use Mecxer713\GoPay\Exception\ConfigurationException;
use Mecxer713\GoPay\Exception\GoPayApiException;
use Mecxer713\GoPay\Exception\GoPayException;

class GoPayService implements GoPayServiceInterface
{
    protected ClientInterface $client;

    public function __construct(
        protected string $baseUrl = 'https://gopay.gooomart.com',
        protected string $paymentApiKey = '',
        protected string $paymentSecretKey = '',
        protected string $payoutApiKey = '',
        ?ClientInterface $client = null
    ) {
        $this->baseUrl = rtrim($this->baseUrl, '/');
        $this->client = $client ?? new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30.0,
        ]);
    }

    /**
     * Initie une demande de paiement (Payment Init).
     *
     * @param  float  $amount  Le montant du paiement
     * @param  string  $devise  La devise (ex: USD, CDF)
     * @param  string  $telephone  Le numéro de téléphone du client
     * @param  string  $myref  La référence interne de la transaction
     * @param  string|null  $usersId  Optionnel. ID de l'utilisateur pour lier la transaction.
     *
     * @throws GoPayException
     */
    public function initPayment(float $amount, string $devise, string $telephone, string $myref, ?string $usersId = null): PaymentResponse
    {
        $endpoint = '/api/v3/payment/init';
        $payload = [
            'amount' => $amount,
            'devise' => $devise,
            'telephone' => $telephone,
            'myref' => $myref,
        ];

        if ($usersId !== null) {
            $payload['users_id'] = $usersId;
        }

        return PaymentResponse::fromArray($this->sendRequest('POST', $endpoint, $payload, 'payment'));
    }

    /**
     * Vérifie l'état de la transaction de paiement.
     *
     * @param  string  $ref  La référence de la transaction
     *
     * @throws GoPayException
     */
    public function checkPayment(string $ref): PaymentResponse
    {
        $endpoint = '/api/v3/payment/check/'.$ref;

        return PaymentResponse::fromArray($this->sendRequest('GET', $endpoint, [], 'payment'));
    }

    /**
     * Récupère le solde de votre Wallet Payout.
     *
     * @throws GoPayException
     */
    public function getPayoutBalance(): PayoutBalanceResponse
    {
        $endpoint = '/api/payout/v3/balance';

        return PayoutBalanceResponse::fromArray($this->sendRequest('GET', $endpoint, [], 'payout'));
    }

    /**
     * Affiche la liste de vos transferts d'argent (Payouts).
     *
     * @throws GoPayException
     */
    public function getPayoutTransfers(): array
    {
        $endpoint = '/api/payout/v3/transfer';

        return $this->sendRequest('GET', $endpoint, [], 'payout');
    }

    /**
     * Permet d'envoyer l'argent à une liste de comptes mobile money.
     *
     * @param  float  $montant  Le montant (minimum 500 CDF ou 0.5 USD)
     * @param  string  $devise  La devise (CDF|USD)
     * @param  array  $telephones  Tableau de numéros (ex. ['0991234567','0811234567'])
     * @param  array  $myrefs  Tableau de références de transaction
     * @param  string|null  $dateDenvoi  Optionnel, planifie l’envoi à une date précise (Y/m/d H:i)
     *
     * @throws GoPayException
     */
    public function sendPayoutTransfer(float $montant, string $devise, array $telephones, array $myrefs, ?string $dateDenvoi = null): PayoutTransferResponse
    {
        $endpoint = '/api/payout/v3/transfer';
        $payload = [
            'montant' => $montant,
            'devise' => $devise,
            'telephone' => $telephones,
            'myref' => $myrefs,
        ];

        if ($dateDenvoi !== null) {
            $payload['date_denvoi'] = $dateDenvoi;
        }

        return PayoutTransferResponse::fromArray($this->sendRequest('POST', $endpoint, $payload, 'payout'));
    }

    /**
     * Affiche le statut d'un transfert d'argent (Payout).
     *
     * @param  string  $transIdOrMyref  L'identifiant de la transaction (TRANS_ID ou myref)
     *
     * @throws GoPayException
     */
    public function getPayoutTransferStatus(string $transIdOrMyref): PayoutTransferResponse
    {
        $endpoint = '/api/payout/v3/transfer/'.$transIdOrMyref;

        return PayoutTransferResponse::fromArray($this->sendRequest('GET', $endpoint, [], 'payout'));
    }

    /**
     * Supprime une transaction (Seules les transactions 'EN ATTENTE' peuvent être supprimées).
     *
     * @param  string  $transId  L'identifiant de la transaction
     *
     * @throws GoPayException
     */
    public function deletePayoutTransfer(string $transId): PayoutTransferResponse
    {
        $endpoint = '/api/payout/v3/transfer/'.$transId;

        return PayoutTransferResponse::fromArray($this->sendRequest('DELETE', $endpoint, [], 'payout'));
    }

    /**
     * Construit et envoie la requête HTTP à l'API GoPAY avec les en-têtes d'authentification.
     *
     * @param  string  $method  GET, POST ou DELETE
     * @param  string  $endpoint  Le chemin de l'API (ex: '/api/v3/payment/init')
     * @param  array  $payload  Les paramètres de la requête
     * @param  string  $type  Le type d'API ('payment' ou 'payout')
     *
     * @throws GoPayException|ConfigurationException
     */
    protected function sendRequest(string $method, string $endpoint, array $payload, string $type): array
    {
        $apiKey = $type === 'payment' ? $this->paymentApiKey : $this->payoutApiKey;
        $secretKey = $this->paymentSecretKey;

        if (empty($apiKey) || empty($secretKey)) {
            throw new ConfigurationException("Les clés API pour {$type} ne sont pas configurées.");
        }

        $nonce = bin2hex(random_bytes(16));
        $timestamp = time();

        // Récupérer uniquement le path pour la signature
        $path = parse_url($this->baseUrl . $endpoint, PHP_URL_PATH);
        $paramsString = empty($payload) ? '' : http_build_query($payload);

        // String à signer: endpoint . methode . params . nonce . timestamp
        $message = $path . $method . $paramsString . $nonce . $timestamp;

        $signature = hash_hmac('sha256', $message, $secretKey);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-api-key' => $apiKey,
            'x-signature' => $signature,
            'x-timestamp' => (string) $timestamp,
            'x-nonce' => $nonce,
        ];

        $options = [
            'headers' => $headers,
        ];

        if ($method === 'POST') {
            $options['json'] = $payload;
        } elseif (in_array($method, ['GET', 'DELETE']) && !empty($payload)) {
            $options['query'] = $payload;
        }

        try {
            $response = $this->client->request($method, $endpoint, $options);
            $content = $response->getBody()->getContents();

            if (empty($content)) {
                return [];
            }

            $responseData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            
            // Check if response contains an error even on HTTP 200
            if (is_array($responseData) && (isset($responseData['error_code']) || (isset($responseData['success']) && $responseData['success'] === false))) {
                $errorMessage = $responseData['message'] ?? 'Erreur inattendue de l\'API GoPAY.';
                $errorCode = $responseData['error_code'] ?? null;
                if ($errorCode !== null) {
                    $errorMessage = sprintf('[%s] %s', $errorCode, $errorMessage);
                }
                throw new GoPayApiException($errorMessage, $response->getStatusCode(), $responseData);
            }

            return is_array($responseData) ? $responseData : [];
        } catch (GuzzleException $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                try {
                    $response = $e->getResponse();
                    if ($response) {
                        $body = $response->getBody()->getContents();
                        $statusCode = $response->getStatusCode();
                        $responseData = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                        
                        if (is_array($responseData)) {
                            $errorMessage = $responseData['message'] ?? 'Erreur lors de la requête API.';
                            $errorCode = $responseData['error_code'] ?? null;
                            if ($errorCode !== null) {
                                $errorMessage = sprintf('[%s] %s', $errorCode, $errorMessage);
                            }
                            
                            throw new GoPayApiException($errorMessage, $statusCode, $responseData, $e);
                        }
                    }
                } catch (\JsonException $jsonException) {
                    // Si on n'arrive pas à parser l'erreur en JSON, on laisse passer pour lancer GoPayException
                }
            }
            throw new GoPayException("Erreur de requête HTTP: " . $e->getMessage(), $e->getCode(), $e);
        } catch (\JsonException $e) {
            throw new GoPayException("Erreur de décodage JSON de la réponse: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
