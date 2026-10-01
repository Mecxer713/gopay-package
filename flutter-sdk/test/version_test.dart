import 'dart:io';

import 'package:gopay/gopay.dart';
import 'package:test/test.dart';

String _pubspecVersion() {
  final pubspec = File('pubspec.yaml').readAsStringSync();
  final match = RegExp(r'^version:\s*([^\s]+)', multiLine: true).firstMatch(pubspec);
  if (match == null) {
    throw StateError('Version not found in pubspec.yaml');
  }
  return match.group(1)!;
}

void main() {
  test('package version is exposed as a runtime SDK constant', () {
    expect(gopaySdkVersion, equals(_pubspecVersion()));
  });
}
