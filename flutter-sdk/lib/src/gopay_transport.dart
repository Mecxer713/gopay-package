import 'dart:async';

import 'package:http/http.dart' as http;

/// Requête HTTP envoyée à la passerelle GoPAY.
class GoPayHttpRequest {
  /// Crée une requête HTTP GoPAY.
  const GoPayHttpRequest({required this.method, required this.url, required this.headers, this.body});

  /// Méthode HTTP ('GET', 'POST', 'DELETE').
  final String method;

  /// URL complète de la requête.
  final Uri url;

  /// En-têtes HTTP requis (incluant x-api-key, x-signature, x-timestamp, x-nonce).
  final Map<String, String> headers;

  /// Corps de la requête au format JSON si applicable.
  final String? body;
}

/// Réponse HTTP brute retournée par la passerelle GoPAY.
class GoPayHttpResponse {
  /// Crée une réponse HTTP GoPAY.
  const GoPayHttpResponse(this.statusCode, this.body);

  /// Code de statut HTTP (200, 400, 401, 500, etc.).
  final int statusCode;

  /// Corps de la réponse sous forme de chaîne de caractères.
  final String body;
}

/// Interface d'abstraction du transport réseau (permet les mocks dans les tests unitaires).
abstract interface class GoPayTransport {
  /// Exécute la requête HTTP et retourne un [GoPayHttpResponse].
  Future<GoPayHttpResponse> send(GoPayHttpRequest request);
}

/// Transport par défaut basé sur `package:http`.
///
/// Fonctionne sur toutes les plateformes Flutter/Dart : Android, iOS, desktop **et Web**.
/// Pour l'ancienne implémentation `dart:io`, importer `package:gopay/io.dart`.
class HttpGoPayTransport implements GoPayTransport {
  /// Crée un [HttpGoPayTransport] avec le timeout spécifié et un client optionnel.
  HttpGoPayTransport(this.timeout, {http.Client? client})
      : _client = client ?? http.Client(),
        _ownsClient = client == null;

  /// Timeout par requête.
  final Duration timeout;
  final http.Client _client;
  final bool _ownsClient;

  @override
  Future<GoPayHttpResponse> send(GoPayHttpRequest request) async {
    final outgoing = http.Request(request.method, request.url)..headers.addAll(request.headers);
    if (request.body != null) outgoing.body = request.body!;
    final streamed = await _client.send(outgoing).timeout(timeout);
    final response = await http.Response.fromStream(streamed).timeout(timeout);
    return GoPayHttpResponse(response.statusCode, response.body);
  }

  /// Libère le client HTTP interne (sans effet si un client a été fourni).
  void close() {
    if (_ownsClient) _client.close();
  }
}
