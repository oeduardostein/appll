import 'dart:convert';
import 'dart:typed_data';

import 'package:http/http.dart' as http;

import 'auth_service.dart';

class AtpvException implements Exception {
  AtpvException(this.message);

  final String message;

  @override
  String toString() => 'AtpvException: $message';
}

class AtpvService {
  AtpvService({http.Client? client, AuthService? authService})
      : _client = client ?? http.Client(),
        _authService = authService ?? AuthService();

  final http.Client _client;
  final AuthService _authService;

  Uri _buildUri(String path) {
    final baseUrl = const String.fromEnvironment(
      'BACKEND_BASE_URL',
      defaultValue: 'https://applldespachante.skalacode.com',
    );
    final sanitized = baseUrl.endsWith('/')
        ? baseUrl.substring(0, baseUrl.length - 1)
        : baseUrl;

    return Uri.parse('$sanitized$path');
  }

  Map<String, String> _authorizedJsonHeaders(String token) {
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  Map<String, String> _authorizedPdfHeaders(String token) {
    return {
      'Accept': 'application/pdf,application/json',
      'Authorization': 'Bearer $token',
    };
  }

  String _ensureToken() {
    final token = _authService.session?.token;
    if (token == null || token.isEmpty) {
      throw AtpvException('Sessão inválida. Faça login novamente.');
    }
    return token;
  }

  String _sanitizePlate(String value) {
    return value.replaceAll(RegExp(r'[^A-Za-z0-9]'), '').toUpperCase();
  }

  Future<Map<String, dynamic>> consultarIntencaoVenda({
    required String renavam,
    required String placa,
    required String captcha,
  }) async {
    final sanitizedRenavam = renavam.trim();
    final sanitizedPlaca = _sanitizePlate(placa);
    final sanitizedCaptcha = captcha.trim().toUpperCase();

    if (sanitizedRenavam.isEmpty ||
        sanitizedPlaca.isEmpty ||
        sanitizedCaptcha.isEmpty) {
      throw AtpvException('Informe placa, renavam e captcha para consultar.');
    }

    final uri = _buildUri('/api/intencao-venda');
    final payload = jsonEncode({
      'renavam': sanitizedRenavam,
      'placa': sanitizedPlaca,
      'captcha': sanitizedCaptcha,
      'estado_intencao_venda': '0',
    });

    final response = await _client.post(
      uri,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: payload,
    );

    final body = response.body.trim();

    if (response.statusCode != 200) {
      final message = _extractMessage(body) ??
          'Falha ao consultar intenção de venda (HTTP ${response.statusCode}).';
      throw AtpvException(message);
    }

    if (body.isEmpty) {
      throw AtpvException('Resposta vazia da consulta.');
    }

    try {
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        final ok = decoded['ok'];
        if (ok is bool && !ok) {
          final message = decoded['message']?.toString();
          throw AtpvException(message ?? 'Falha ao consultar intenção de venda.');
        }
        final data = decoded['data'];
        if (data is Map<String, dynamic>) {
          return data;
        }
        return decoded;
      }
    } catch (_) {
      // ignored
    }

    throw AtpvException('Resposta inválida ao consultar intenção de venda.');
  }

