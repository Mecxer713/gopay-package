import 'dart:io';
import 'package:gopay/gopay.dart';
import 'package:crypto/crypto.dart';
import 'package:test/test.dart';
import 'dart:convert';

class FakeTransport implements GoPayTransport {
  FakeTransport(this.response);
  final GoPayHttpResponse response;
  GoPayHttpRequest? request;

  @override
  Future<GoPayHttpResponse> send(GoPayHttpRequest request) async {
    this.request = request;
    return response;
  }
}

GoPayConfig config() => const GoPayConfig(paymentApiKey: 'pay-key', paymentSecretKey: 'secret', payoutApiKey: 'out-key');

void main() {
  test('payment signs request and parses transaction response', () async {
    final transport = FakeTransport(const GoPayHttpResponse(200, '{"success":true,"transaction":{"trans_id":"T1"}}'));
    final client = GoPayClient(config(), transport: transport, clockSeconds: () => 1234, nonceProvider: () => 'nonce');
    final response = await client.initPayment(12, 'CDF', '+2431', 'ref');
    expect(response.success, isTrue);
    expect(response.transId, 'T1');
    expect(transport.request!.method, 'POST');
    expect(transport.request!.url.path, '/api/v3/payment/init');
    expect(transport.request!.headers['x-api-key'], 'pay-key');
    expect(transport.request!.headers['x-timestamp'], '1234');
    final expected = Hmac(sha256, utf8.encode('secret')).convert(utf8.encode(
      '/api/v3/payment/initPOSTamount=12&devise=CDF&telephone=%2B2431&myref=refnonce1234',
    )).toString();
    expect(transport.request!.headers['x-signature'], expected);
  });

  test('payout uses payout key and preserves API error payload', () async {
    final transport = FakeTransport(const GoPayHttpResponse(400, '{"success":false,"error_code":"ERR_VALIDATION"}'));
    final client = GoPayClient(config(), transport: transport, clockSeconds: () => 1, nonceProvider: () => 'n');
    final response = await client.sendPayoutTransfer(3, 'CDF', ['1'], ['r']);
    expect(transport.request!.headers['x-api-key'], 'out-key');
    expect(response.success, isFalse);
    expect(response.errorCode, 'ERR_VALIDATION');
    expect(transport.request!.body, contains('"telephone":["1"]'));
  });

  test('payout balance reads nested data', () async {
    final client = GoPayClient(config(), transport: FakeTransport(
      const GoPayHttpResponse(200, '{"success":true,"data":{"balance":5000,"currency":"USD"}}'),
    ));
    final response = await client.getPayoutBalance();
    expect(response.balance, 5000);
    expect(response.currency, 'USD');
  });

  test('malformed JSON becomes GoPayException', () async {
    final client = GoPayClient(config(), transport: FakeTransport(const GoPayHttpResponse(200, 'not-json')));
    expect(() => client.checkPayment('ref'), throwsA(isA<GoPayException>()));
  });

  test('transport failure becomes GoPayException', () async {
    final client = GoPayClient(config(), transport: _FailingTransport());
    await expectLater(client.checkPayment('ref'), throwsA(isA<GoPayException>()));
  });

  test('every payout operation uses its documented route', () async {
    final routes = <String>[];
    final transport = _SequenceTransport(routes);
    final client = GoPayClient(config(), transport: transport);
    await client.getPayoutBalance();
    final transfers = await client.getPayoutTransfers() as List<dynamic>;
    await client.sendPayoutTransfer(1, 'CDF', ['1'], ['r']);
    await client.getPayoutTransferStatus('T1');
    await client.deletePayoutTransfer('T1');
    expect(routes, [
      'GET /api/payout/v3/balance',
      'GET /api/payout/v3/transfer',
      'POST /api/payout/v3/transfer',
      'GET /api/payout/v3/transfer/T1',
      'DELETE /api/payout/v3/transfer/T1',
    ]);
    expect(transfers.single['trans_id'], 'T1');
  });
}

class _SequenceTransport implements GoPayTransport {
  _SequenceTransport(this.routes);
  final List<String> routes;
  @override
  Future<GoPayHttpResponse> send(GoPayHttpRequest request) async {
    routes.add('${request.method} ${request.url.path}');
    if (request.url.path == '/api/payout/v3/transfer' && request.method == 'GET') {
      return const GoPayHttpResponse(200, '[{"trans_id":"T1"}]');
    }
    return const GoPayHttpResponse(200, '{"success":true,"balance":10}');
  }
}

class _FailingTransport implements GoPayTransport {
  @override
  Future<GoPayHttpResponse> send(GoPayHttpRequest request) async => throw const SocketException('offline');
}
