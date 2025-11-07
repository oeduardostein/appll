import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:share_plus/share_plus.dart';

import '../shared/gravame_details_card.dart';
import 'package:frontend_app/ui/widgets/response_top_bar.dart';

class GravamePage extends StatelessWidget {
  const GravamePage({
    super.key,
    required this.placa,
    required this.payload,
    this.renavam,
    this.uf,
  });

  final String placa;
  final String? renavam;
  final String? uf;
  final Map<String, dynamic> payload;

  @override
  Widget build(BuildContext context) {
    final structured = _GravameStructuredPayload.fromPayload(payload);
    final fonte = structured.fonte;
    final veiculo = structured.veiculo;
    final gravames = structured.gravames;
    final gravamesDatas = structured.gravamesDatas;
    final intencao = structured.intencaoGravame;
    final origin = structured.origin;

    final inclusionDate = _nonEmptyString(intencao?['data_inclusao']) ??
        _nonEmptyString(gravamesDatas?['inclusao_financiamento']) ??
        '—';

    final restricaoFinanceira =
        _nonEmptyString(intencao?['restricao_financeira']) ??
            _nonEmptyString(gravames?['restricao_financeira']) ??
            '—';

    final agenteFinanceiro = _nonEmptyString(intencao?['agente_financeiro']) ??
        _nonEmptyString(gravames?['nome_agente']) ??
        '—';

    final nomeFinanciado = _nonEmptyString(intencao?['nome_financiado']) ??
        _nonEmptyString(gravames?['arrendatario']) ??
        '—';

    final documentoFinanciado = _nonEmptyString(intencao?['cnpj_cpf']) ??
        _nonEmptyString(gravames?['cnpj_cpf_financiado']) ??
        '—';

    final arrendatario = _nonEmptyString(gravames?['arrendatario']);

    final statusLabel =
        restricaoFinanceira != '—' || _nonEmptyString(intencao?['restricao_financeira']) != null
            ? 'Ativo'
            : 'Não encontrado';

    final formattedJson = const JsonEncoder.withIndent('  ').convert(payload);

    return Scaffold(
      appBar: ResponseTopBar(
        title: 'Gravame',
        subtitle: 'Placa: $placa',
        onShare: () => _shareResult(context, structured),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _GravameSummaryCard(
                placa: placa,
                renavam: renavam,
                uf: uf,
                origin: origin,
                veiculo: veiculo,
                fonte: fonte,
              ),
              const SizedBox(height: 16),
              GravameDetailsCard(
                statusLabel: statusLabel,
                inclusionDate: inclusionDate,
                restricaoFinanceira: restricaoFinanceira,
                agenteFinanceiro: agenteFinanceiro,
                nomeFinanciado: nomeFinanciado,
                documentoFinanciado: documentoFinanciado,
                arrendatario: arrendatario,
              ),
              const SizedBox(height: 24),
              Text(
                'Resposta completa',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
              ),
              const SizedBox(height: 12),
              Container(
                decoration: BoxDecoration(
                  color: Colors.black.withOpacity(0.04),
                  borderRadius: BorderRadius.circular(16),
                ),
                padding: const EdgeInsets.all(16),
                child: SelectableText(
                  formattedJson,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        fontFamily: 'monospace',
                        height: 1.4,
                      ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _shareResult(
    BuildContext context,
    _GravameStructuredPayload structured,
  ) async {
    bool dialogOpened = false;
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );
    dialogOpened = true;

    try {
      final generator = _GravamePdfGenerator();
      final bytes = await generator.generate(
        data: structured,
        placa: placa,
        renavam: renavam,
        uf: uf,
      );

      if (dialogOpened && context.mounted) {
        Navigator.of(context, rootNavigator: true).pop();
        dialogOpened = false;
      }

      final sanitized = placa.replaceAll(RegExp(r'[^A-Za-z0-9]'), '');
      final filename =
          'pesquisa_gravame_${sanitized.isEmpty ? 'consulta' : sanitized}.pdf';

      await Share.shareXFiles(
        [
          XFile.fromData(
            bytes,
            mimeType: 'application/pdf',
            name: filename,
          ),
        ],
        text: 'Pesquisa Gravame',
        subject: 'Pesquisa Gravame',
      );

      if (!context.mounted) return;
      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          const SnackBar(
            content: Text('PDF gerado. Escolha o app para compartilhar.'),
          ),
        );
    } catch (error) {
      if (dialogOpened && context.mounted) {
        Navigator.of(context, rootNavigator: true).pop();
      }
      if (!context.mounted) return;
      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          SnackBar(
            content: Text('Não foi possível gerar o PDF (${error.toString()}).'),
          ),
        );
    }
  }
}

class _GravameSummaryCard extends StatelessWidget {
  const _GravameSummaryCard({
    required this.placa,
    this.origin,
    this.veiculo,
    this.fonte,
    this.renavam,
    this.uf,
  });

  final String placa;
  final String? uf;
  final String? renavam;
  final String? origin;
  final Map<String, dynamic>? veiculo;
  final Map<String, dynamic>? fonte;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final generatedAt = fonte != null ? fonte!['gerado_em']?.toString() : null;
    final procedencia =
        veiculo != null ? veiculo!['procedencia']?.toString() : null;

    Widget buildTile(String label, String value) {
      return Expanded(
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: colorScheme.outlineVariant.withOpacity(0.3),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: const Color(0xFF667085),
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                value,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Container(
      decoration: BoxDecoration(
        color: colorScheme.primary,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: colorScheme.primary.withOpacity(0.2),
            blurRadius: 20,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Text(
                'Resumo da consulta',
                style: theme.textTheme.titleMedium?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const Spacer(),
              if (origin != null && origin!.isNotEmpty)
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Text(
                    origin == 'another_base_estadual'
                        ? 'Outros estados'
                        : 'Base estadual',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
            ],
          ),
          if (generatedAt != null && generatedAt.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              'Gerado em $generatedAt',
              style: theme.textTheme.bodySmall?.copyWith(
                color: Colors.white70,
              ),
            ),
          ],
          const SizedBox(height: 16),
          Row(
            children: [
              buildTile('Placa', placa),
              if (renavam != null && renavam!.trim().isNotEmpty) ...[
                const SizedBox(width: 12),
                buildTile('Renavam', renavam!.trim()),
              ],
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              if (uf != null && uf!.trim().isNotEmpty) ...[
                buildTile('UF', uf!.trim()),
                if (procedencia != null && procedencia.isNotEmpty)
                  const SizedBox(width: 12),
              ],
              if (procedencia != null && procedencia.isNotEmpty)
                buildTile('Procedência', procedencia),
            ],
          ),
        ],
      ),
    );
  }
}

class _GravameStructuredPayload {
  const _GravameStructuredPayload({
    this.fonte,
    this.veiculo,
    this.gravames,
    this.gravamesDatas,
    this.intencaoGravame,
    this.origin,
  });

