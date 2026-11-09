# Changelog

Versioning follows SemVer independently from the Flutter and PHP packages. Until `1.0.0`, a breaking change increments the minor version; compatible fixes increment the patch version.

## 0.2.0

- Automatic retry with exponential backoff and jitter on idempotent `GET` requests (network errors and HTTP 408/429/5xx).
- `POST` and `DELETE` requests are never retried to prevent double debits.
- Fresh nonce and timestamp generated for each retry attempt.
- Added `GoPayWebhook.verify(payload, signature, secretKey)` helper method for HMAC-SHA256 signature validation of incoming webhooks.
- Added `statusCode` to `GoPayException` for granular HTTP error inspection.
- Upgraded compile target and build validation on Android Platform 35.

## 0.1.0

- Initial standalone Android/Kotlin client for GoPAY payments and payouts.
