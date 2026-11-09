<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Webhook;

class GoPayWebhook
{
    /**
     * Vérifie la signature HMAC-SHA256 d'une notification webhook reçue de GoPAY.
     *
     * @param  string  $payload  Le corps brut de la requête (raw body)
     * @param  string  $signature  La signature reçue (ex: header x-signature ou paramètre signature)
     * @param  string  $secretKey  Le secret marchand utilisé pour signer
     */
    public static function verify(string $payload, string $signature, string $secretKey): bool
    {
        if ($signature === '' || $secretKey === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secretKey);

        return hash_equals($expected, strtolower($signature));
    }
}

