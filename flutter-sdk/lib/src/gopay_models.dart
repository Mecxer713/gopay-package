class GoPayConfig {
  const GoPayConfig({
    this.baseUrl = 'https://gopay.gooomart.com',
    required this.paymentApiKey,
    required this.paymentSecretKey,
    required this.payoutApiKey,
    this.timeout = const Duration(seconds: 30),
  });

  final String baseUrl;
  final String paymentApiKey;
  final String paymentSecretKey;
  final String payoutApiKey;
  final Duration timeout;
}

class GoPayResponse {
  const GoPayResponse({
    required this.success,
    required this.raw,
    this.transactionStatus,
    this.transId,
    this.url,
    this.state,
    this.message,
    this.amount,
    this.currency,
    this.source,
    this.date,
    this.errorCode,
    this.balance,
  });

  final bool success;
  final String? transactionStatus, transId, url, state, message;
  final String? amount, currency, source, date, errorCode;
  final double? balance;
  final Map<String, dynamic> raw;

  factory GoPayResponse.fromJson(Map<String, dynamic> json) {
    if (!json.containsKey('success') && !json.containsKey('status')) {
      throw const GoPayException('GoPAY response is missing success/status.');
    }
    final tx = (json['transaction'] ?? json['data'] ?? const <String, dynamic>{}) as Map<String, dynamic>;
    String? stringValue(dynamic value) => value == null ? null : value.toString();
    return GoPayResponse(
      success: json.containsKey('success') ? json['success'] == true : json['status'] == 'success',
      transactionStatus: stringValue(tx['status']),
      transId: stringValue(tx['trans_id']),
      url: stringValue(tx['url']),
      state: stringValue(tx['state']),
      message: stringValue(json['message']),
      amount: stringValue(tx['amount']),
      currency: stringValue(tx['currency']),
      source: stringValue(tx['source']),
      date: stringValue(tx['date']),
      errorCode: stringValue(json['error_code']),
      balance: ((tx['balance'] ?? json['balance']) as num?)?.toDouble(),
      raw: json,
    );
  }
}

class GoPayException implements Exception {
  const GoPayException(this.message, [this.cause]);
  final String message;
  final Object? cause;
  @override
  String toString() => 'GoPayException: $message';
}

class GoPayConfigurationException extends GoPayException {
  const GoPayConfigurationException(super.message);
}
