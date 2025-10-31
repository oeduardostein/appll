import 'dart:convert';

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
      throw BaseEstadualException(
        'Falha ao consultar base estadual (HTTP ${response.statusCode}).',
      );
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
      throw BaseEstadualException(
        'Falha ao consultar base de outros estados (HTTP ${response.statusCode}).',
      );
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
    String? placa,
    String? chassi,
    String? renavam,
  }) async {
    final opcao = origin.toUpperCase() == 'RENAJUD' ? '2' : '1';
    final params = <String, String>{
      'opcaoPesquisa': opcao,
      'captcha': captcha,
    };

    final sanitizedPlate = placa?.trim().toUpperCase() ?? '';
    final sanitizedChassi = chassi?.trim().toUpperCase() ?? '';

    params['placa'] = sanitizedPlate;
    if (sanitizedChassi.isNotEmpty) {
      params['chassi'] = sanitizedChassi;
    }

    if (renavam != null && renavam.trim().isNotEmpty) {
      params['renavam'] = renavam.trim();
    }

    final uri = Uri.parse(
      'https://applldespachante.skalacode.com/api/bloqueios-ativos',
    ).replace(
      queryParameters: params,
    );

    final response = await _client.get(uri);

    if (response.statusCode != 200) {
      throw BaseEstadualException(
        'Falha ao consultar bloqueios ativos (HTTP ${response.statusCode}).',
      );
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

  Future<Map<String, dynamic>> emitirCrlv({
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

    if (response.statusCode != 200) {
      throw BaseEstadualException(
        'Falha ao emitir CRLV-e (HTTP ${response.statusCode}).',
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
}
