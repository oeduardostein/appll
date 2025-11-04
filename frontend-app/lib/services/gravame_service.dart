import 'dart:convert';

import 'package:http/http.dart' as http;

class GravameException implements Exception {
  GravameException(this.message);

  final String message;

  @override
  String toString() => 'GravameException: $message';
}

class GravameService {
  GravameService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  static final Uri _gravameUri = Uri.parse(
    'https://applldespachante.skalacode.com/api/gravame',
  );

  Future<Map<String, dynamic>> consultar({
    required String placa,
    required String renavam,
    required String uf,
    required String captcha,
  }) async {
    final uri = _gravameUri.replace(
      queryParameters: {
        'placa': placa,
        'renavam': renavam,
        'uf': uf,
        'captcha': captcha,
      },
    );

    final response = await _client.get(uri);

    if (response.statusCode != 200) {
      throw GravameException(
        _extractErrorMessage(response.body) ??
            'Falha ao consultar gravame (HTTP ${response.statusCode}).',
      );
    }

    try {
      final dynamic decoded = jsonDecode(response.body);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      if (decoded is Map) {
        return decoded.map(
          (key, dynamic value) => MapEntry(key.toString(), value),
        );
      }
    } catch (_) {
      // fall-through handled below
    }

    throw GravameException('Resposta do gravame em formato inválido.');
  }

  String? _extractErrorMessage(String body) {
    try {
      final dynamic decoded = jsonDecode(body);
      if (decoded is Map) {
        final message = decoded['message'];
        if (message is String && message.trim().isNotEmpty) {
          return message.trim();
        }
      }
    } catch (_) {
      return null;
    }
    return null;
  }
}
