import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/renainf_models.dart';

class RenainfException implements Exception {
  RenainfException(this.message);

  final String message;

  @override
  String toString() => 'RenainfException: $message';
}

class RenainfService {
  RenainfService({
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

  Future<RenainfResult> consultar({
    required String plate,
    required int statusCode,
    required String statusLabel,
    required String uf,
    required DateTime startDate,
    required DateTime endDate,
    required String captcha,
  }) async {
    final uri = _buildUri(
      '/api/renainf',
      queryParameters: {
        'placa': plate,
        'status': statusCode.toString(),
        'data_inicial': _formatRequestDate(startDate),
        'data_final': _formatRequestDate(endDate),
        'uf': uf,
        'captcha': captcha,
      },
    );

    final response = await _client.get(
      uri,
      headers: _jsonHeaders(),
    );

    if (response.statusCode != 200) {
      final message = _extractErrorMessage(response.body) ??
          'Falha ao consultar o RENAINF (HTTP ${response.statusCode}).';
      throw RenainfException(message);
    }

    Map<String, dynamic>? decoded;
    try {
      final dynamic json = jsonDecode(response.body);
      decoded = _asMap(json);
      if (decoded == null) {
        throw const FormatException();
      }
    } catch (_) {
      throw RenainfException('Resposta do RENAINF em formato inválido.');
    }

    final dataMap = _asMap(decoded['data']);
    final effectivePayload =
        dataMap != null && dataMap.isNotEmpty ? dataMap : decoded;

    return RenainfResult.fromJson(
      effectivePayload,
      requestPlate: plate,
      requestStatusCode: statusCode,
      requestStatusLabel: statusLabel,
      requestUf: uf,
      requestStartDate: startDate,
      requestEndDate: endDate,
    );
  }

  Map<String, String> _jsonHeaders() {
    return const {
      'Accept': 'application/json',
    };
  }

  static String _formatRequestDate(DateTime date) {
    final year = date.year.toString().padLeft(4, '0');
    final month = date.month.toString().padLeft(2, '0');
    final day = date.day.toString().padLeft(2, '0');
    return '$year-$month-$day';
  }

  Uri _buildUri(String path, {Map<String, String>? queryParameters}) {
    final uri = Uri.parse('$_baseUrl$path');
    if (queryParameters == null) return uri;
    return uri.replace(queryParameters: queryParameters);
  }

  static String _sanitizeBaseUrl(String baseUrl) {
    if (baseUrl.endsWith('/')) {
      return baseUrl.substring(0, baseUrl.length - 1);
    }
    return baseUrl;
  }

  static Map<String, dynamic>? _asMap(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) {
      return value.map(
        (key, dynamic val) => MapEntry(key.toString(), val),
      );
    }
    return null;
  }

  String? _extractErrorMessage(String body) {
    try {
      final dynamic payload = jsonDecode(body);
      final map = _asMap(payload);
      if (map == null) return null;

      final message = map['message'] ?? map['error'];
      if (message is String && message.trim().isNotEmpty) {
        return message.trim();
      }

      final errors = map['errors'];
      if (errors is Map) {
        final buffer = <String>[];
        errors.forEach((_, dynamic value) {
          if (value is List && value.isNotEmpty) {
            buffer.add(value.first.toString());
          } else if (value != null) {
            buffer.add(value.toString());
          }
        });
        if (buffer.isNotEmpty) {
          return buffer.join('\n');
        }
      } else if (errors is List && errors.isNotEmpty) {
        return errors.first.toString();
      }
    } catch (_) {
      return null;
    }
    return null;
  }
}
