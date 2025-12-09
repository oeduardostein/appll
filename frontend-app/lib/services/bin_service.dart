import 'dart:convert';

import 'package:http/http.dart' as http;

class BinException implements Exception {
  BinException(this.message);

  final String message;

  @override
  String toString() => 'BinException: $message';
}

class BinService {
  BinService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  static final Uri _captchaUri = Uri.parse(
    'https://applldespachante.skalacode.com/api/captcha',
  );

  static final Uri _consultaUri = Uri.parse(
    'https://applldespachante.skalacode.com/api/bin',
  );

  Future<String> fetchCaptcha() async {
    final response = await _client.get(_captchaUri);

    if (response.statusCode != 200) {
      throw BinException(
        'Não foi possível carregar o captcha (HTTP ${response.statusCode}).',
      );
    }

    final contentType = response.headers['content-type'] ?? '';
    final bytes = response.bodyBytes;

    if (bytes.isNotEmpty &&
        (contentType.contains('image') || contentType.contains('octet-stream'))) {
      return base64Encode(bytes);
    }

    final body = response.body.trim();
    if (body.isEmpty) {
      throw BinException('Resposta vazia ao carregar o captcha.');
    }

    try {
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        final value = decoded['captcha'] ?? decoded['data'];
        if (value is String && value.isNotEmpty) {
          return value;
        }
      } else if (decoded is String && decoded.isNotEmpty) {
        return decoded;
      }
    } catch (_) {
      if (body.isNotEmpty) {
        return body;
      }
    }

    throw BinException('Formato de captcha inválido.');
  }

  Future<Map<String, dynamic>> consultar({
    String? placa,
    String? renavam,
    String? chassi,
    required String captcha,
  }) async {
    final normalizedPlaca = placa?.trim().toUpperCase() ?? '';
    final normalizedRenavam = renavam?.trim() ?? '';
    final normalizedChassi = chassi?.trim().toUpperCase() ?? '';
    final normalizedCaptcha = captcha.trim().toUpperCase();

    if (normalizedCaptcha.isEmpty) {
      throw BinException('Informe o captcha para consultar.');
    }

    final hasChassi = normalizedChassi.isNotEmpty;
    final hasPlateRenavam =
        normalizedPlaca.isNotEmpty && normalizedRenavam.isNotEmpty;

    if (!hasChassi && !hasPlateRenavam) {
      throw BinException(
        'Informe placa e renavam ou chassi para consultar.',
      );
    }

    final params = <String, String>{
      'captcha': normalizedCaptcha,
    };

    if (hasPlateRenavam) {
      params['placa'] = normalizedPlaca;
      params['renavam'] = normalizedRenavam;
    }

    if (hasChassi) {
      params['chassi'] = normalizedChassi;
    }

    final uri = _consultaUri.replace(queryParameters: params);

    final response = await _client.get(uri);

    if (response.statusCode != 200) {
      final message = _extractMessage(response.body) ??
          'Falha ao consultar BIN (HTTP ${response.statusCode}).';
      throw BinException(message);
    }

    final body = response.body.trim();
    if (body.isEmpty) {
      throw BinException('Resposta vazia da consulta BIN.');
    }

    if (_looksLikeHtml(body)) {
      return {'html': body};
    }

    try {
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      if (decoded is List) {
        return {'items': decoded};
      }
      if (decoded is String) {
        return {'message': decoded};
      }
    } catch (_) {
      // Conteúdo não-JSON, retornar como mensagem simples.
      return {'message': body};
    }

    throw BinException('Formato de resposta da consulta BIN inválido.');
  }

  bool _looksLikeHtml(String body) {
    final lower = body.toLowerCase();
    return lower.contains('<html') || lower.contains('<!doctype');
  }

  String? _extractMessage(String? body) {
    if (body == null || body.isEmpty) {
      return null;
    }

    try {
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        final message = decoded['message'] ?? decoded['error'] ?? decoded['status'];
        if (message is String && message.trim().isNotEmpty) {
          return message.trim();
        }
      } else if (decoded is String && decoded.trim().isNotEmpty) {
        return decoded.trim();
      }
    } catch (_) {
      // ignore
    }

    return body;
  }
}
