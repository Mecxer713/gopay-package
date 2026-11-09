import 'dart:async';

import 'package:http/http.dart' as http;

class GoPayHttpRequest {
  const GoPayHttpRequest({required this.method, required this.url, required this.headers, this.body});
  final String method;
  final Uri url;
  final Map<String, String> headers;
  final String? body;
}

class GoPayHttpResponse {
  const GoPayHttpResponse(this.statusCode, this.body);
  final int statusCode;
  final String body;
}

abstract interface class GoPayTransport {
  Future<GoPayHttpResponse> send(GoPayHttpRequest request);
}

/// Transport par défaut basé sur `package:http`.
///
/// Fonctionne sur toutes les plateformes Flutter/Dart : Android, iOS, desktop **et Web**.
/// Pour l'ancienne implémentation `dart:io`, importer `package:gopay/io.dart`.
class HttpGoPayTransport implements GoPayTransport {
  HttpGoPayTransport(this.timeout, {http.Client? client})
      : _client = client ?? http.Client(),
        _ownsClient = client == null;

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