  Future<Map<String, dynamic>> emitirAtpv({
    required String renavam,
    required String placa,
    required String captcha,
    String? chassi,
    String? hodometro,
    String? emailProprietario,
    String? cpfCnpjProprietario,
    required String cpfCnpjComprador,
    required String nomeComprador,
    String? emailComprador,
    required String uf,
    String? valorVenda,
    String? cepComprador,
    String? municipioComprador,
    String? bairroComprador,
    String? logradouroComprador,
    String? numeroComprador,
    String? complementoComprador,
    String? municipioCodigo,
    String? opcaoPesquisaProprietario,
    required String opcaoPesquisaComprador,
  }) async {
    final token = _ensureToken();
    final uri = _buildUri('/api/emissao-atpv');
    final normalizedPlate = _sanitizePlate(placa);

    final payload = {
      'renavam': renavam,
      'placa': normalizedPlate,
      'captcha': captcha,
      'uf': uf,
      'cpf_cnpj_comprador': cpfCnpjComprador,
      'nome_comprador': nomeComprador,
      if (chassi != null && chassi.isNotEmpty) 'chassi': chassi,
      if (hodometro != null && hodometro.isNotEmpty) 'hodometro': hodometro,
      if (emailProprietario != null && emailProprietario.isNotEmpty)
        'email_proprietario': emailProprietario,
      if (cpfCnpjProprietario != null && cpfCnpjProprietario.isNotEmpty)
        'cpf_cnpj_proprietario': cpfCnpjProprietario,
      if (emailComprador != null && emailComprador.isNotEmpty)
        'email_comprador': emailComprador,
      if (valorVenda != null && valorVenda.isNotEmpty)
        'valor_venda': valorVenda,
      if (cepComprador != null && cepComprador.isNotEmpty)
        'cep_comprador': cepComprador,
      if (municipioComprador != null && municipioComprador.isNotEmpty)
        'municipio_comprador': municipioComprador,
      if (bairroComprador != null && bairroComprador.isNotEmpty)
        'bairro_comprador': bairroComprador,
      if (logradouroComprador != null && logradouroComprador.isNotEmpty)
        'logradouro_comprador': logradouroComprador,
      if (numeroComprador != null && numeroComprador.isNotEmpty)
        'numero_comprador': numeroComprador,
      if (complementoComprador != null && complementoComprador.isNotEmpty)
        'complemento_comprador': complementoComprador,
      if (municipioCodigo != null && municipioCodigo.isNotEmpty)
        'municipio2': municipioCodigo,
      if (opcaoPesquisaProprietario != null &&
          opcaoPesquisaProprietario.isNotEmpty)
        'opcao_pesquisa_proprietario': opcaoPesquisaProprietario,
      'opcao_pesquisa_comprador': opcaoPesquisaComprador,
      'method': 'pesquisar',
    };

    final response = await _client.post(
      uri,
      headers: _authorizedJsonHeaders(token),
      body: jsonEncode(payload),
    );

    final body = response.body.trim();

    if (response.statusCode != 200) {
      final message = _extractMessage(body) ??
          'Falha ao emitir a ATPV-e (HTTP ${response.statusCode}).';
      throw AtpvException(message);
    }

    if (body.isEmpty) {
      throw AtpvException('Resposta vazia da emissão.');
    }

    try {
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
    } catch (_) {
      // ignored
    }

    throw AtpvException('Resposta inválida ao emitir a ATPV-e.');
  }

  Future<Uint8List> baixarPdf({
    required String placa,
    required String renavam,
    required String captcha,
  }) async {
    final token = _ensureToken();
    final normalizedPlate = _sanitizePlate(placa);

    final uri = _buildUri('/api/emissao-atpv/pdf').replace(
      queryParameters: {
        'placa': normalizedPlate,
        'renavam': renavam,
        'captcha': captcha,
      },
    );

    final response = await _client.get(
      uri,
      headers: _authorizedPdfHeaders(token),
    );

    final contentType = response.headers['content-type'] ?? '';

    if (response.statusCode == 200 &&
        (contentType.contains('application/pdf') ||
            contentType.contains('application/octet-stream'))) {
      final bytes = response.bodyBytes;
      if (bytes.isEmpty) {
        throw AtpvException('O PDF retornou vazio.');
      }
      return Uint8List.fromList(bytes);
    }

    final message = _extractMessage(response.body) ??
        'Falha ao baixar o PDF da ATPV-e (HTTP ${response.statusCode}).';
    throw AtpvException(message);
  }

  Future<Map<String, dynamic>> registrarTipoAssinatura({
    required int registroId,
    required bool assinaturaDigital,
  }) async {
    final token = _ensureToken();
    final uri = _buildUri('/api/emissao-atpv/assinatura');

    final response = await _client.post(
      uri,
      headers: _authorizedJsonHeaders(token),
      body: jsonEncode({
        'registro_id': registroId,
        'assinatura_digital': assinaturaDigital,
      }),
    );

    final body = response.body.trim();

    if (response.statusCode != 200) {
      final message = _extractMessage(body) ??
          'Falha ao registrar a assinatura (HTTP ${response.statusCode}).';
      throw AtpvException(message);
    }

    if (body.isEmpty) {
      throw AtpvException('Resposta vazia ao registrar a assinatura.');
    }

    try {
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
    } catch (_) {
      // ignore
    }

    throw AtpvException('Resposta inválida ao registrar a assinatura.');
  }

  String? _extractMessage(String? body) {
    if (body == null || body.isEmpty) {
      return null;
    }

    try {
      final dynamic decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        final message = decoded['message'];
        if (message is String && message.trim().isNotEmpty) {
          return message.trim();
        }
        final errors = decoded['detalhes'] ?? decoded['errors'];
        if (errors is List && errors.isNotEmpty) {
          return errors.first.toString();
        }
      }
    } catch (_) {
      return null;
    }
    return null;
  }
}
