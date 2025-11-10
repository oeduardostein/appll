import 'dart:convert';

import 'package:http/http.dart' as http;

class AuthException implements Exception {
  AuthException(this.message);

  final String message;

  @override
  String toString() => 'AuthException: $message';
}

class AuthUser {
  const AuthUser({
    required this.id,
    required this.username,
    required this.email,
  });

  final int id;
  final String username;
  final String email;

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    return AuthUser(
      id: json['id'] as int,
      username: json['username'] as String,
      email: json['email'] as String,
    );
  }
}

class AuthSession {
  const AuthSession({
    required this.token,
    required this.user,
  });

  final String token;
  final AuthUser user;

  AuthSession copyWith({String? token, AuthUser? user}) {
    return AuthSession(
      token: token ?? this.token,
      user: user ?? this.user,
    );
  }
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

  static AuthSession? _sharedSession;
  static List<String> _cachedPermissions = [];

  AuthSession? get session => _sharedSession;
  List<String> get permissions => List.unmodifiable(_cachedPermissions);

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

  Map<String, String> _authorizedHeaders(String token) {
    return {
      ..._jsonHeaders(),
      'Authorization': 'Bearer $token',
    };
  }

  Future<AuthSession> register({
    required String username,
    required String email,
    required String password,
    required String passwordConfirmation,
    required bool acceptedPrivacyPolicy,
  }) async {
    final uri = _buildUri('/api/auth/register');
    final response = await _client.post(
      uri,
      headers: _jsonHeaders(),
      body: jsonEncode({
        'username': username,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'accepted_privacy_policy': acceptedPrivacyPolicy,
      }),
    );

    final payload = _handleResponse(response);
    final session = _createOrUpdateSession(payload);
    await fetchPermissions();
    return session;
  }

  Future<AuthSession> login({
    required String identifier,
    required String password,
  }) async {
    final uri = _buildUri('/api/auth/login');
    final response = await _client.post(
      uri,
      headers: _jsonHeaders(),
      body: jsonEncode({
        'identifier': identifier,
        'password': password,
      }),
    );

    final payload = _handleResponse(response);
    final session = _createOrUpdateSession(payload);
    await fetchPermissions();
    return session;
  }

  Future<void> logout() async {
    final token = _ensureToken();
    final uri = _buildUri('/api/auth/logout');
    final response = await _client.post(
      uri,
      headers: _authorizedHeaders(token),
      body: jsonEncode({}),
    );

    _handleResponse(response);
    clearSession();
  }

  Future<AuthUser> fetchCurrentUser() async {
    final token = _ensureToken();
    final uri = _buildUri('/api/auth/user');
    final response = await _client.get(
      uri,
      headers: _authorizedHeaders(token),
    );

    final payload = _handleResponse(response);
    final userJson = payload['user'];
    if (userJson is! Map<String, dynamic>) {
      throw AuthException('Dados do usuário inválidos.');
    }

    final user = AuthUser.fromJson(userJson);
    _sharedSession =
        _sharedSession?.copyWith(user: user) ?? AuthSession(token: token, user: user);
    return user;
  }

  AuthSession _createOrUpdateSession(Map<String, dynamic> payload) {
    final token = payload['token'];
    final userJson = payload['user'];

    if (token is! String || token.isEmpty) {
      throw AuthException('Token de autenticação ausente.');
    }
    if (userJson is! Map<String, dynamic>) {
      throw AuthException('Dados do usuário inválidos.');
    }

    final user = AuthUser.fromJson(userJson);
    final session = AuthSession(token: token, user: user);
    _sharedSession = session;
    return session;
  }

  void clearSession() {
    _sharedSession = null;
    _cachedPermissions = [];
  }

  Map<String, dynamic> _handleResponse(http.Response response) {
    final isSuccessStatus =
        response.statusCode == 200 || response.statusCode == 201;

    final dynamic decoded = response.body.isNotEmpty
        ? jsonDecode(response.body)
        : <String, dynamic>{};

    if (isSuccessStatus && decoded is Map<String, dynamic>) {
      final status = decoded['status'];
      if (status == 'success') {
        return decoded;
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

  String _ensureToken() {
    final token = _sharedSession?.token;
    if (token == null || token.isEmpty) {
      throw AuthException('Sessão expirada. Faça login novamente.');
    }
    return token;
  }

  Future<List<String>> fetchPermissions() async {
    final token = _ensureToken();
    final uri = _buildUri('/api/user/permissions');
    final response = await _client.get(
      uri,
      headers: _authorizedHeaders(token),
    );

    final payload = _handleResponse(response);
    var slugs = _extractSlugList(payload['slugs']);
    if (slugs.isEmpty) {
      slugs = _extractSlugList(payload['permissions']);
    }
    _cachedPermissions = slugs;
    return List.unmodifiable(_cachedPermissions);
  }

  List<String> _extractSlugList(dynamic raw) {
    if (raw is Iterable) {
      return raw
          .map((value) {
            if (value is String) return value.trim();
            if (value is Map && value['slug'] != null) {
              return value['slug'].toString().trim();
            }
            return null;
          })
          .whereType<String>()
          .where((slug) => slug.isNotEmpty)
          .toList(growable: false);
    }
    return const [];
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