  final Map<String, dynamic>? fonte;
  final Map<String, dynamic>? veiculo;
  final Map<String, dynamic>? gravames;
  final Map<String, dynamic>? gravamesDatas;
  final Map<String, dynamic>? intencaoGravame;
  final String? origin;

  factory _GravameStructuredPayload.fromPayload(Map<String, dynamic> payload) {
    return _GravameStructuredPayload(
      fonte: _asMap(payload['fonte']),
      veiculo: _asMap(payload['veiculo']),
      gravames: _asMap(payload['gravames']),
      gravamesDatas: _asMap(payload['gravames_datas']),
      intencaoGravame: _asMap(payload['intencao_gravame']),
      origin: payload['origin']?.toString(),
    );
  }
}

class _GravamePdfField {
  const _GravamePdfField({required this.label, required this.value});

  final String label;
  final String value;
}

class _GravamePdfSection {
  const _GravamePdfSection({required this.title, required this.fields});

  final String title;
  final List<_GravamePdfField> fields;
}

class _GravamePdfGenerator {
  Future<Uint8List> generate({
    required _GravameStructuredPayload data,
    required String placa,
    String? renavam,
    String? uf,
  }) async {
    final sections = _buildSections(data, placa, renavam, uf);
    final doc = pw.Document();
    final logo = await _loadLogo();

    doc.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.fromLTRB(32, 28, 32, 36),
        build: (context) => [
          _buildHeader(
            logo: logo,
            placa: placa,
            renavam: renavam,
            uf: uf,
            origin: data.origin,
          ),
          pw.SizedBox(height: 18),
          ...sections.map(_buildSection),
        ],
      ),
    );

