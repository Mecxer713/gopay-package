<?php

declare(strict_types=1);

namespace Mecxer713\GoPay;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
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
            'timeout'  => 30.0,
        ]);
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
            $this->sendRequest('POST', '/api/v3/payment/init', $payload, 'payment')
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
            $this->sendRequest('GET', '/api/v3/payment/check/'.$ref, [], 'payment')
        );
    }

    /**
     * Récupère le solde de votre Wallet Payout.
     *
     * @throws GoPayException
     */
    public function getPayoutBalance(): PayoutBalanceResponse
    {
        return PayoutBalanceResponse::fromArray(
            $this->sendRequest('GET', '/api/payout/v3/balance', [], 'payout')
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
        return $this->sendRequest('GET', '/api/payout/v3/transfer', [], 'payout');
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
            $this->sendRequest('POST', '/api/payout/v3/transfer', $payload, 'payout')
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
            $this->sendRequest('GET', '/api/payout/v3/transfer/'.$transIdOrMyref, [], 'payout')
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
            $this->sendRequest('DELETE', '/api/payout/v3/transfer/'.$transId, [], 'payout')
        );
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Construit et envoie la requête HTTP à l'API GoPAY avec les en-têtes d'authentification.
     *
     * @param  string               $method   GET, POST ou DELETE
     * @param  string               $endpoint Le chemin de l'API (ex: '/api/v3/payment/init')
     * @param  array<string, mixed> $payload  Les paramètres de la requête
     * @param  string               $type     Le type d'API ('payment' ou 'payout')
     *
     * @return array<string, mixed>
     *
     * @throws GoPayException|ConfigurationException
     */
    protected function sendRequest(string $method, string $endpoint, array $payload, string $type): array
    {
        $apiKey    = $type === 'payment' ? $this->paymentApiKey : $this->payoutApiKey;
        $secretKey = $this->paymentSecretKey;

        if (empty($apiKey) || empty($secretKey)) {
            throw new ConfigurationException("Les clés API pour {$type} ne sont pas configurées.");
        }

        $nonce     = bin2hex(random_bytes(16));
        $timestamp = time();

        $options = [
            'headers' => $this->buildHeaders($apiKey, $endpoint, $method, $payload, $nonce, $timestamp, $secretKey),
        ];

        if ($method === 'POST') {
            $options['json'] = $payload;
        } elseif (in_array($method, ['GET', 'DELETE'], strict: true) && !empty($payload)) {
            $options['query'] = $payload;
        }

        try {
            $response = $this->client->request($method, $endpoint, $options);
            $content  = $response->getBody()->getContents();

            if (empty($content)) {
                return [];
            }

            $responseData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($responseData)) {
                return [];
            }

            // Erreur logique retournée avec HTTP 200 (success: false ou présence d'un error_code)
            if (isset($responseData['error_code']) || (isset($responseData['success']) && $responseData['success'] === false)) {
                throw new GoPayApiException(
                    $this->formatErrorMessage($responseData),
                    $response->getStatusCode(),
                    $responseData
                );
            }

            return $responseData;

        } catch (GuzzleException $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $httpResponse = $e->getResponse();
                if ($httpResponse !== null) {
                    try {
                        $body         = $httpResponse->getBody()->getContents();
                        $statusCode   = $httpResponse->getStatusCode();
                        $responseData = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

                        if (is_array($responseData)) {
                            throw new GoPayApiException(
                                $this->formatErrorMessage($responseData),
                                $statusCode,
                                $responseData,
                                $e
                            );
                        }
                    } catch (\JsonException) {
                        // Impossible de parser le body → on laisse lever GoPayException
                    }
                }
            }

            throw new GoPayException('Erreur de requête HTTP: '.$e->getMessage(), $e->getCode(), $e);

        } catch (\JsonException $e) {
            throw new GoPayException('Erreur de décodage JSON de la réponse: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Construit les headers HMAC pour une requête.
     *
     * @param  array<string, mixed> $payload
     * @return array<string, string>
     */
    private function buildHeaders(
        string $apiKey,
        string $endpoint,
        string $method,
        array $payload,
        string $nonce,
        int $timestamp,
        string $secretKey
    ): array {
        $signature = $this->buildSignature($endpoint, $method, $payload, $nonce, $timestamp, $secretKey);

        return [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'x-api-key'     => $apiKey,
            'x-signature'   => $signature,
            'x-timestamp'   => (string) $timestamp,
            'x-nonce'       => $nonce,
        ];
    }

    /**
     * Calcule la signature HMAC-SHA256 de la requête.
     *
     * Format du message : path + method + params + nonce + timestamp
     *
     * @param  array<string, mixed> $payload
     */
    private function buildSignature(
        string $endpoint,
        string $method,
        array $payload,
        string $nonce,
        int $timestamp,
        string $secretKey
    ): string {
        $path         = (string) parse_url($this->baseUrl.$endpoint, PHP_URL_PATH);
        $paramsString = empty($payload) ? '' : http_build_query($payload);
        $message      = $path.$method.$paramsString.$nonce.$timestamp;

        return hash_hmac('sha256', $message, $secretKey);
    }

    /**
     * Formate le message d'erreur à partir d'une réponse API.
     *
     * @param  array<string, mixed> $responseData
     */
    private function formatErrorMessage(array $responseData): string
    {
        $message   = $responseData['message'] ?? "Erreur inattendue de l'API GoPAY.";
        $errorCode = $responseData['error_code'] ?? null;

        return $errorCode !== null
            ? sprintf('[%s] %s', $errorCode, $message)
            : $message;
    }
}
