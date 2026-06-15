<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\DTO;

class PaymentResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly bool|string|null $status,
        public readonly ?string $transId = null,
        public readonly ?string $url = null,
        public readonly ?string $state = null,
        public readonly ?string $message = null,
        public readonly array $data = []
    ) {}

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
            status: $response['success'] ?? $response['status'] ?? null,
            transId: $data['trans_id'] ?? null,
            url: $data['url'] ?? null,
            state: $data['state'] ?? null,
            message: $response['message'] ?? null,
            data: $data
        );
    }
}
