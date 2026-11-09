## 0.2.2

- Optimized pubspec description length to fit pana's recommended 60-180 character range for a perfect 160/160 pub points score.

## 0.2.1

- Enhanced dartdoc API documentation across all public classes, methods, and fields.
- Enriched search metadata, descriptions, and keywords for pub.dev discoverability.

## 0.2.0

- **Flutter Web support**: the default transport is now `HttpGoPayTransport` (based on `package:http`), which works on Android, iOS, desktop and Web.
- **Breaking**: `IoGoPayTransport` moved to `package:gopay/io.dart` (it depends on `dart:io`, which is unavailable on Web).
- `GoPayConfig.payoutApiKey` is now optional (defaults to `''`) — payment-only mobile integrations no longer need to ship a payout key.
- Automatic retry with exponential backoff + jitter for idempotent `GET` requests on network errors or HTTP 408/429/5xx (`maxRetries`, `retryBaseDelay`). `POST`/`DELETE` are never replayed.
- A fresh nonce/timestamp is generated for every attempt.
- `GoPayException.statusCode` exposes the HTTP status when available.
- pub.dev metadata: topics, monorepo repository / issue tracker links.

## 0.1.0

- Initial standalone Flutter/Dart client for GoPAY payments and payouts.
