<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Exception;

use Mecxer713\GoPay\Enums\GoPayErrorCode;
use Throwable;

class GoPayApiException extends GoPayException
{
    /**
     * @param string                 $message
     * @param int                    $code         HTTP Status Code
     * @param array<string, mixed>   $responseData Decoded JSON response body
     * @param Throwable|null         $previous
     */
    public function __construct(
        string $message,
        int $code = 0,
        public readonly array $responseData = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Retourne les données brutes de la réponse JSON de l'API.
     *
     * @return array<string, mixed>
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * Retourne le code d'erreur brut de l'API (ex: "ERR_APIKEY_MISSING"), ou null si absent.
     */
    public function getErrorCode(): ?string
    {
        return $this->responseData['error_code'] ?? null;
    }

    /**
     * Retourne l'enum GoPayErrorCode si le code correspond à un cas connu, sinon null.
     */
    public function getErrorCodeEnum(): ?GoPayErrorCode
    {
        $code = $this->getErrorCode();

        if ($code === null) {
            return null;
        }

        return GoPayErrorCode::tryFrom($code);
    }

    /**
     * Vérifie si l'erreur correspond à un code d'erreur spécifique.
     */
    public function isErrorCode(GoPayErrorCode $errorCode): bool
    {
        return $this->getErrorCode() === $errorCode->value;
    }
}
