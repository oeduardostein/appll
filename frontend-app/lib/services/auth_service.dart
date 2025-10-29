import 'dart:convert';

import 'package:http/http.dart' as http;

class AuthException implements Exception {
  AuthException(this.message);

  final String message;

  @override
  String toString() => 'AuthException: $message';
}

class AuthService {
  AuthService({
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

  Uri _buildUri(String path) {
    return Uri.parse('$_baseUrl$path');
  }

  Future<void> register({
    required String username,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final uri = _buildUri('/api/auth/register');
    final response = await _client.post(
      uri,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'username': username,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
      }),
    );

    _handleResponse(response);
  }

  Future<void> login({
    required String identifier,
    required String password,
  }) async {
    final uri = _buildUri('/api/auth/login');
    final response = await _client.post(
      uri,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'identifier': identifier,
        'password': password,
      }),
    );

    _handleResponse(response);
  }

  void _handleResponse(http.Response response) {
    final isSuccessStatus =
        response.statusCode == 200 || response.statusCode == 201;

    final dynamic decoded = response.body.isNotEmpty
        ? jsonDecode(response.body)
        : <String, dynamic>{};

    if (isSuccessStatus && decoded is Map<String, dynamic>) {
      final status = decoded['status'];
      if (status == 'success') {
        return;
      }
      throw AuthException(
        decoded['message']?.toString() ?? 'Não foi possível completar a ação.',
      );
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
