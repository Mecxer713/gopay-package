<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mecxer713\GoPay\DTO\PayoutBalanceResponse;
use Mecxer713\GoPay\DTO\PayoutTransferResponse;
use Mecxer713\GoPay\GoPayService;

// Helper pour créer un GoPayService payout avec un mock HTTP
function makePayoutService(array $responses): GoPayService
{
    $mock   = new MockHandler($responses);
    $client = new Client(['handler' => HandlerStack::create($mock)]);

    return new GoPayService(
        'https://gopay.gooomart.com',
        '',
        'test_secret_key',
        'test_payout_api_key',
        $client
    );
}

it('can get payout balance', function () {
    $service = makePayoutService([
        new Response(200, [], (string) json_encode([
            'success' => true,
            'data'    => ['balance' => 5000, 'currency' => 'USD'],
        ])),
    ]);

    $response = $service->getPayoutBalance();

    expect($response)
        ->toBeInstanceOf(PayoutBalanceResponse::class)
        ->success->toBeTrue()
        ->isSuccessful()->toBeTrue()
        ->balance->toBe(5000.0)
        ->currency->toBe('USD');
});

it('can send a payout transfer', function () {
    $service = makePayoutService([
        new Response(200, [], (string) json_encode([
            'success'     => true,
            'message'     => 'Transfert initié.',
            'transaction' => [
                'status'   => 'EN ATTENTE',
                'trans_id' => 'PO123',
                'amount'   => '5',
                'currency' => 'USD',
            ],
        ])),
    ]);

    $response = $service->sendPayoutTransfer(5.0, 'USD', ['243999999999'], ['ref_po_123']);

    expect($response)
        ->toBeInstanceOf(PayoutTransferResponse::class)
        ->success->toBeTrue()
        ->transactionStatus->toBe('EN ATTENTE')
        ->transId->toBe('PO123')
        ->amount->toBe('5')
        ->currency->toBe('USD');
});

it('can get payout transfer status', function () {
    $service = makePayoutService([
        new Response(200, [], (string) json_encode([
            'success'     => true,
            'transaction' => [
                'status'   => 'TRAITÉE',
                'trans_id' => 'PO456',
            ],
        ])),
    ]);

    $response = $service->getPayoutTransferStatus('PO456');

    expect($response)
        ->toBeInstanceOf(PayoutTransferResponse::class)
        ->success->toBeTrue()
        ->transactionStatus->toBe('TRAITÉE')
        ->transId->toBe('PO456');
});
