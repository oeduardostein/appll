import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/pesquisa_models.dart';
import 'auth_service.dart';

class PesquisaException implements Exception {
  PesquisaException(this.message);

  final String message;

  @override
  String toString() => 'PesquisaException: $message';
}

class PesquisaService {
  PesquisaService({http.Client? client, AuthService? authService})
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

  Map<String, String> _authorizedHeaders() {
    final token = _authService.session?.token;
    if (token == null || token.isEmpty) {
      throw PesquisaException('Sessão inválida.');
    }

    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  Future<void> salvarPesquisa({
    required String nome,
    String? placa,
    String? renavam,
    String? chassi,
    String? opcaoPesquisa,
  }) async {
    final uri = _buildUri('/api/pesquisas');
    final response = await _client.post(
      uri,
      headers: _authorizedHeaders(),
      body: jsonEncode(
        {
          'nome': nome,
          if (placa != null && placa.isNotEmpty) 'placa': placa,
          if (renavam != null && renavam.isNotEmpty) 'renavam': renavam,
          if (chassi != null && chassi.isNotEmpty) 'chassi': chassi,
          if (opcaoPesquisa != null && opcaoPesquisa.isNotEmpty)
            'opcao_pesquisa': opcaoPesquisa,
        },
      ),
    );

    if (response.statusCode == 201) {
      return;
    }

    final message = _extractMessage(response.body) ??
        'Falha ao registrar a pesquisa (HTTP ${response.statusCode}).';
    throw PesquisaException(message);
  }

  Future<List<PesquisaResumo>> listarRecentes() async {
    final uri = _buildUri('/api/pesquisas');
    final response = await _client.get(
      uri,
      headers: _authorizedHeaders(),
    );

    if (response.statusCode != 200) {
      final message = _extractMessage(response.body) ??
          'Falha ao carregar pesquisas (HTTP ${response.statusCode}).';
      throw PesquisaException(message);
    }

    return _parsePesquisaList(response.body);
  }

  Future<List<PesquisaResumo>> listarUltimoMes() async {
    final uri = _buildUri('/api/pesquisas/ultimo-mes');
    final response = await _client.get(
      uri,
      headers: _authorizedHeaders(),
    );

    if (response.statusCode != 200) {
      final message = _extractMessage(response.body) ??
          'Falha ao carregar pesquisas do último mês (HTTP ${response.statusCode}).';
      throw PesquisaException(message);
    }

    return _parsePesquisaList(response.body);
  }

  String? _extractMessage(String body) {
    if (body.isEmpty) return null;
    try {
      final dynamic decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        final message = decoded['message'];
        if (message is String && message.trim().isNotEmpty) {
          return message.trim();
        }
        final errors = decoded['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) {
            return first.first.toString();
          }
        }
      }
    } catch (_) {
      return null;
    }
    return null;
  }

  List<PesquisaResumo> _parsePesquisaList(String body) {
    try {
      final dynamic decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        final data = decoded['data'];
        if (data is List) {
          return data
              .whereType<Map>()
              .map(
                (item) => PesquisaResumo.fromJson(
                  item.map(
                    (key, dynamic value) => MapEntry(key.toString(), value),
                  ),
                ),
              )
              .toList(growable: false);
        }
      }
      throw const FormatException();
    } catch (_) {
      throw PesquisaException('Resposta inválida ao carregar pesquisas.');
    }
  }
}
