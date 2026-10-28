#!/usr/bin/env bash
# Forwarding Gradle wrapper for Android SDK
set -e
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "$ROOT_DIR/android-sdk/gradlew" -p "$ROOT_DIR/android-sdk" "$@"
