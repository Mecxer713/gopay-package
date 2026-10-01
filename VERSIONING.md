# Mobile SDK maintenance and releases

The Android and Flutter SDKs are independent products in this repository. Each has its own public API, release number, changelog, tests, and release tag. A release of one SDK does not require a version change or publication of the other.

## Version policy

- Follow SemVer for both SDKs. Before `1.0.0`, use the `0.MINOR.PATCH` convention: increment the minor number for public API changes or additions, and the patch number for compatible fixes. From `1.0.0`, use major for breaking changes, minor for backwards compatible features, and patch for compatible fixes.
- Keep Android's release notes in `android-sdk/CHANGELOG.md`; the JitPack Maven artifact version comes from the Android Git tag and is also exposed from `GoPaySdkVersion.VERSION` in code.
- Keep Flutter's release number in `flutter-sdk/pubspec.yaml`, its release notes in `flutter-sdk/CHANGELOG.md`, and the runtime version constant in `flutter-sdk/lib/src/version.dart`.
- Use independent tags: `android-vX.Y.Z` and `flutter-vX.Y.Z`. Do not create a shared version for all packages.
- Avoid changing existing released versions. Publish a new version for every released change.
- For future maintenance, keep the public SDK version constant and the published package version in sync during release preparation and CI checks.

## Release checklist

1. Bump the SDK version only after reviewing the public API and changelog entries.
2. Update the changelog heading to `## X.Y.Z` and list the user-visible changes.
3. Run the automated consistency check: `python scripts/check_mobile_sdk_versions.py --sdk all`.
4. Validate the SDK-specific build and tests before tagging release.
5. Create the matching tag (`android-vX.Y.Z` or `flutter-vX.Y.Z`) and publish only after CI passes.

This automation prevents silent drift between the runtime constant, package metadata, and changelog entries.

## Android release

1. Update `android-sdk/CHANGELOG.md` and review public API compatibility.
2. Run `bash ./gradlew :gopay:testReleaseUnitTest :gopay:publishReleasePublicationToMavenLocal` from the repository root (Windows: `.\gradlew.bat` with the same tasks).
3. Create an annotated `android-vX.Y.Z` tag. JitPack builds only the `gopay` Android library module; consuming apps add that exact tag as the Gradle dependency.
4. Confirm the Android CI workflow passed and inspect the JitPack build before announcing the version.

## Flutter release

1. Set `version: X.Y.Z` in `flutter-sdk/pubspec.yaml`, update `flutter-sdk/CHANGELOG.md`, and review public API compatibility.
2. Run `flutter pub get`, `dart format lib test`, `flutter analyze`, `flutter test`, and `dart pub publish --dry-run` from `flutter-sdk/`.
3. Create an annotated `flutter-vX.Y.Z` tag matching the pubspec version, then publish that package from `flutter-sdk/` to pub.dev.
4. Confirm the Flutter CI workflow passed before publishing.

## Ongoing maintenance

- Keep platform-specific implementation and tests inside their SDK directories. Do not import or generate mobile code from the PHP SDK.
- Centralize Android Gradle plugin and JUnit versions in `gradle/libs.versions.toml`.
- Run the workflow for the SDK being changed; changes to shared Gradle configuration must pass Android CI.
