/// Configuration requise pour instancier [GoPayClient].
class GoPayConfig {
  /// Crée une configuration GoPAY.
  const GoPayConfig({
    this.baseUrl = 'https://gopay.gooomart.com',
    required this.paymentApiKey,
    required this.paymentSecretKey,
    this.payoutApiKey = '',
    this.timeout = const Duration(seconds: 30),
    this.maxRetries = 2,
    this.retryBaseDelay = const Duration(milliseconds: 300),
  });

  /// URL de base de la passerelle GoPAY (HTTPS obligatoire).
  final String baseUrl;

  /// Clé API Marchand pour les paiements.
  final String paymentApiKey;

  /// Secret de signature HMAC-SHA256.
  final String paymentSecretKey;

  /// Clé API Marchand pour les transferts sortants (Payout).
  /// Optionnelle pour les applications clientes purement de paiement.
  final String payoutApiKey;

  /// Délai maximal d'attente réseau par tentative.
  final Duration timeout;

  /// Nombre de nouvelles tentatives pour les requêtes GET en cas d'erreur transitoire.
  final int maxRetries;

  /// Délai de base pour l'attente exponentielle (backoff).
  final Duration retryBaseDelay;
}

/// Modèle de réponse unifié pour les opérations GoPAY.
class GoPayResponse {
  /// Crée une instance de [GoPayResponse].
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

  /// Indique si la requête API a été traitée avec succès.
  final bool success;

  /// Statut de la transaction (ex: 'success', 'pending', 'failed').
  final String? transactionStatus;

  /// Identifiant unique de la transaction généré par GoPAY.
  final String? transId;

  /// URL de redirection éventuelle pour le paiement.
  final String? url;

  /// État actuel de l'opération.
  final String? state;

  /// Message textuel renvoyé par la passerelle.
  final String? message;

  /// Montant de la transaction sous forme de chaîne de caractères.
  final String? amount;

  /// Devise de la transaction ('CDF' ou 'USD').
  final String? currency;

  /// Source émettrice de la transaction.
  final String? source;

  /// Horodatage de la transaction.
  final String? date;

  /// Code d'erreur officiel en cas d'échec.
  final String? errorCode;

  /// Solde disponible du compte Payout.
  final double? balance;

  /// Données JSON brutes retournées par l'API.
  final Map<String, dynamic> raw;

  /// Construit un [GoPayResponse] à partir d'un objet JSON décodé.
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

/// Exception levée lors d'une erreur d'exécution ou réseau avec GoPAY.
class GoPayException implements Exception {
  /// Crée une [GoPayException] avec un message et une cause éventuelle.
  const GoPayException(this.message, [this.cause, this.statusCode]);

  /// Description de l'erreur.
  final String message;

  /// Cause sous-jacente éventuelle.
  final Object? cause;

  /// Code HTTP renvoyé par GoPAY, si disponible.
  final int? statusCode;

  @override
  String toString() => 'GoPayException: $message';
}

/// Exception levée lorsque les clés requises sont manquantes dans la configuration.
class GoPayConfigurationException extends GoPayException {
  /// Crée une [GoPayConfigurationException].
  const GoPayConfigurationException(super.message);
}
