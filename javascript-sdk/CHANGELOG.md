# Changelog - GoPAY JavaScript & TypeScript SDK

All notable changes to this project will be documented in this file.

## [0.1.0] - 2026-10-27
### Added
- Initial release of the official TypeScript / JavaScript client for GoPAY.
- HMAC-SHA256 request signing with automatic nonce and timestamp.
- Full support for Payments (`initPayment`, `checkPayment`).
- Full support for Payouts (`getPayoutBalance`, `getPayoutTransfers`, `sendPayoutTransfer`, `getPayoutTransferStatus`, `deletePayoutTransfer`).
- Strongly typed TypeScript DTOs and Error models.
- Zero runtime external dependencies (Node 18+ native fetch and crypto).
