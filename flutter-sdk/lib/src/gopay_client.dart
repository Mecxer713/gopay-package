import 'dart:convert';
import 'dart:math';

import 'package:crypto/crypto.dart';

import 'gopay_models.dart';
import 'gopay_transport.dart';

class GoPayClient {
  GoPayClient(this.config, {GoPayTransport? transport, int Function()? clockSeconds, String Function()? nonceProvider})
      : _transport = transport ?? IoGoPayTransport(config.timeout),
        _clockSeconds = clockSeconds ?? (() => DateTime.now().millisecondsSinceEpoch ~/ 1000),
        _nonceProvider = nonceProvider ?? _randomNonce {
    if (!config.baseUrl.startsWith('https://')) throw ArgumentError('GoPAY baseUrl must use HTTPS.');
  }

  final GoPayConfig config;
  final GoPayTransport _transport;
  final int Function() _clockSeconds;
  final String Function() _nonceProvider;

  Future<GoPayResponse> initPayment(double amount, String devise, String telephone, String myref, {String? usersId}) async {
    final payload = <String, dynamic>{'amount': amount, 'devise': devise, 'telephone': telephone, 'myref': myref};
    if (usersId != null) payload['users_id'] = usersId;
    return _request('POST', '/api/v3/payment/init', payload);
  }

  Future<GoPayResponse> checkPayment(String ref) => _request('GET', '/api/v3/payment/check/${Uri.encodeComponent(ref)}', const {});
  Future<GoPayResponse> getPayoutBalance() => _request('GET', '/api/payout/v3/balance', const {}, payout: true);
  Future<Object?> getPayoutTransfers() => _requestRaw('GET', '/api/payout/v3/transfer', const {}, payout: true);

  Future<GoPayResponse> sendPayoutTransfer(double montant, String devise, List<String> telephones, List<String> myrefs, {String? dateDenvoi}) {
    final payload = <String, dynamic>{'montant': montant, 'devise': devise, 'telephone': telephones, 'myref': myrefs};
    if (dateDenvoi != null) payload['date_denvoi'] = dateDenvoi;
    return _request('POST', '/api/payout/v3/transfer', payload, payout: true);
  }

  Future<GoPayResponse> getPayoutTransferStatus(String transIdOrMyref) =>
      _request('GET', '/api/payout/v3/transfer/${Uri.encodeComponent(transIdOrMyref)}', const {}, payout: true);
  Future<GoPayResponse> deletePayoutTransfer(String transId) =>
      _request('DELETE', '/api/payout/v3/transfer/${Uri.encodeComponent(transId)}', const {}, payout: true);

  Future<GoPayResponse> _request(String method, String endpoint, Map<String, dynamic> payload, {bool payout = false}) async {
    final decoded = await _requestRaw(method, endpoint, payload, payout: payout);
    if (decoded is! Map<String, dynamic>) throw const GoPayException('Expected a JSON object from GoPAY.');
    return GoPayResponse.fromJson(decoded);
  }

  Future<Object?> _requestRaw(String method, String endpoint, Map<String, dynamic> payload, {bool payout = false}) async {
    final apiKey = payout ? config.payoutApiKey : config.paymentApiKey;
    final secret = config.paymentSecretKey;
    if (apiKey.isEmpty || secret.isEmpty) throw const GoPayConfigurationException('GoPAY API key and signing secret must be configured.');
    final nonce = _nonceProvider();
    final timestamp = _clockSeconds();
    final signature = _sign(endpoint, method, payload, nonce, timestamp, secret);
    final headers = <String, String>{
      'Accept': 'application/json', 'Content-Type': 'application/json', 'x-api-key': apiKey,
      'x-signature': signature, 'x-timestamp': '$timestamp', 'x-nonce': nonce,
    };
    final request = GoPayHttpRequest(
      method: method,
      url: Uri.parse('${config.baseUrl.replaceFirst(RegExp(r'/+$'), '')}$endpoint'),
      headers: headers,
      body: method == 'POST' ? jsonEncode(payload) : null,
    );
    late final GoPayHttpResponse response;
    try {
      response = await _transport.send(request).timeout(config.timeout);
    } catch (error) {
      throw GoPayException('GoPAY HTTP request failed: $error', error);
    }
    if (response.body.isEmpty) return <String, dynamic>{};
    try {
      final decoded = jsonDecode(response.body);
      return decoded;
    } catch (error) {
      throw GoPayException('Invalid JSON response from GoPAY.', error);
    }
  }

  String _sign(String endpoint, String method, Map<String, dynamic> payload, String nonce, int timestamp, String secret) {
    final path = Uri.parse(config.baseUrl).resolve(endpoint).path;
    final params = <String>[];
    for (final entry in payload.entries) {
      final value = entry.value;
      if (value is List) {
        for (var i = 0; i < value.length; i++) {
          params.add('${_phpEncode('${entry.key}[$i]')}=${_phpEncode(_queryValue(value[i]))}');
        }
      } else {
        params.add('${_phpEncode(entry.key)}=${_phpEncode(_queryValue(value))}');
      }
    }
    final message = '$path$method${params.join('&')}$nonce$timestamp';
    return Hmac(sha256, utf8.encode(secret)).convert(utf8.encode(message)).toString();
  }

  static String _phpEncode(String value) {
    final output = StringBuffer();
    for (final byte in utf8.encode(value)) {
      final unreserved = (byte >= 0x41 && byte <= 0x5a) ||
          (byte >= 0x61 && byte <= 0x7a) ||
          (byte >= 0x30 && byte <= 0x39) ||
          byte == 0x2d || byte == 0x5f || byte == 0x2e;
      if (unreserved) {
        output.writeCharCode(byte);
      } else if (byte == 0x20) {
        output.write('+');
      } else {
        output.write('%${byte.toRadixString(16).padLeft(2, '0').toUpperCase()}');
      }
    }
    return output.toString();
  }

  static String _queryValue(Object? value) {
    if (value is num && value.isFinite && value == value.truncateToDouble()) return value.toInt().toString();
    return value.toString();
  }

  static String _randomNonce() {
    final random = Random.secure();
    return List<int>.generate(16, (_) => random.nextInt(256)).map((byte) => byte.toRadixString(16).padLeft(2, '0')).join();
  }
}
