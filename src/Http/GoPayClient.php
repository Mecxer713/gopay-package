<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Mecxer713\GoPay\Exception\ConfigurationException;
use Mecxer713\GoPay\Exception\GoPayApiException;
use Mecxer713\GoPay\Exception\GoPayException;

class GoPayClient
{
    private ClientInterface $client;

    public function __construct(
        private string $baseUrl,
        private string $paymentApiKey,
        private string $paymentSecretKey,
        private string $payoutApiKey,
        ?ClientInterface $client = null
    ) {
        $this->baseUrl = rtrim($this->baseUrl, '/');
        $this->client = $client ?? new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30.0,
        ]);
    }

    /**
     * Construit et envoie la requête HTTP à l'API GoPAY.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     * @throws GoPayException|ConfigurationException
     */
    public function sendRequest(string $method, string $endpoint, array $payload, string $type): array
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
                try {
                    $response = $e->getResponse();
                    if ($response) {
                        $body         = $response->getBody()->getContents();
                        $responseData = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

                        return is_array($responseData) ? $responseData : [];
                    }
                } catch (\JsonException $jsonException) {
                    // Ignorer et jeter GoPayException plus bas
                }
            }

            throw new GoPayException("Erreur de requête HTTP: " . $e->getMessage(), $e->getCode(), $e);
        } catch (\JsonException $e) {
            throw new GoPayException("Erreur de décodage JSON de la réponse: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, string>
     */
    private function buildHeaders(string $apiKey, string $endpoint, string $method, array $payload, string $nonce, int $timestamp, string $secretKey): array
    {
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
     * @param array<string, mixed> $payload
     */
    private function buildSignature(string $endpoint, string $method, array $payload, string $nonce, int $timestamp, string $secretKey): string
    {
        $path         = (string) parse_url($this->baseUrl.$endpoint, PHP_URL_PATH);
        $paramsString = empty($payload) ? '' : http_build_query($payload);
        $message      = $path.$method.$paramsString.$nonce.$timestamp;

        return hash_hmac('sha256', $message, $secretKey);
    }

    /**
     * @param array<string, mixed> $responseData
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
