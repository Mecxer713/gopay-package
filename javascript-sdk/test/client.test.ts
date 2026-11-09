import { describe, it } from 'node:test';
import assert from 'node:assert';
import { phpUrlEncode, buildQueryString, computeSignature, generateNonce } from '../src/hmac';
import { GoPayClient } from '../src/client';
import { GoPayConfigurationError, GoPayError } from '../src/errors';

describe('GoPay HMAC and Query String Formatting', () => {
  it('phpUrlEncode handles spaces, unreserved characters and symbols', () => {
    assert.strictEqual(phpUrlEncode('hello world'), 'hello+world');
    assert.strictEqual(phpUrlEncode('+24399000000'), '%2B24399000000');
    assert.strictEqual(phpUrlEncode('order_123-test.ok'), 'order_123-test.ok');
  });

  it('buildQueryString formats single values and arrays according to PHP RFC1738', () => {
    const query = buildQueryString({
      amount: 4000,
      devise: 'CDF',
      telephone: '+24399000000',
      myref: 'REF 001',
    });
    assert.strictEqual(query, 'amount=4000&devise=CDF&telephone=%2B24399000000&myref=REF+001');

    const arrayQuery = buildQueryString({
      telephones: ['+2431', '+2432'],
    });
    assert.strictEqual(arrayQuery, 'telephones%5B0%5D=%2B2431&telephones%5B1%5D=%2B2432');
  });

  it('computeSignature produces consistent HMAC-SHA256 digest', () => {
    const signature = computeSignature(
      '/api/v3/payment/init',
      'POST',
      'amount=4000&devise=CDF',
      'test_nonce',
      '1234567890',
      'my_secret_key'
    );
    assert.ok(/^[a-f0-9]{64}$/.test(signature), 'Signature must be a 64-character hex string');
  });

  it('generateNonce generates 32 hex chars', () => {
    const nonce = generateNonce();
    assert.strictEqual(nonce.length, 32);
    assert.ok(/^[a-f0-9]{32}$/.test(nonce));
  });
});

describe('GoPayClient validation', () => {
  it('throws GoPayConfigurationError when API keys are missing', async () => {
    const client = new GoPayClient({});
    await assert.rejects(
      async () => client.initPayment(100, 'CDF', '+24399000000', 'REF-1'),
      GoPayConfigurationError
    );
  });

  it('rejects non-HTTPS base URLs', () => {
    assert.throws(() => new GoPayClient({ baseUrl: 'http://insecure.test' }), GoPayConfigurationError);
  });
});

interface Call {
  url: string;
  init: RequestInit;
}

function mockFetch(responses: Array<{ status: number; body: string } | Error>) {
  const calls: Call[] = [];
  let i = 0;
  const fn = (async (url: string, init: RequestInit) => {
    calls.push({ url, init });
    const next = responses[Math.min(i++, responses.length - 1)];
    if (next instanceof Error) throw next;
    return new Response(next.body, { status: next.status });
  }) as unknown as typeof fetch;
  return { fn, calls };
}

const keys = { paymentApiKey: 'pay-key', paymentSecretKey: 'secret', payoutApiKey: 'out-key', retryBaseDelayMs: 0 };

