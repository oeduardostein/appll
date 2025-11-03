import 'dart:math';

class RenainfResult {
  RenainfResult({
    required this.plate,
    required this.statusCode,
    required this.statusLabel,
    required this.uf,
    required this.startDate,
    required this.endDate,
    required this.summary,
    required this.infractions,
    this.sourceTitle,
    this.sourceGeneratedAt,
    this.consulta,
    this.occurrences = const [],
    this.occurrencesCount,
  });

  final String plate;
  final int statusCode;
  final String statusLabel;
  final String uf;
  final DateTime startDate;
  final DateTime endDate;
  final RenainfSummary summary;
  final List<RenainfInfraction> infractions;
  final String? sourceTitle;
  final String? sourceGeneratedAt;
  final RenainfConsulta? consulta;
  final List<RenainfOccurrence> occurrences;
  final int? occurrencesCount;

  factory RenainfResult.fromJson(
    Map<String, dynamic> json, {
    required String requestPlate,
    required int requestStatusCode,
    required String requestStatusLabel,
    required String requestUf,
    required DateTime requestStartDate,
    required DateTime requestEndDate,
  }) {
    final resolvedPlate = _fallbackValue(
      _readString(json, ['plate', 'placa']),
      requestPlate,
    );
    final resolvedStatusCode = _readInt(json, ['status_code', 'status_codigo']) ??
        requestStatusCode;
    final resolvedStatusLabel = _fallbackValue(
      _readString(json, ['status_label', 'statusDescricao', 'status']),
      requestStatusLabel,
    );
    final resolvedUf = _fallbackValue(
      _readString(json, ['uf', 'uf_filtro']),
      requestUf,
    );

    final periodMap =
        _asMap(json['period']) ?? _asMap(json['periodo']) ?? _asMap(json['filtro']);
    final apiStartDate = periodMap != null
        ? _parseDate(periodMap['start'] ?? periodMap['inicio'])
        : null;
    final apiEndDate = periodMap != null
        ? _parseDate(periodMap['end'] ?? periodMap['fim'])
        : null;

    final rootData = _asMap(json['data']);
    final effectiveJson = rootData != null && rootData.isNotEmpty
        ? {...json, ...rootData}
        : json;

    final fonteMap = _asMap(effectiveJson['fonte']);
    final sourceTitle = fonteMap != null
        ? _readString(fonteMap, ['titulo', 'title'])
        : '';
    final sourceGeneratedAt = fonteMap != null
        ? _readString(fonteMap, ['gerado_em', 'geradoEm', 'gerado'])
        : '';

    final consultaMap = _asMap(effectiveJson['consulta']);
    final consulta = consultaMap != null
        ? RenainfConsulta.fromJson(
            consultaMap,
            fallbackPlate: resolvedPlate,
            fallbackUf: resolvedUf,
            fallbackIndicator: resolvedStatusLabel,
          )
        : null;

    final renainfMap = _asMap(effectiveJson['renainf']);
    int? occurrencesCount;
    final occurrences = <RenainfOccurrence>[];
    if (renainfMap != null) {
      occurrencesCount = _readInt(
        renainfMap,
        ['quantidade_ocorrencias', 'quantidade', 'total'],
      );
      final rawOccurrences = _coerceListOfMap(renainfMap['ocorrencias']) ?? [];
      for (final item in rawOccurrences) {
        occurrences.add(RenainfOccurrence.fromJson(item));
      }
      occurrencesCount ??=
          occurrences.isNotEmpty ? occurrences.length : occurrencesCount;
    }

    final summaryJson = _asMap(effectiveJson['summary']) ??
        _asMap(effectiveJson['resumo']) ??
        _asMap(effectiveJson['totals']) ??
        {};

    final infractions = _extractInfractions(effectiveJson)
        .map(
          (item) => RenainfInfraction.fromJson(
            item,
            plate: resolvedPlate,
          ),
        )
        .toList();

    final summaryBase = RenainfSummary.fromJson(summaryJson, infractions);
    final summary = RenainfSummary(
      totalInfractions:
          occurrencesCount ?? summaryBase.totalInfractions,
      totalValue: summaryBase.totalValue,
      openValue: summaryBase.openValue,
      lastUpdatedAt: summaryBase.lastUpdatedAt,
      lastUpdatedLabel: summaryBase.lastUpdatedLabel,
    );

    return RenainfResult(
      plate: resolvedPlate,
      statusCode: resolvedStatusCode,
      statusLabel: resolvedStatusLabel,
      uf: resolvedUf,
      startDate: apiStartDate ?? requestStartDate,
      endDate: apiEndDate ?? requestEndDate,
      summary: summary,
      infractions: infractions,
      sourceTitle: sourceTitle.isNotEmpty ? sourceTitle : null,
      sourceGeneratedAt:
          sourceGeneratedAt.isNotEmpty ? sourceGeneratedAt : null,
      consulta: consulta,
      occurrences: occurrences,
      occurrencesCount: occurrencesCount,
    );
  }
}

