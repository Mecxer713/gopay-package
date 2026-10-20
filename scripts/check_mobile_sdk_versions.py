#!/usr/bin/env python3

from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

SEMVER_RE = re.compile(r"^\d+\.\d+\.\d+(?:[-A-Za-z0-9.]+)?$")


def read_text(path: Path) -> str:
    return path.read_text(encoding="utf-8")


def require_semver(version: str, label: str) -> None:
    if not SEMVER_RE.fullmatch(version):
        raise ValueError(f"{label} is not a valid SemVer value: {version!r}")


def parse_flutter_pubspec_version() -> str:
    text = read_text(ROOT / "flutter-sdk" / "pubspec.yaml")
    match = re.search(r"(?m)^version:\s*([^\s]+)", text)
    if match is None:
        raise ValueError("Unable to find flutter-sdk version in pubspec.yaml")
    version = match.group(1).strip()
    require_semver(version, "Flutter SDK")
    return version


def parse_flutter_runtime_version() -> str:
    text = read_text(ROOT / "flutter-sdk" / "lib" / "src" / "version.dart")
    match = re.search(r"const\s+String\s+gopaySdkVersion\s*=\s*['\"]([^'\"]+)['\"]", text)
    if match is None:
        raise ValueError("Unable to find gopaySdkVersion in flutter-sdk/lib/src/version.dart")
    version = match.group(1).strip()
    require_semver(version, "Flutter runtime version")
    return version


def parse_android_runtime_version() -> str:
    text = read_text(ROOT / "android-sdk" / "gopay" / "src" / "main" / "java" / "com" / "mecxer713" / "gopay" / "GoPaySdkVersion.kt")
    match = re.search(r'const\s+val\s+VERSION\s*=\s*"([^"]+)"', text)
    if match is None:
        raise ValueError("Unable to find VERSION in GoPaySdkVersion.kt")
    version = match.group(1).strip()
    require_semver(version, "Android runtime version")
    return version


def parse_changelog_latest_version(path: Path) -> str:
    text = read_text(path)
    match = re.search(r"(?m)^##\s+([^\s]+)", text)
    if match is None:
        raise ValueError(f"Unable to find a release heading in {path.name}")
    version = match.group(1).strip()
    require_semver(version, f"{path.name} changelog")
    return version


def ensure_same_versions() -> None:
    flutter_pubspec = parse_flutter_pubspec_version()
    flutter_runtime = parse_flutter_runtime_version()
    android_runtime = parse_android_runtime_version()
    flutter_changelog = parse_changelog_latest_version(ROOT / "flutter-sdk" / "CHANGELOG.md")
    android_changelog = parse_changelog_latest_version(ROOT / "android-sdk" / "CHANGELOG.md")

    mismatches = []
    if flutter_pubspec != flutter_runtime:
        mismatches.append(f"Flutter pubspec ({flutter_pubspec}) != runtime constant ({flutter_runtime})")
    if flutter_pubspec != flutter_changelog:
        mismatches.append(f"Flutter pubspec ({flutter_pubspec}) != changelog ({flutter_changelog})")
    if android_runtime != android_changelog:
        mismatches.append(f"Android runtime ({android_runtime}) != changelog ({android_changelog})")
    if mismatches:
        raise ValueError("\n".join(mismatches))

    print(f"Flutter package version: {flutter_pubspec}")
    print(f"Android SDK version: {android_runtime}")
    print("Mobile SDK version consistency check passed.")


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Validate version consistency for all mobile SDKs.")
    parser.add_argument("--sdk", choices=["android", "flutter", "all"], default="all")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    try:
        if args.sdk in {"all", "flutter"}:
            flutter_pubspec = parse_flutter_pubspec_version()
            flutter_runtime = parse_flutter_runtime_version()
            flutter_changelog = parse_changelog_latest_version(ROOT / "flutter-sdk" / "CHANGELOG.md")
            if flutter_pubspec != flutter_runtime:
                raise ValueError(f"Flutter pubspec ({flutter_pubspec}) != runtime constant ({flutter_runtime})")
            if flutter_pubspec != flutter_changelog:
                raise ValueError(f"Flutter pubspec ({flutter_pubspec}) != changelog ({flutter_changelog})")
            print(f"Flutter SDK version check passed: {flutter_pubspec}")
        if args.sdk in {"all", "android"}:
            android_runtime = parse_android_runtime_version()
            android_changelog = parse_changelog_latest_version(ROOT / "android-sdk" / "CHANGELOG.md")
            if android_runtime != android_changelog:
                raise ValueError(f"Android runtime ({android_runtime}) != changelog ({android_changelog})")
            print(f"Android SDK version check passed: {android_runtime}")
        return 0
    except Exception as exc:  # pragma: no cover - CLI exit path
        print(f"Version check failed: {exc}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
