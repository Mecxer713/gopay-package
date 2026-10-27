import { describe, it } from 'node:test';
import assert from 'node:assert';
import { phpUrlEncode, buildQueryString, computeSignature, generateNonce } from '../src/hmac';
import { GoPayClient } from '../src/client';
import { GoPayConfigurationError } from '../src/errors';

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
});
