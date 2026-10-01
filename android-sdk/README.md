# GoPAY Android SDK

Kotlin Android library for GoPAY payments and payouts. The module is `:gopay` and requires Android API 23 or newer. The host app supplies the merchant's own keys when it creates a client; the SDK does not contain fixed GoPAY credentials or a settings screen.

## Install from Gradle

After a GitHub release tag is built by JitPack, add JitPack to the consuming app's `settings.gradle.kts`:

```kotlin
dependencyResolutionManagement {
    repositories {
        google()
        mavenCentral()
        maven { url = uri("https://jitpack.io") }
    }
}
```

Then add the standalone Android artifact to the app module's `build.gradle.kts` (replace the tag with a published release):

```kotlin
dependencies {
    implementation("com.github.Mecxer713.gopay-package:gopay:android-v0.1.0")
}
```

The artifact contains the Kotlin client and has no dependency on Composer, PHP, or the PHP SDK sources. For local development, open `android-sdk/` as a standalone Gradle build.

```kotlin
// Read these values from the app's settings form or account configuration.
val gopay = GoPayClient(GoPayConfig(
    paymentApiKey = apiKeyInput.trim(),
    paymentSecretKey = secretKeyInput,
    payoutApiKey = payoutApiKeyInput.trim(), // use "" when the app does not use payouts
))

val result = withContext(Dispatchers.IO) {
    gopay.initPayment(4000.0, "CDF", "+24399000000", "ORDER-001")
}
```

All client methods perform synchronous network I/O; call them on a background dispatcher/thread. Use `checkPayment`, `getPayoutBalance`, `getPayoutTransfers`, `sendPayoutTransfer`, `getPayoutTransferStatus` and `deletePayoutTransfer` for the other GoPAY operations. `GoPayResponse.raw` retains the complete decoded API object.

Create a new `GoPayClient` after the user saves or changes their keys. The client uses the values supplied to its `GoPayConfig`; it does not load build-time constants. The payout API key is optional until a payout operation is called. The app may store settings between launches, but storage and the screen for entering keys belong to the host app.

## Security

This direct-client design requires the API keys and signing secret in the application. Values embedded in an APK can be recovered, including after obfuscation. Do not treat this as secure storage. A backend that holds the secret and signs requests is the safer production architecture, especially for payout operations.

Run the release unit tests and check the Maven artifact locally from the repository root with `bash ./gradlew :gopay:testReleaseUnitTest :gopay:publishReleasePublicationToMavenLocal`. On Windows, use `.\gradlew.bat` with the same tasks. The wrapper pins Gradle for repeatable builds.
