import 'dart:convert';
import 'dart:typed_data';

import 'package:http/http.dart' as http;

class BaseEstadualException implements Exception {
  BaseEstadualException(this.message);

  final String message;

  @override
  String toString() => 'BaseEstadualException: $message';
}

class BaseEstadualService {
  BaseEstadualService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  static final Uri _captchaUri = Uri.parse(
    'https://applldespachante.skalacode.com/api/captcha',
  );

  static final Uri _consultaBaseUri = Uri.parse(
    'https://applldespachante.skalacode.com/api/base-estadual',
  );

  static final Uri _consultaBaseOutrosEstadosUri = Uri.parse(
    'https://applldespachante.skalacode.com/api/another-base-estadual',
  );

  Future<String> fetchCaptcha() async {
    final response = await _client.get(_captchaUri);

    if (response.statusCode != 200) {
      throw BaseEstadualException(
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
      throw BaseEstadualException('Resposta vazia ao carregar o captcha.');
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

    throw BaseEstadualException('Formato de captcha inválido.');
  }

  Future<Map<String, dynamic>> consultar({
    required String placa,
    required String renavam,
    required String captcha,
  }) async {
    final uri = _consultaBaseUri.replace(
      queryParameters: {
        'placa': placa,
        'renavam': renavam,
        'captcha': captcha,
      },
    );

    final response = await _client.get(uri);

    if (response.statusCode != 200) {
      final message = _extractMessage(response.body) ??
          'Falha ao consultar base estadual (HTTP ${response.statusCode}).';
      throw BaseEstadualException(message);
    }

    final body = response.body.trim();
    if (body.isEmpty) {
      throw BaseEstadualException('Resposta vazia da consulta.');
    }

    final lowerBody = body.toLowerCase();
    if (lowerBody.contains('<html') || lowerBody.contains('<!doctype')) {
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
      // If content is not JSON, encapsulate as plain text.
      return {'message': body};
    }

    throw BaseEstadualException('Formato de resposta da consulta inválido.');
  }

  Future<Map<String, dynamic>> consultarOutrosEstados({
    required String chassi,
    required String uf,
    required String captcha,
  }) async {
    final uri = _consultaBaseOutrosEstadosUri.replace(
      queryParameters: {
        'chassi': chassi,
        'uf': uf,
        'captcha': captcha,
      },
    );

    final response = await _client.get(uri);

    if (response.statusCode != 200) {
      final message = _extractMessage(response.body) ??
          'Falha ao consultar base de outros estados (HTTP ${response.statusCode}).';
      throw BaseEstadualException(message);
    }

    final body = response.body.trim();
    if (body.isEmpty) {
      throw BaseEstadualException('Resposta vazia da consulta.');
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
      return {'message': body};
    }

    throw BaseEstadualException('Formato de resposta da consulta inválido.');
  }

  Future<Map<String, dynamic>> consultarBloqueiosAtivos({
    required String origin,
    required String captcha,
    required String chassi,
    required String opcaoPesquisa,
  }) async {
    final params = <String, String>{
      'opcaoPesquisa': opcaoPesquisa,
      'captcha': captcha,
      'chassi': chassi.trim().toUpperCase(),
    };

    final uri = Uri.parse(
      'https://applldespachante.skalacode.com/api/bloqueios-ativos',
    ).replace(
      queryParameters: params,
    );

    final response = await _client.get(uri);

    if (response.statusCode != 200) {
      final message = _extractMessage(response.body) ??
          'Falha ao consultar bloqueios ativos (HTTP ${response.statusCode}).';
      throw BaseEstadualException(message);
    }

    final body = response.body.trim();
    if (body.isEmpty) {
      throw BaseEstadualException('Resposta vazia da consulta.');
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
      return {'message': body};
    }

    throw BaseEstadualException('Formato de resposta da consulta inválido.');
  }

  Future<Map<String, dynamic>> emitirAtpv({
    required String placa,
    required String renavam,
    required String captchaResponse,
  }) async {
    final uri = Uri.parse(
      'https://applldespachante.skalacode.com/api/emissao-atpv',
    ).replace(
      queryParameters: {
        'placa': placa,
        'renavam': renavam,
        'captchaResponse': captchaResponse,
      },
    );

    final response = await _client.get(uri);

    if (response.statusCode != 200) {
      throw BaseEstadualException(
        'Falha ao emitir ATPV-e (HTTP ${response.statusCode}).',
      );
    }

    final body = response.body.trim();
    if (body.isEmpty) {
      throw BaseEstadualException('Resposta vazia da emissão.');
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
      return {'message': body};
    }

    throw BaseEstadualException('Formato de resposta da emissão inválido.');
  }

  Future<Uint8List> emitirCrlv({
    required String placa,
    required String renavam,
    required String cpf,
    required String cnpj,
    required String captchaResponse,
    required String opcaoPesquisa,
  }) async {
    final uri = Uri.parse(
      'https://applldespachante.skalacode.com/api/emissao-crlv',
    ).replace(
      queryParameters: {
        'placa': placa,
        'renavam': renavam,
        'cpf': cpf,
        'cnpj': cnpj,
        'captchaResponse': captchaResponse,
        'opcaoPesquisa': opcaoPesquisa,
      },
    );

    final response = await _client.get(uri);

    final contentType = response.headers['content-type'] ?? '';
    final bytes = response.bodyBytes;

    final isPdf = contentType.contains('application/pdf') ||
        contentType.contains('application/octet-stream');

    if (response.statusCode == 200 && isPdf) {
      if (bytes.isEmpty) {
        throw BaseEstadualException('O PDF retornou vazio.');
      }
      return Uint8List.fromList(bytes);
    }

    final body = response.body.trim();

    if (response.statusCode != 200) {
      final message = _extractMessage(body) ??
          'Falha ao emitir CRLV-e (HTTP ${response.statusCode}).';
      throw BaseEstadualException(message);
    }

    if (body.isEmpty) {
      throw BaseEstadualException('Resposta vazia da emissão.');
    }

    final message = _extractMessage(body);
    if (message != null) {
      throw BaseEstadualException(message);
    }

    throw BaseEstadualException('Formato de resposta da emissão inválido.');
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
      // ignore, content não é JSON.
    }

    return body;
  }
}
