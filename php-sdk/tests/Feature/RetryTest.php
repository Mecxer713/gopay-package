<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mecxer713\GoPay\Exception\GoPayException;
use Mecxer713\GoPay\GoPayService;

it('retries GET requests on 503 and succeeds', function () {
    $mock = new MockHandler([
        new Response(503, [], 'Service Unavailable'),
        new Response(200, [], (string) json_encode([
            'success' => true,
            'transaction' => ['status' => 'success', 'trans_id' => 'T1'],
        ])),
    ]);

    $client = new Client(['handler' => HandlerStack::create($mock)]);
    $service = new GoPayService(
        'https://gopay.gooomart.com',
        'test_api_key',
        'test_secret_key',
        'test_payout_api_key',
        $client,
        maxRetries: 2,
        retryBaseDelayMs: 0
    );

    $response = $service->checkPayment('REF-123');

    expect($response->success)->toBeTrue()
        ->and($response->transId)->toBe('T1')
        ->and($mock->count())->toBe(0); // All 2 responses were consumed
});

it('does not retry POST requests on failure', function () {
    $mock = new MockHandler([
        new Response(500, [], 'Internal Server Error'),
        new Response(200, [], '{"success":true}'),
    ]);

    $client = new Client(['handler' => HandlerStack::create($mock)]);
    $service = new GoPayService(
        'https://gopay.gooomart.com',
        'test_api_key',
        'test_secret_key',
        'test_payout_api_key',
        $client,
        maxRetries: 2,
        retryBaseDelayMs: 0
    );

    expect(fn () => $service->initPayment(100.0, 'CDF', '+24399000000', 'ref-001'))
        ->toThrow(GoPayException::class);

    // Only 1 request was made (POST is non-idempotent)
    expect($mock->count())->toBe(1);
});

