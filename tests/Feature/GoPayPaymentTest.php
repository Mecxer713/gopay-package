<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mecxer713\GoPay\DTO\PaymentResponse;
use Mecxer713\GoPay\Exception\GoPayApiException;
use Mecxer713\GoPay\Enums\GoPayErrorCode;
use Mecxer713\GoPay\GoPayService;

// Helper pour créer un GoPayService avec un mock HTTP
function makePaymentService(array $responses): GoPayService
{
    $mock    = new MockHandler($responses);
    $client  = new Client(['handler' => HandlerStack::create($mock)]);

    return new GoPayService(
        'https://gopay.gooomart.com',
        'test_api_key',
        'test_secret_key',
        'test_payout_api_key',
        $client
    );
}

it('can initialize a payment (success key)', function () {
    $service = makePaymentService([
        new Response(200, [], (string) json_encode([
            'success'     => true,
            'message'     => 'Votre transaction est effectuée avec succès.',
            'transaction' => [
                'status'   => 'success',
                'amount'   => '4000',
                'currency' => 'CDF',
                'trans_id' => 'TRANS-001.94786.53389',
                'source'   => 'API',
                'date'     => '2023-08-06T17:18:17.755517Z',
            ],
        ])),
    ]);

    $response = $service->initPayment(4000.0, 'CDF', '+24399000000', 'ref-001');

    expect($response)
        ->toBeInstanceOf(PaymentResponse::class)
        ->success->toBeTrue()
        ->isSuccessful()->toBeTrue()
        ->transactionStatus->toBe('success')
        ->transId->toBe('TRANS-001.94786.53389')
        ->amount->toBe('4000')
        ->currency->toBe('CDF')
        ->source->toBe('API')
        ->message->toBe('Votre transaction est effectuée avec succès.');
});

it('can initialize a payment (legacy status key)', function () {
    $service = makePaymentService([
        new Response(200, [], (string) json_encode([
            'status'  => 'success',
            'data'    => ['trans_id' => '12345', 'url' => 'http://example.com'],
        ])),
    ]);

    $response = $service->initPayment(100.0, 'USD', '243999999999', 'ref123');

    expect($response)
        ->toBeInstanceOf(PaymentResponse::class)
        ->success->toBeTrue()
        ->transId->toBe('12345');
});

it('can check a payment status', function () {
    $service = makePaymentService([
        new Response(200, [], (string) json_encode([
            'success'     => true,
            'transaction' => ['status' => 'COMPLETED', 'trans_id' => 'T999'],
        ])),
    ]);

    $response = $service->checkPayment('ref123');

    expect($response)
        ->toBeInstanceOf(PaymentResponse::class)
        ->success->toBeTrue()
        ->transactionStatus->toBe('COMPLETED')
        ->transId->toBe('T999');
});

it('throws GoPayApiException with error code on API error', function () {
    $service = makePaymentService([
        new Response(200, [], (string) json_encode([
            'success'    => false,
            'error_code' => 'ERR_NO_PAYMENT_FOUND',
            'message'    => 'Aucune transaction correspondante.',
        ])),
    ]);

    $service->checkPayment('invalid-ref');
})->throws(GoPayApiException::class, '[ERR_NO_PAYMENT_FOUND]');

it('GoPayApiException provides error code helpers', function () {
    $service = makePaymentService([
        new Response(400, [], (string) json_encode([
            'success'    => false,
            'error_code' => 'ERR_APIKEY_INVALID',
            'message'    => 'La clé API est invalide.',
        ])),
    ]);

    try {
        $service->initPayment(100, 'CDF', '0999', 'ref');
    } catch (GoPayApiException $e) {
        expect($e->getErrorCode())->toBe('ERR_APIKEY_INVALID');
        expect($e->getErrorCodeEnum())->toBe(GoPayErrorCode::APIKEY_INVALID);
        expect($e->isErrorCode(GoPayErrorCode::APIKEY_INVALID))->toBeTrue();
        expect($e->isErrorCode(GoPayErrorCode::APIKEY_MISSING))->toBeFalse();
    }
});
