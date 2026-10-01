# GoPAY Flutter SDK

Dart client package for Flutter mobile apps. After the first release is published on pub.dev, install it with:

```bash
flutter pub add gopay
```

For local development before the package is published, add the package path to the app's `pubspec.yaml`:

```yaml
dependencies:
  gopay:
    path: ../gopay-package/flutter-sdk
```

The package includes its own Dart implementation and does not depend on Composer, PHP, or the PHP SDK sources.

```dart
final gopay = GoPayClient(const GoPayConfig(
  paymentApiKey: 'your_api_key',
  paymentSecretKey: 'your_secret_key',
  payoutApiKey: 'your_payout_api_key',
));

final response = await gopay.initPayment(4000, 'CDF', '+24399000000', 'ORDER-001');
if (response.success) print(response.transId);
```

The client also exposes `checkPayment`, `getPayoutBalance`, `getPayoutTransfers`, `sendPayoutTransfer`, `getPayoutTransferStatus` and `deletePayoutTransfer`. Network calls are asynchronous. The `GoPayTransport` interface can be implemented by applications and tests.

## Security

This direct-client design requires the API keys and signing secret in the application. Values embedded in an APK can be recovered, including after obfuscation. Do not treat this as secure storage. A backend that holds the secret and signs requests is the safer production architecture, especially for payout operations.

Run `flutter pub get` and `flutter test` from this directory.
