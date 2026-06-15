<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Exception;

use Throwable;

class GoPayApiException extends GoPayException
{
    /**
     * @param string $message
     * @param int $code HTTP Status Code
     * @param array<string, mixed> $responseData Decoded JSON response body
     * @param Throwable|null $previous
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
     * @return array<string, mixed>
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }
}
