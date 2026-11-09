# Changelog - GoPAY JavaScript & TypeScript SDK

All notable changes to this project will be documented in this file.

## [0.2.0] - 2026-11-09
### Fixed
- **Published package was unusable**: `main`/`types` pointed to `dist/index.js` while the compiler emitted `dist/src/index.js`.
- `checkPayment()` now calls `GET /api/v3/payment/check/{ref}` (encoded path segment), aligned with the PHP, Kotlin and Dart SDKs (previously sent `?ref=`).
- HTTP 5xx/408/429 responses are no longer silently parsed as success payloads.

### Added
- Dual **ESM + CommonJS** build with a conditional `exports` map (`dist/esm`, `dist/cjs`, `dist/types`) — works with Vite, Webpack, Next.js and plain Node.
- Automatic retry with exponential backoff + jitter for idempotent `GET` requests (`maxRetries`, `retryBaseDelayMs`). `POST`/`DELETE` are never replayed to avoid double debits.
- Injectable `fetch` option (tests, proxies, polyfills).
- `GoPayError.statusCode`.
- HTTPS enforcement on `baseUrl`.
- Package smoke test (`npm run test:package`) and CI publishing to npm with provenance.

## [0.1.0] - 2026-10-27
### Added
- Initial release of the official TypeScript / JavaScript client for GoPAY.
- HMAC-SHA256 request signing with automatic nonce and timestamp.
- Full support for Payments (`initPayment`, `checkPayment`).
- Full support for Payouts (`getPayoutBalance`, `getPayoutTransfers`, `sendPayoutTransfer`, `getPayoutTransferStatus`, `deletePayoutTransfer`).
- Strongly typed TypeScript DTOs and Error models.
- Zero runtime external dependencies (Node 18+ native fetch and crypto).
