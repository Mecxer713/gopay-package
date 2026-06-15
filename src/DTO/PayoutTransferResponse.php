<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\DTO;

class PayoutTransferResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        /** Résultat booléen retourné par la clé "success" de la réponse racine. */
        public readonly bool $success,
        /** Statut textuel de la transaction (ex: "EN ATTENTE", "TRAITÉE") — depuis transaction.status. */
        public readonly ?string $transactionStatus = null,
        public readonly ?string $transId = null,
        public readonly ?string $state = null,
        public readonly ?string $message = null,
        public readonly ?string $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $source = null,
        public readonly ?string $date = null,
        public readonly array $raw = []
    ) {}

    /**
     * Indique si le transfert a été initié avec succès.
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

        // Support des clés "data", "transaction" ou à la racine
        $tx = $response['transaction'] ?? $response['data'] ?? [];

        return new self(
            success: (bool) ($response['success'] ?? ($response['status'] === 'success')),
            transactionStatus: $tx['status'] ?? null,
            transId: $tx['trans_id'] ?? null,
            state: $tx['state'] ?? null,
            message: $response['message'] ?? null,
            amount: isset($tx['amount']) ? (string) $tx['amount'] : null,
            currency: $tx['currency'] ?? null,
            source: $tx['source'] ?? null,
            date: $tx['date'] ?? null,
            raw: $response
        );
    }
}
