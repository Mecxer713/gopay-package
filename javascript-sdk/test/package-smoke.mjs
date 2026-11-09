// Vérifie que le package construit est consommable en CommonJS ET en ESM via le champ "exports".
import { createRequire } from 'node:module';
import assert from 'node:assert';

const require = createRequire(import.meta.url);
const cjs = require('gopay-js');
const esm = await import('gopay-js');

for (const [kind, mod] of [['cjs', cjs], ['esm', esm]]) {
  assert.strictEqual(typeof mod.GoPayClient, 'function', `${kind}: GoPayClient export missing`);
  assert.strictEqual(typeof mod.computeSignature, 'function', `${kind}: computeSignature export missing`);
  assert.ok(new mod.GoPayClient({ paymentApiKey: 'k', paymentSecretKey: 's' }));
}
console.log('package smoke test OK (cjs + esm)');

