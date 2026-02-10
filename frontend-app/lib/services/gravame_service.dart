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

  static String _sanitizeAlnum(String value) {
    return value.replaceAll(RegExp(r'[^A-Za-z0-9]'), '').toUpperCase();
  }

  static final Uri _gravameUri = Uri.parse(
    'https://applldespachante.skalacode.com/api/gravame',
  );

  Future<Map<String, dynamic>> consultar({
    String? placa,
    String? chassi,
    required String captcha,
  }) async {
    final plateValue = _sanitizeAlnum(placa ?? '');
    final chassiValue = _sanitizeAlnum(chassi ?? '');
    final captchaValue = _sanitizeAlnum(captcha);
    if (plateValue.isEmpty && chassiValue.isEmpty) {
      throw GravameException('Informe a placa ou o chassi para consultar.');
    }

    final uri = _gravameUri.replace(
      queryParameters: {
        'captcha': captchaValue,
        if (plateValue.isNotEmpty) 'placa': plateValue,
        if (chassiValue.isNotEmpty) 'chassi': chassiValue,
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