class RenainfSummary {
  RenainfSummary({
    required this.totalInfractions,
    required this.totalValue,
    required this.openValue,
    required this.lastUpdatedAt,
    required this.lastUpdatedLabel,
  });

  final int totalInfractions;
  final double totalValue;
  final double openValue;
  final DateTime? lastUpdatedAt;
  final String? lastUpdatedLabel;

  factory RenainfSummary.fromJson(
    Map<String, dynamic> json,
    List<RenainfInfraction> infractions,
  ) {
    final totalInfractions =
        _readInt(json, ['total_infractions', 'total', 'quantidade']) ??
            infractions.length;

    final totalValue = _readDouble(json, [
          'total_value',
          'valor_total',
          'total',
          'valorTotal',
        ]) ??
        infractions.fold<double>(0, (sum, infraction) => sum + infraction.amount);

    final openValue = _readDouble(json, [
          'open_value',
          'valor_em_aberto',
          'valorAberto',
        ]) ??
        infractions
            .where((infraction) => infraction.isOpen)
            .fold<double>(0, (sum, infraction) => sum + infraction.amount);

    final lastUpdateRaw = _readString(json, [
      'last_update',
      'ultima_atualizacao',
      'ultimaAtualizacao',
      'updated_at',
    ]);
    final lastUpdatedAt = _parseDate(lastUpdateRaw);

    return RenainfSummary(
      totalInfractions: totalInfractions,
      totalValue: totalValue,
      openValue: openValue,
      lastUpdatedAt: lastUpdatedAt,
      lastUpdatedLabel:
          lastUpdatedAt == null && lastUpdateRaw.isNotEmpty ? lastUpdateRaw : null,
    );
  }
}

class RenainfConsulta {
  const RenainfConsulta({
    required this.placa,
    required this.ufEmplacamento,
    required this.indicadorExigibilidade,
  });

  final String placa;
  final String ufEmplacamento;
  final String indicadorExigibilidade;

  factory RenainfConsulta.fromJson(
    Map<String, dynamic> json, {
    required String fallbackPlate,
    required String fallbackUf,
    required String fallbackIndicator,
  }) {
    final placa = _fallbackValue(
      _readString(json, ['placa', 'plate']),
      fallbackPlate,
    );
    final ufEmplacamento = _fallbackValue(
      _readString(json, ['uf_emplacamento', 'ufEmplacamento', 'uf']),
      fallbackUf,
    );
    final indicador = _fallbackValue(
      _readString(json, [
        'indicador_exigibilidade',
        'indicadorExigibilidade',
        'indicador',
      ]),
      fallbackIndicator,
    );

    return RenainfConsulta(
      placa: placa,
      ufEmplacamento: ufEmplacamento,
      indicadorExigibilidade: indicador,
    );
  }
}

class RenainfOccurrence {
  const RenainfOccurrence({
    required this.orgaoAutuador,
    required this.autoInfracao,
    required this.infracao,
    required this.dataInfracao,
    required this.exigibilidade,
  });

