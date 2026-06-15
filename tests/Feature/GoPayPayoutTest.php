<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mecxer713\GoPay\DTO\PayoutBalanceResponse;
use Mecxer713\GoPay\DTO\PayoutTransferResponse;
use Mecxer713\GoPay\GoPayService;

it('can get payout balance', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['status' => 'success', 'data' => ['balance' => 5000, 'currency' => 'USD']]) ?: ''),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $service = new GoPayService(
        'https://gopay.gooomart.com',
        '', 'test_secret_key', 'test_payout_api_key',
        $client
    );

    $response = $service->getPayoutBalance();

    expect($response)
        ->toBeInstanceOf(PayoutBalanceResponse::class)
        ->status->toBe('success')
        ->balance->toBe(5000.0);
});

it('can send a payout transfer', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['status' => 'success', 'data' => ['trans_id' => 'PO123']]) ?: ''),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $service = new GoPayService(
        'https://gopay.gooomart.com',
        '', 'test_secret_key', 'test_payout_api_key',
        $client
    );

    $response = $service->sendPayoutTransfer(5.0, 'USD', ['243999999999'], ['ref_po_123']);

    expect($response)
        ->toBeInstanceOf(PayoutTransferResponse::class)
        ->status->toBe('success')
        ->transId->toBe('PO123');
});
