<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\DTO;

class PayoutBalanceResponse
{
    public function __construct(
        public readonly string $status,
        public readonly float $balance = 0.0,
        public readonly ?string $currency = null,
        public readonly ?string $message = null
    ) {}

    /**
     * @param  array<string, mixed>  $response
     */
    public static function fromArray(array $response): self
    {
        if (!isset($response['status'])) {
            throw new \InvalidArgumentException('Clé "status" manquante dans la réponse de l\'API.');
        }

        $data = $response['data'] ?? [];

        return new self(
            status: $response['status'],
            balance: (float) ($data['balance'] ?? 0.0),
            currency: $data['currency'] ?? null,
            message: $response['message'] ?? null
        );
    }
}