  final String orgaoAutuador;
  final String autoInfracao;
  final String infracao;
  final String dataInfracao;
  final String exigibilidade;

  factory RenainfOccurrence.fromJson(Map<String, dynamic> json) {
    return RenainfOccurrence(
      orgaoAutuador: _ensureValue(
        _readString(json, ['orgao_autuador', 'orgaoAutuador', 'orgao']),
      ),
      autoInfracao: _ensureValue(
        _readString(json, ['auto_infracao', 'autoInfracao', 'auto']),
      ),
      infracao: _ensureValue(
        _readString(json, ['infracao', 'codigo_infracao', 'codigoInfracao']),
      ),
      dataInfracao: _ensureValue(
        _readString(json, ['data_infracao', 'dataInfracao', 'data']),
      ),
      exigibilidade: _ensureValue(
        _readString(json, ['exigibilidade', 'indicador_exigibilidade']),
      ),
    );
  }
}

class RenainfInfraction {
  RenainfInfraction({
    required this.code,
    required this.description,
    required this.status,
    required this.statusCode,
    required this.amount,
    required this.origin,
    required this.plate,
    required this.date,
    required this.dateLabel,
    required this.municipioPlaca,
    required this.ufJuridica,
    required this.modelDescription,
    required this.codigoInfracao,
    required this.classificacao,
    required this.dataCadastro,
    required this.dataEmissao,
    required this.valorPago,
    required this.local,
    required this.tipoAuto,
    required this.ufPagamento,
    required this.dataPagamento,
    required this.dataRegistroPagamento,
    required this.cnhInfrator,
    required this.cnhCondutor,
    required this.suspensaoTipo,
    required this.suspensaoDataRegistro,
    required this.suspensaoOrigem,
    required this.suspensaoAceitoUf,
  });

  final String code;
  final String description;
  final String status;
  final int? statusCode;
  final double amount;
  final String origin;
  final String plate;
  final DateTime? date;
  final String? dateLabel;
  final String municipioPlaca;
  final String ufJuridica;
  final String modelDescription;
  final String codigoInfracao;
  final String classificacao;
  final String dataCadastro;
  final String dataEmissao;
  final double valorPago;
  final String local;
  final String tipoAuto;
  final String ufPagamento;
  final String dataPagamento;
  final String dataRegistroPagamento;
  final String cnhInfrator;
  final String cnhCondutor;
  final String suspensaoTipo;
  final String suspensaoDataRegistro;
  final String suspensaoOrigem;
  final String suspensaoAceitoUf;

  bool get isOpen {
    final normalized = status.toLowerCase();
    if (statusCode == 1) return true;
    return normalized.contains('abert') || normalized.contains('cobran');
  }

