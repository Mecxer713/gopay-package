<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Mecxer713\GoPay\Exception\ConfigurationException;
use Mecxer713\GoPay\Exception\GoPayException;
use Psr\Http\Message\ResponseInterface;

class GoPayClient
{
    /** Codes HTTP transitoires pour lesquels une requête idempotente (GET) est rejouée. */
    private const RETRYABLE_STATUS = [408, 429, 500, 502, 503, 504];

    private ClientInterface $client;

    /** @var callable(int): void */
    private $sleeper;

    /**
     * @param  int  $maxRetries  Nombre de nouvelles tentatives pour les requêtes GET en cas d'erreur transitoire.
     * @param  int  $retryBaseDelayMs  Délai de base (ms) du backoff exponentiel.
     * @param  callable(int): void|null  $sleeper  Fonction d'attente (en ms), injectable pour les tests.
     */
    public function __construct(
        private string $baseUrl,
        private string $paymentApiKey,
        private string $paymentSecretKey,
        private string $payoutApiKey,
        ?ClientInterface $client = null,
        private int $maxRetries = 2,
        private int $retryBaseDelayMs = 300,
        ?callable $sleeper = null
    ) {
        $this->baseUrl = rtrim($this->baseUrl, '/');
        $this->maxRetries = max(0, $this->maxRetries);
        $this->retryBaseDelayMs = max(0, $this->retryBaseDelayMs);
        $this->client = $client ?? new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30.0,
        ]);
        $this->sleeper = $sleeper ?? static function (int $ms): void {
            usleep($ms * 1000);
        };
    }

    /**
     * Construit et envoie la requête HTTP à l'API GoPAY.
     *
     * Seules les requêtes GET sont rejouées automatiquement (erreurs réseau ou HTTP 408/429/5xx) :
     * POST et DELETE ne le sont jamais afin d'éviter un double débit ou une double annulation.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws GoPayException|ConfigurationException
     */
    public function sendRequest(string $method, string $endpoint, array $payload, string $type): array
    {
        $apiKey = $type === 'payment' ? $this->paymentApiKey : $this->payoutApiKey;
        $secretKey = $this->paymentSecretKey;

        if ($apiKey === '' || $secretKey === '') {
            throw new ConfigurationException("Les clés API pour {$type} ne sont pas configurées.");
        }

        $attempts = $method === 'GET' ? $this->maxRetries + 1 : 1;

        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->attempt($method, $endpoint, $payload, $apiKey, $secretKey);
            } catch (RetryableRequestException $e) {
                if ($attempt >= $attempts) {
                    return $e->fallback();
                }
                ($this->sleeper)($this->backoffDelay($attempt));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws GoPayException|RetryableRequestException
     */
    private function attempt(string $method, string $endpoint, array $payload, string $apiKey, string $secretKey): array
    {
        // Nouveau nonce / timestamp à chaque tentative (protection anti-rejeu côté serveur).
        $nonce = bin2hex(random_bytes(16));
        $timestamp = time();

        $options = [
            'headers' => $this->buildHeaders($apiKey, $endpoint, $method, $payload, $nonce, $timestamp, $secretKey),
        ];

        if ($method === 'POST') {
            $options['json'] = $payload;
        } elseif (in_array($method, ['GET', 'DELETE'], strict: true) && $payload !== []) {
            $options['query'] = $payload;
        }

        try {
            $response = $this->client->request($method, $endpoint, $options);
        } catch (ConnectException $e) {
            throw new RetryableRequestException(
                fn () => throw new GoPayException('Erreur de requête HTTP: '.$e->getMessage(), (int) $e->getCode(), $e)
            );
        } catch (RequestException $e) {
            $errorResponse = $e->getResponse();
            if ($errorResponse === null) {
                throw new RetryableRequestException(
                    fn () => throw new GoPayException('Erreur de requête HTTP: '.$e->getMessage(), (int) $e->getCode(), $e)
                );
            }

            return $this->handleErrorResponse($errorResponse, $e);
        } catch (GuzzleException $e) {
            throw new GoPayException('Erreur de requête HTTP: '.$e->getMessage(), (int) $e->getCode(), $e);
        }

        // Clients configurés avec 'http_errors' => false : on applique la même logique.
        if ($response->getStatusCode() >= 400) {
            return $this->handleErrorResponse($response, null);
        }

        return $this->decode((string) $response->getBody());
    }

    /**
     * Les erreurs 4xx contenant un JSON sont renvoyées telles quelles (payload d'erreur GoPAY).
     * Les erreurs transitoires sont signalées comme rejouables ; le JSON éventuel sert de repli.
     *
     * @return array<string, mixed>
     *
     * @throws GoPayException|RetryableRequestException
     */
    private function handleErrorResponse(ResponseInterface $response, ?\Throwable $previous): array
    {
        $body = (string) $response->getBody();
        $status = $response->getStatusCode();

        $resolve = function () use ($body, $status, $previous): array {
            try {
                $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new GoPayException("Erreur de requête HTTP: GoPAY a répondu HTTP {$status}.", $status, $previous);
            }

            return $this->normalize($data);
        };

        if (in_array($status, self::RETRYABLE_STATUS, strict: true)) {
            throw new RetryableRequestException($resolve);
        }

        return $resolve();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws GoPayException
     */
    private function decode(string $content): array
    {
        if ($content === '') {
            return [];
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new GoPayException('Erreur de décodage JSON de la réponse: '.$e->getMessage(), $e->getCode(), $e);
        }

        return $this->normalize($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalize(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    private function backoffDelay(int $attempt): int
    {
        $base = $this->retryBaseDelayMs * (2 ** ($attempt - 1));

        return $base + ($this->retryBaseDelayMs > 0 ? random_int(0, $this->retryBaseDelayMs) : 0);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function buildHeaders(string $apiKey, string $endpoint, string $method, array $payload, string $nonce, int $timestamp, string $secretKey): array
    {
        $signature = $this->buildSignature($endpoint, $method, $payload, $nonce, $timestamp, $secretKey);

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-api-key' => $apiKey,
            'x-signature' => $signature,
            'x-timestamp' => (string) $timestamp,
            'x-nonce' => $nonce,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function buildSignature(string $endpoint, string $method, array $payload, string $nonce, int $timestamp, string $secretKey): string
    {
        $path = (string) parse_url($this->baseUrl.$endpoint, PHP_URL_PATH);
        $paramsString = $payload === [] ? '' : http_build_query($payload);
        $message = $path.$method.$paramsString.$nonce.$timestamp;

        return hash_hmac('sha256', $message, $secretKey);
    }
}
