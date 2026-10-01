import 'dart:async';
import 'dart:convert';
import 'dart:io';

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

class IoGoPayTransport implements GoPayTransport {
  IoGoPayTransport(this.timeout);
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
