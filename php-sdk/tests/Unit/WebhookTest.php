<?php

declare(strict_types=1);

use Mecxer713\GoPay\Webhook\GoPayWebhook;

it('verifies valid webhook signature', function () {
    $payload = '{"event":"payment.success","ref":"REF-123"}';
    $secret = 'test_secret_key';
    $signature = hash_hmac('sha256', $payload, $secret);

    expect(GoPayWebhook::verify($payload, $signature, $secret))->toBeTrue();
});

it('rejects invalid or empty webhook signatures', function () {
    $payload = '{"event":"payment.success"}';
    $secret = 'test_secret_key';

    expect(GoPayWebhook::verify($payload, 'invalid_sig', $secret))->toBeFalse();
    expect(GoPayWebhook::verify($payload, '', $secret))->toBeFalse();
    expect(GoPayWebhook::verify($payload, 'some_sig', ''))->toBeFalse();
});

