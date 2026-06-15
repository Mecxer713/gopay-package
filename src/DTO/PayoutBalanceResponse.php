<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\DTO;

class PayoutBalanceResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        /** Résultat booléen retourné par la clé "success" de la réponse racine. */
        public readonly bool $success,
        public readonly float $balance = 0.0,
        public readonly ?string $currency = null,
        public readonly ?string $message = null,
        public readonly array $raw = []
    ) {}

    /**
     * Indique si la récupération du solde a réussi.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public static function fromArray(array $response): self
    {
        if (!isset($response['success']) && !isset($response['status'])) {
            throw new \InvalidArgumentException('Clé "success" ou "status" manquante dans la réponse de l\'API.');
        }

        $data = $response['data'] ?? $response['transaction'] ?? [];

        return new self(
            success: (bool) ($response['success'] ?? ($response['status'] === 'success')),
            balance: (float) ($data['balance'] ?? 0.0),
            currency: $data['currency'] ?? null,
            message: $response['message'] ?? null,
            raw: $response
        );
    }
}