  factory RenainfInfraction.fromJson(
    Map<String, dynamic> json, {
    required String plate,
  }) {
    final code = _ensureValue(
      _readString(
        json,
        ['code', 'auto', 'auto_infracao', 'autoInfracao', 'numero_auto'],
      ),
    );
    final description = _ensureValue(
      _readString(json, ['description', 'descricao', 'descricao_infracao']),
    );
    final status = _ensureValue(
      _readString(json, ['status_label', 'status', 'situacao']),
    );
    final statusCode = _readInt(json, ['status_code', 'statusCodigo', 'status']);
    final amount =
        _readDouble(json, ['amount', 'valor', 'valor_infracao', 'valorMulta']) ??
            0;
    final origin = _ensureValue(
      _readString(json, ['origin', 'orgao', 'orgao_autuador', 'nomeOrgao']),
    );

    final municipioPlaca = _ensureValue(
      _readString(json, ['municipio_placa', 'municipioPlaca', 'municipio']),
    );
    final ufJuridica = _ensureValue(
      _readString(json, ['uf_jurisdicao', 'ufJuridicao', 'uf']),
    );
    final modelDescription = _ensureValue(
      _readString(json, ['model_description', 'modelo', 'descricao_modelo']),
    );
    final codigoInfracao = _ensureValue(
      _readString(json, ['codigo_infracao', 'codigoInfracao', 'codigo']),
    );
    final classificacao = _ensureValue(
      _readString(json, ['classificacao', 'descricao_classificacao']),
      description,
    );
    final dataCadastro = _ensureValue(
      _readString(json, ['data_cadastro', 'dataCadastro']),
    );
    final dataEmissao = _ensureValue(
      _readString(json, ['data_emissao', 'dataEmissao']),
    );
    final valorPago =
        _readDouble(json, ['valor_pago', 'valorPago', 'valorPagoMulta']) ?? 0;

    final dateRaw = _readString(
      json,
      ['date_time', 'dataHora', 'data_hora', 'data'],
    );
    final date = _parseDate(dateRaw);

    final local = _ensureValue(
      _readString(json, ['local', 'local_infracao', 'localInfracao']),
    );
    final tipoAuto = _ensureValue(
      _readString(json, ['tipo_auto', 'tipoAuto']),
    );
    final ufPagamento = _ensureValue(
      _readString(json, ['uf_pagamento', 'ufPagamento']),
    );
    final dataPagamento = _ensureValue(
      _readString(json, ['data_pagamento', 'dataPagamento']),
    );
    final dataRegistroPagamento = _ensureValue(
      _readString(json, ['data_registro_pagamento', 'dataRegistroPagamento']),
    );
    final cnhInfrator = _ensureValue(
      _readString(json, ['cnh_infrator', 'cnhInfrator']),
    );
    final cnhCondutor = _ensureValue(
      _readString(json, ['cnh_condutor', 'cnhCondutor']),
    );
    final suspensaoTipo = _ensureValue(
      _readString(json, ['suspensao_tipo', 'suspensaoTipo']),
    );
    final suspensaoDataRegistro = _ensureValue(
      _readString(json, ['suspensao_data_registro', 'suspensaoDataRegistro']),
    );
    final suspensaoOrigem = _ensureValue(
      _readString(json, ['suspensao_origem', 'suspensaoOrigem']),
    );
    final suspensaoAceitoUf = _ensureValue(
      _readString(json, ['suspensao_aceito_uf', 'suspensaoAceitoUf']),
    );

    return RenainfInfraction(
      code: code,
      description: description,
      status: status,
      statusCode: statusCode,
      amount: amount,
      origin: origin,
      plate: plate,
      date: date,
      dateLabel: date == null && dateRaw.isNotEmpty ? dateRaw : null,
      municipioPlaca: municipioPlaca,
      ufJuridica: ufJuridica,
      modelDescription: modelDescription,
      codigoInfracao: codigoInfracao,
      classificacao: classificacao,
      dataCadastro: dataCadastro,
      dataEmissao: dataEmissao,
      valorPago: valorPago,
      local: local,
      tipoAuto: tipoAuto,
      ufPagamento: ufPagamento,
      dataPagamento: dataPagamento,
      dataRegistroPagamento: dataRegistroPagamento,
      cnhInfrator: cnhInfrator,
      cnhCondutor: cnhCondutor,
      suspensaoTipo: suspensaoTipo,
      suspensaoDataRegistro: suspensaoDataRegistro,
      suspensaoOrigem: suspensaoOrigem,
      suspensaoAceitoUf: suspensaoAceitoUf,
    );
  }
}

String _fallbackValue(String candidate, String fallback) {
  return candidate.trim().isNotEmpty ? candidate.trim() : fallback;
}

String _ensureValue(String value, [String fallback = '—']) {
  final trimmed = value.trim();
  return trimmed.isEmpty ? fallback : trimmed;
}

Map<String, dynamic>? _asMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) {
    return value.map(
      (key, dynamic val) => MapEntry(key.toString(), val),
    );
  }
  return null;
}

