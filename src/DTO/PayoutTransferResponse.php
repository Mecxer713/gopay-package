<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\DTO;

class PayoutTransferResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $status,
        public readonly ?string $transId = null,
        public readonly ?string $state = null,
        public readonly ?string $message = null,
        public readonly array $data = []
    ) {}

    /**
     * @param  array<string, mixed>  $response
     */
    public static function fromArray(array $response): self
    {
        $data = $response['data'] ?? [];

        // Sometimes the transfer ID might be nested differently, depending on the API
        return new self(
            status: $response['status'] ?? 'unknown',
            transId: $data['trans_id'] ?? null,
            state: $data['state'] ?? null,
            message: $response['message'] ?? null,
            data: $data
        );
    }
}