    return doc.save();
  }

  Future<pw.MemoryImage?> _loadLogo() async {
    try {
      final data = await rootBundle.load('assets/images/logoLL.png');
      return pw.MemoryImage(data.buffer.asUint8List());
    } catch (_) {
      return null;
    }
  }

  pw.Widget _buildHeader({
    required pw.MemoryImage? logo,
    required String placa,
    String? renavam,
    String? uf,
    String? origin,
  }) {
    final now = DateTime.now();
    final dateFormatted =
        '${_twoDigits(now.day)}/${_twoDigits(now.month)}/${now.year} - '
        '${_twoDigits(now.hour)}:${_twoDigits(now.minute)}:${_twoDigits(now.second)}';

    return pw.Column(
      crossAxisAlignment: pw.CrossAxisAlignment.start,
      children: [
        pw.Row(
          crossAxisAlignment: pw.CrossAxisAlignment.start,
          children: [
            if (logo != null)
              pw.Container(
                width: 70,
                height: 70,
                decoration: pw.BoxDecoration(
                  borderRadius: pw.BorderRadius.circular(16),
                ),
                child: pw.Image(logo),
              ),
            if (logo != null) pw.SizedBox(width: 16),
            pw.Expanded(
              child: pw.Column(
                crossAxisAlignment: pw.CrossAxisAlignment.start,
                children: [
                  pw.Text(
                    'LL DESPACHANTE',
                    style: pw.TextStyle(
                      fontSize: 18,
                      fontWeight: pw.FontWeight.bold,
                      color: PdfColors.blue900,
                    ),
                  ),
                  pw.Text(
                    'AV. DES. PLÍNIO DE CARVALHO PINTO, 05 - ENSEADA - (13) 99730-1533 / 11 3367-8400\nGUARUJÁ - SP',
                    style: const pw.TextStyle(fontSize: 10),
                  ),
                ],
              ),
            ),
          ],
        ),
        pw.SizedBox(height: 14),
        pw.Center(
          child: pw.Column(
            children: [
              pw.Text(
                'PESQUISA GRAVAME',
                style: pw.TextStyle(
                  fontSize: 14,
                  fontWeight: pw.FontWeight.bold,
                  color: PdfColors.blue800,
                ),
              ),
              pw.Text(
                'Data da pesquisa: $dateFormatted',
                style: const pw.TextStyle(fontSize: 10),
              ),
              if (origin != null && origin.trim().isNotEmpty)
                pw.Text(
                  'Origem: ${origin == 'another_base_estadual' ? 'Outros estados' : origin}',
                  style: const pw.TextStyle(fontSize: 10),
                ),
            ],
          ),
        ),
        pw.SizedBox(height: 10),
        pw.Text(
          'Placa: ${placa.toUpperCase()}   |   Renavam: ${_formatDisplayValue(renavam)}   |   UF: ${_formatDisplayValue(uf)}',
          style: const pw.TextStyle(fontSize: 11),
        ),
      ],
    );
  }

  pw.Widget _buildSection(_GravamePdfSection section) {
    return pw.Container(
      margin: const pw.EdgeInsets.only(bottom: 16),
      padding: const pw.EdgeInsets.all(14),
      decoration: pw.BoxDecoration(
        borderRadius: pw.BorderRadius.circular(10),
        border: pw.Border.all(color: PdfColors.blueGrey300, width: 0.6),
      ),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.Text(
            section.title,
            style: pw.TextStyle(
              fontSize: 12,
              fontWeight: pw.FontWeight.bold,
              color: PdfColors.blue800,
            ),
          ),
          pw.SizedBox(height: 8),
          _buildFieldsGrid(section.fields),
        ],
      ),
    );
  }

  pw.Widget _buildFieldsGrid(List<_GravamePdfField> fields) {
    final rows = <pw.TableRow>[];
    for (var i = 0; i < fields.length; i += 2) {
      final first = fields[i];
      final second = i + 1 < fields.length ? fields[i + 1] : null;
      rows.add(
        pw.TableRow(
          children: [
            _buildFieldCell(first),
            if (second != null) _buildFieldCell(second) else pw.Container(),
          ],
        ),
      );
    }

    return pw.Table(
      columnWidths: const {
        0: pw.FlexColumnWidth(1),
        1: pw.FlexColumnWidth(1),
      },
      defaultVerticalAlignment: pw.TableCellVerticalAlignment.middle,
      children: rows,
    );
  }

  pw.Widget _buildFieldCell(_GravamePdfField field) {
    return pw.Padding(
      padding: const pw.EdgeInsets.all(4),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.Text(
            field.label,
            style: pw.TextStyle(
              fontSize: 9,
              fontWeight: pw.FontWeight.bold,
              color: PdfColors.blueGrey700,
            ),
          ),
          pw.SizedBox(height: 2),
          pw.Text(
            field.value,
            style: pw.TextStyle(
              fontSize: 11,
              color: PdfColors.blueGrey900,
            ),
          ),
        ],
      ),
    );
  }

  List<_GravamePdfSection> _buildSections(
    _GravameStructuredPayload data,
    String placa,
    String? renavam,
    String? uf,
  ) {
    final sections = <_GravamePdfSection?>[
      _pdfSectionFromMap(
        'Dados da consulta',
        {
          'placa': placa,
          'renavam': renavam,
          'uf': uf,
          'origin': data.origin,
          'gerado_em': data.fonte?['gerado_em'],
        },
        const {
          'placa': 'Placa',
          'renavam': 'Renavam',
          'uf': 'UF',
          'origin': 'Origem',
          'gerado_em': 'Gerado em',
        },
      ),
      _pdfSectionFromMap(
        'Veículo',
        data.veiculo,
        const {
          'marca': 'Marca',
          'modelo': 'Modelo',
          'tipo': 'Tipo',
          'categoria': 'Categoria',
          'procedencia': 'Procedência',
          'combustivel': 'Combustível',
          'cor': 'Cor',
          'ano_fabricacao': 'Ano fabricação',
          'ano_modelo': 'Ano modelo',
          'chassi': 'Chassi',
        },
      ),
      _pdfSectionFromMap(
        'Gravame atual',
        data.gravames,
        const {
          'restricao_financeira': 'Restrição financeira',
          'nome_agente': 'Nome do agente',
          'arrendatario': 'Arrendatário',
          'cnpj_cpf_financiado': 'CNPJ/CPF financiado',
          'numero_contrato': 'Número do contrato',
        },
        excludeKeys: const {'datas'},
      ),
      _pdfSectionFromMap(
        'Gravame - Datas',
        data.gravamesDatas,
        const {
          'inclusao_financiamento': 'Inclusão financiamento',
          'emissao': 'Emissão',
        },
      ),
      _pdfSectionFromMap(
        'Intenção de gravame',
        data.intencaoGravame,
        const {
          'restricao_financeira': 'Restrição financeira',
          'agente_financeiro': 'Agente financeiro',
          'nome_financiado': 'Nome financiado',
          'cnpj_cpf': 'CNPJ/CPF',
          'data_inclusao': 'Data inclusão',
        },
      ),
    ];

    return sections.whereType<_GravamePdfSection>().toList();
  }
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

