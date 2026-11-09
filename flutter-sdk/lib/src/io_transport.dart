import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'gopay_transport.dart';

/// Transport basé sur `dart:io` (non disponible sur Flutter Web).
///
/// Conservé pour compatibilité ; préférer [HttpGoPayTransport] (transport par défaut).
class IoGoPayTransport implements GoPayTransport {
  /// Crée un [IoGoPayTransport] avec le timeout spécifié.
  IoGoPayTransport(this.timeout);

  /// Timeout réseau par requête.
  final Duration timeout;

  @override
  Future<GoPayHttpResponse> send(GoPayHttpRequest request) async {
    final client = HttpClient()..connectionTimeout = timeout;
    try {
      final outgoing = await client.openUrl(request.method, request.url).timeout(timeout);
      request.headers.forEach(outgoing.headers.set);
      if (request.body != null) {
        outgoing.headers.contentType = ContentType.json;
        outgoing.write(request.body);
      }
      final response = await outgoing.close().timeout(timeout);
      return GoPayHttpResponse(response.statusCode, await utf8.decoder.bind(response).join());
    } finally {
      client.close(force: true);
    }
  }
}
