// Vérifie que le package construit est consommable en CommonJS ET en ESM via le champ "exports".
import { createRequire } from 'node:module';
import assert from 'node:assert';
import { readFileSync } from 'node:fs';

const require = createRequire(import.meta.url);
const pkg = JSON.parse(readFileSync(new URL('../package.json', import.meta.url), 'utf-8'));
const cjs = require(pkg.name);
const esm = await import(pkg.name);

for (const [kind, mod] of [['cjs', cjs], ['esm', esm]]) {
  assert.strictEqual(typeof mod.GoPayClient, 'function', `${kind}: GoPayClient export missing`);
  assert.strictEqual(typeof mod.computeSignature, 'function', `${kind}: computeSignature export missing`);
  assert.ok(new mod.GoPayClient({ paymentApiKey: 'k', paymentSecretKey: 's' }));
}
console.log('package smoke test OK (cjs + esm)');

