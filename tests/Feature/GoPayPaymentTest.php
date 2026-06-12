<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mecxer713\GoPay\DTO\PaymentResponse;
use Mecxer713\GoPay\GoPayService;

it('can initialize a payment', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['status' => 'success', 'data' => ['trans_id' => '12345', 'url' => 'http://example.com']]) ?: ''),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $service = new GoPayService(
        'https://gopay.gooomart.com',
        'test_api_key',
        'test_secret_key',
        'test_payout_api_key',
        'test_payout_secret_key',
        $client
    );

    $response = $service->initPayment(100.0, 'USD', '243999999999', 'ref123');

    expect($response)
        ->toBeInstanceOf(PaymentResponse::class)
        ->status->toBe('success')
        ->transId->toBe('12345');
});

it('can check a payment status', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['status' => 'success', 'data' => ['state' => 'COMPLETED']]) ?: ''),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $service = new GoPayService(
        'https://gopay.gooomart.com',
        'test_api_key',
        'test_secret_key',
        'test_payout_api_key',
        'test_payout_secret_key',
        $client
    );

    $response = $service->checkPayment('ref123');

    expect($response)
        ->toBeInstanceOf(PaymentResponse::class)
        ->status->toBe('success')
        ->state->toBe('COMPLETED');
});
