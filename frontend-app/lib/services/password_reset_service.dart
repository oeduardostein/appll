import 'dart:convert';

import 'package:http/http.dart' as http;

import 'auth_service.dart';

class PasswordResetService {
  PasswordResetService({http.Client? httpClient, String? baseUrl})
      : _client = httpClient ?? http.Client(),
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

  Uri _buildUri(String path) {
    return Uri.parse('$_baseUrl$path');
  }

  Map<String, String> _jsonHeaders() {
    return const {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    };
  }

  Future<String> requestCode({required String email}) async {
    final response = await _client.post(
      _buildUri('/api/password/forgot'),
      headers: _jsonHeaders(),
      body: jsonEncode({'email': email}),
    );

    return _handleResponse(response);
  }

  Future<String> resetPassword({
    required String email,
    required String code,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await _client.post(
      _buildUri('/api/password/reset'),
      headers: _jsonHeaders(),
      body: jsonEncode({
        'email': email,
        'codigo': code,
        'password': password,
        'password_confirmation': passwordConfirmation,
      }),
    );

    return _handleResponse(response);
  }

  String _handleResponse(http.Response response) {
    final dynamic decoded = response.body.isNotEmpty
        ? jsonDecode(response.body)
        : <String, dynamic>{};

    if (response.statusCode >= 200 && response.statusCode < 300) {
      if (decoded is Map<String, dynamic>) {
        final message = decoded['message'];
        if (message is String && message.trim().isNotEmpty) {
          return message;
        }
      }
      return 'Solicitação concluída com sucesso.';
    }

    if (response.statusCode == 422) {
      throw AuthException(_extractValidationErrors(decoded));
    }

    throw AuthException(
      decoded is Map<String, dynamic> && decoded['message'] != null
          ? decoded['message'].toString()
          : 'Erro inesperado (${response.statusCode}). Tente novamente.',
    );
  }

  String _extractValidationErrors(dynamic decoded) {
    if (decoded is Map<String, dynamic>) {
      final errors = decoded['errors'];
      if (errors is Map<String, dynamic>) {
        final messages = <String>[];
        for (final value in errors.values) {
          if (value is List) {
            messages.addAll(
              value.whereType<String>(),
            );
          } else if (value is String) {
            messages.add(value);
          }
        }
        if (messages.isNotEmpty) {
          return messages.join('\n');
        }
      }
      if (decoded['message'] is String && decoded['message'].trim().isNotEmpty) {
        return decoded['message'] as String;
      }
    }
    return 'Dados inválidos. Verifique as informações e tente novamente.';
  }
}