describe('GoPayClient HTTP behaviour', () => {
  it('signs initPayment and parses the transaction response', async () => {
    const { fn, calls } = mockFetch([{ status: 200, body: '{"success":true,"transaction":{"trans_id":"T1","status":"PENDING"}}' }]);
    const client = new GoPayClient({ ...keys, fetch: fn });
    const res = await client.initPayment(12, 'CDF', '+2431', 'ref');

    assert.strictEqual(res.success, true);
    assert.strictEqual(res.transId, 'T1');
    assert.strictEqual(res.transactionStatus, 'PENDING');
    assert.strictEqual(calls.length, 1);
    assert.strictEqual(calls[0].url, 'https://gopay.gooomart.com/api/v3/payment/init');
    const h = calls[0].init.headers as Record<string, string>;
    assert.strictEqual(h['x-api-key'], 'pay-key');
    const expected = computeSignature(
      '/api/v3/payment/init',
      'POST',
      'amount=12&devise=CDF&telephone=%2B2431&myref=ref',
      h['x-nonce'],
      h['x-timestamp'],
      'secret'
    );
    assert.strictEqual(h['x-signature'], expected);
    assert.deepStrictEqual(JSON.parse(String(calls[0].init.body)), { amount: 12, devise: 'CDF', telephone: '+2431', myref: 'ref' });
  });

  it('checkPayment uses the reference as an encoded path segment (aligned with PHP/Kotlin/Dart SDKs)', async () => {
    const { fn, calls } = mockFetch([{ status: 200, body: '{"success":true}' }]);
    const client = new GoPayClient({ ...keys, fetch: fn });
    await client.checkPayment('REF 1/2');
    assert.strictEqual(calls[0].url, 'https://gopay.gooomart.com/api/v3/payment/check/REF%201%2F2');
  });

  it('uses the payout key for payout operations', async () => {
    const { fn, calls } = mockFetch([{ status: 200, body: '{"success":true,"data":{"balance":"1500","devise":"CDF"}}' }]);
    const client = new GoPayClient({ ...keys, fetch: fn });
    const res = await client.getPayoutBalance();
    assert.strictEqual(res.balance, 1500);
    assert.strictEqual(res.currency, 'CDF');
    assert.strictEqual((calls[0].init.headers as Record<string, string>)['x-api-key'], 'out-key');
  });

  it('retries GET requests on transient errors with a fresh nonce', async () => {
    const { fn, calls } = mockFetch([
      new Error('ECONNRESET'),
      { status: 503, body: 'busy' },
      { status: 200, body: '{"success":true}' },
    ]);
    const client = new GoPayClient({ ...keys, fetch: fn, maxRetries: 2 });
    const res = await client.checkPayment('REF');
    assert.strictEqual(res.success, true);
    assert.strictEqual(calls.length, 3);
    const nonces = new Set(calls.map((c) => (c.init.headers as Record<string, string>)['x-nonce']));
    assert.strictEqual(nonces.size, 3);
  });

  it('gives up after maxRetries and exposes the HTTP status', async () => {
    const { fn, calls } = mockFetch([{ status: 502, body: 'bad gateway' }]);
    const client = new GoPayClient({ ...keys, fetch: fn, maxRetries: 1 });
    await assert.rejects(client.checkPayment('REF'), (err: unknown) => {
      assert.ok(err instanceof GoPayError);
      assert.strictEqual(err.statusCode, 502);
      return true;
    });
    assert.strictEqual(calls.length, 2);
  });

  it('never retries POST requests (prevents double debit)', async () => {
    const { fn, calls } = mockFetch([new Error('timeout')]);
    const client = new GoPayClient({ ...keys, fetch: fn, maxRetries: 5 });
    await assert.rejects(client.initPayment(10, 'USD', '+2431', 'R'), GoPayError);
    assert.strictEqual(calls.length, 1);
  });

  it('returns API error payloads (4xx) instead of throwing', async () => {
    const { fn } = mockFetch([{ status: 400, body: '{"success":false,"error_code":"ERR_VALIDATION","message":"bad"}' }]);
    const client = new GoPayClient({ ...keys, fetch: fn });
    const res = await client.sendPayoutTransfer(500, 'CDF', ['+2431'], ['R1']);
    assert.strictEqual(res.success, false);
    assert.strictEqual(res.errorCode, 'ERR_VALIDATION');
  });

  it('throws GoPayError on invalid JSON', async () => {
    const { fn } = mockFetch([{ status: 200, body: '<html>' }]);
    const client = new GoPayClient({ ...keys, fetch: fn });
    await assert.rejects(client.getPayoutTransferStatus('T1'), GoPayError);
  });
});
