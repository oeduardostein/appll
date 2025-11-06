import 'dart:convert';

import 'package:http/http.dart' as http;

class CepException implements Exception {
  CepException(this.message);

  final String message;

  @override
  String toString() => 'CepException: $message';
}

class CepAddress {
  const CepAddress({
    required this.cep,
    required this.street,
    required this.neighborhood,
    required this.city,
    required this.state,
    required this.complement,
  });

  final String? cep;
  final String? street;
  final String? neighborhood;
  final String? city;
  final String? state;
  final String? complement;

  factory CepAddress.fromJson(Map<String, dynamic> json) {
    return CepAddress(
      cep: json['cep'] as String?,
      street: json['logradouro'] as String?,
      neighborhood: json['bairro'] as String?,
      city: json['cidade'] as String?,
      state: json['uf'] as String?,
      complement: json['complemento'] as String?,
    );
  }
}

class CepService {
  CepService({
    http.Client? httpClient,
    String? baseUrl,
  })  : _client = httpClient ?? http.Client(),
        _baseUrl = _sanitizeBaseUrl(
          baseUrl ??
              const String.fromEnvironment(
                'BACKEND_BASE_URL',
                defaultValue: 'https://applldespachante.skalacode.com',
              ),
        );

  final http.Client _client;
  final String _baseUrl;

  static String _sanitizeBaseUrl(String baseUrl) {
    if (baseUrl.endsWith('/')) {
      return baseUrl.substring(0, baseUrl.length - 1);
    }
    return baseUrl;
  }

  Uri _buildUri(String path, [Map<String, String>? query]) {
    return Uri.parse('$_baseUrl$path').replace(queryParameters: query);
  }

  Future<CepAddress> lookup(String cep) async {
    final sanitized = cep.replaceAll(RegExp(r'[^0-9]'), '');
    final response = await _client.get(
      _buildUri('/api/cep', {'cep': sanitized}),
      headers: const {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    );

    final body = response.body.isNotEmpty ? jsonDecode(response.body) : {};

    if (response.statusCode == 200) {
      if (body is Map<String, dynamic>) {
        return CepAddress.fromJson(body);
      }
      throw CepException('Resposta inválida do serviço de CEP.');
    }

    if (body is Map<String, dynamic>) {
      final message = body['message'];
      if (message is String && message.trim().isNotEmpty) {
        throw CepException(message);
      }
    }

    throw CepException(
      'Não foi possível consultar o CEP (HTTP ${response.statusCode}).',
    );
  }
}