List<Map<String, dynamic>> _extractInfractions(Map<String, dynamic> json) {
  const keys = [
    'infractions',
    'infracoes',
    'items',
    'itens',
    'infractions_list',
    'resultado',
    'infractionsList',
  ];

  for (final key in keys) {
    final list = _coerceListOfMap(json[key]);
    if (list != null && list.isNotEmpty) {
      return list;
    }
  }

  final dataValue = json['data'];
  if (dataValue is List) {
    final list = _coerceListOfMap(dataValue);
    if (list != null && list.isNotEmpty) {
      return list;
    }
  } else if (dataValue is Map) {
    final nested = _extractInfractions(_asMap(dataValue) ?? {});
    if (nested.isNotEmpty) {
      return nested;
    }
  }

  return const [];
}

List<Map<String, dynamic>>? _coerceListOfMap(dynamic value) {
  if (value is List) {
    final result = <Map<String, dynamic>>[];
    for (final item in value) {
      final map = _asMap(item);
      if (map != null) {
        result.add(map);
      }
    }
    return result;
  }
  return null;
}

String _readString(Map<String, dynamic> source, List<String> keys) {
  for (final key in keys) {
    if (!source.containsKey(key)) continue;
    final value = source[key];
    if (value == null) continue;
    final text = value.toString().trim();
    if (text.isNotEmpty) {
      return text;
    }
  }
  return '';
}

int? _readInt(Map<String, dynamic> source, List<String> keys) {
  for (final key in keys) {
    if (!source.containsKey(key)) continue;
    final value = source[key];
    if (value == null) continue;
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String) {
      final sanitized = value.replaceAll(RegExp(r'[^0-9\-]'), '');
      if (sanitized.isEmpty) continue;
      final match = RegExp(r'-?\d+').firstMatch(sanitized);
      if (match != null) {
        final parsed = int.tryParse(match.group(0)!);
        if (parsed != null) return parsed;
      }
    }
  }
  return null;
}

double? _readDouble(Map<String, dynamic> source, List<String> keys) {
  for (final key in keys) {
    if (!source.containsKey(key)) continue;
    final value = source[key];
    final parsed = _parseDouble(value);
    if (parsed != null) return parsed;
  }
  return null;
}

double? _parseDouble(dynamic value) {
  if (value == null) return null;
  if (value is num) return value.toDouble();
  if (value is String) {
    var cleaned = value.trim();
    if (cleaned.isEmpty) return null;
    cleaned = cleaned.replaceAll(RegExp(r'[^0-9,.\-]'), '');
    final hasComma = cleaned.contains(',');
    final hasDot = cleaned.contains('.');
    if (hasComma && hasDot) {
      cleaned = cleaned.replaceAll('.', '').replaceAll(',', '.');
    } else if (hasComma) {
      cleaned = cleaned.replaceAll(',', '.');
    }
    return double.tryParse(cleaned);
  }
  return null;
}

DateTime? _parseDate(dynamic value) {
  if (value == null) return null;
  if (value is DateTime) return value;
  final text = value.toString().trim();
  if (text.isEmpty) return null;

  final direct = DateTime.tryParse(text);
  if (direct != null) return direct;

  final match = RegExp(
    r'(?<day>\d{2})[\/\-](?<month>\d{2})[\/\-](?<year>\d{4})(?:[ T](?<hour>\d{2}):(?<minute>\d{2})(?::(?<second>\d{2}))?)?',
  ).firstMatch(text);

  if (match != null) {
    final day = int.parse(match.namedGroup('day')!);
    final month = int.parse(match.namedGroup('month')!);
    final year = int.parse(match.namedGroup('year')!);
    final hour = int.tryParse(match.namedGroup('hour') ?? '0') ?? 0;
    final minute = int.tryParse(match.namedGroup('minute') ?? '0') ?? 0;
    final second = int.tryParse(match.namedGroup('second') ?? '0') ?? 0;

    return DateTime(
      year,
      max(1, month),
      max(1, day),
      max(0, hour),
      max(0, minute),
      max(0, second),
    );
  }

  return null;
}