String? _nonEmptyString(dynamic value) {
  if (value == null) return null;
  final text = value.toString().trim();
  if (text.isEmpty) return null;
  if (RegExp(r'^-+$').hasMatch(text)) return null;
  return text;
}

List<_GravamePdfField> _buildPdfFieldsFromMap(
  Map<String, dynamic>? source,
  Map<String, String> labels, {
  Set<String> excludeKeys = const {},
}) {
  if (source == null) return [];

  final fields = <_GravamePdfField>[];
  final handled = <String>{};

  for (final entry in labels.entries) {
    handled.add(entry.key);
    final value = source[entry.key];
    if (!_hasDisplayValue(value)) continue;
    fields.add(
      _GravamePdfField(
        label: entry.value,
        value: _formatDisplayValue(value),
      ),
    );
  }

  for (final entry in source.entries) {
    final key = entry.key;
    if (handled.contains(key) || excludeKeys.contains(key)) {
      continue;
    }
    final value = entry.value;
    if (!_hasDisplayValue(value)) continue;
    fields.add(
      _GravamePdfField(
        label: _generateAutoLabel(key),
        value: _formatDisplayValue(value),
      ),
    );
  }

  return fields;
}

_GravamePdfSection? _pdfSectionFromMap(
  String title,
  Map<String, dynamic>? source,
  Map<String, String> labels, {
  Set<String> excludeKeys = const {},
}) {
  final fields = _buildPdfFieldsFromMap(
    source,
    labels,
    excludeKeys: excludeKeys,
  );
  if (fields.isEmpty) return null;
  return _GravamePdfSection(title: title, fields: fields);
}

bool _hasDisplayValue(dynamic value) {
  if (value == null) return false;
  if (value is String) return value.trim().isNotEmpty && !RegExp(r'^-+$').hasMatch(value.trim());
  if (value is Iterable) return value.isNotEmpty;
  if (value is Map) return value.isNotEmpty;
  return true;
}

String _generateAutoLabel(String key) {
  if (key.trim().isEmpty) return 'Valor';
  String working = key
      .replaceAll(RegExp(r'[-_]+'), ' ')
      .replaceAllMapped(RegExp(r'(?<=[a-z0-9])([A-Z])'), (match) => ' ${match.group(1)}');
  final buffer = StringBuffer();
  for (final part in working.split(RegExp(r'\s+'))) {
    if (part.isEmpty) continue;
    if (buffer.isNotEmpty) buffer.write(' ');
    buffer.write(part[0].toUpperCase());
    if (part.length > 1) {
      buffer.write(part.substring(1).toLowerCase());
    }
  }
  return buffer.isEmpty ? key : buffer.toString();
}

String _formatDisplayValue(dynamic value) {
  if (value == null) return '—';
  if (value is String) {
    final trimmed = value.trim();
    return trimmed.isEmpty ? '—' : trimmed;
  }
  return value.toString();
}

String _twoDigits(int value) => value.toString().padLeft(2, '0');
