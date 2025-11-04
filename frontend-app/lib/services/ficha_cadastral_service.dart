import 'dart:convert';

import 'package:http/http.dart' as http;

class FichaCadastralException implements Exception {
  FichaCadastralException(this.message);

  final String message;

  @override
  String toString() => 'FichaCadastralException: $message';
}

class FichaCadastralService {
  FichaCadastralService({http.Client? client})
      : _client = client ?? http.Client();

  final http.Client _client;

  static final Uri _consultaUri = Uri.parse(
    'https://applldespachante.skalacode.com/api/ficha-cadastral/consulta',
  );

  static final Uri _andamentoUri = Uri.parse(
    'https://applldespachante.skalacode.com/api/ficha-cadastral/andamento',
  );

  Future<Map<String, dynamic>> consultarFicha({
    required String placa,
    required String captcha,
  }) async {
    final uri = _consultaUri.replace(
      queryParameters: {
        'placa': placa,
        'captcha': captcha,
      },
    );

    final response = await _client.get(uri);

    if (response.statusCode != 200) {
      throw FichaCadastralException(
        _extractMessage(response.body) ??
            'Falha ao consultar ficha cadastral (HTTP ${response.statusCode}).',
      );
    }

    return _decodeBody(response.body);
  }

  Future<Map<String, dynamic>> consultarAndamento({
    required String numeroFicha,
    required String anoFicha,
    required String captcha,
    String? placa,
  }) async {
    final query = {
      'numero_ficha': numeroFicha,
      'ano_ficha': anoFicha,
      'captcha': captcha,
    };
    if (placa != null && placa.isNotEmpty) {
      query['placa'] = placa;
    }

    final uri = _andamentoUri.replace(queryParameters: query);
    final response = await _client.get(uri);

    if (response.statusCode != 200) {
      throw FichaCadastralException(
        _extractMessage(response.body) ??
            'Falha ao consultar andamento do processo (HTTP ${response.statusCode}).',
      );
    }

    return _decodeBody(response.body);
  }

  Map<String, dynamic> _decodeBody(String body) {
    try {
      final dynamic decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      if (decoded is Map) {
        return decoded.map(
          (key, dynamic value) => MapEntry(key.toString(), value),
        );
      }
    } catch (_) {
      // swallow to throw below
    }
    throw FichaCadastralException('Resposta em formato inválido.');
  }

  String? _extractMessage(String body) {
    try {
      final dynamic decoded = jsonDecode(body);
      if (decoded is Map && decoded['message'] is String) {
        final message = decoded['message'] as String;
        if (message.trim().isNotEmpty) {
          return message.trim();
        }
      }
    } catch (_) {
      return null;
    }
    return null;
  }
}
