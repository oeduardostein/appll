import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:share_plus/share_plus.dart';

import 'package:frontend_app/ui/widgets/response_top_bar.dart';

String? _nonEmptyString(dynamic value) {
  if (value == null) return null;
  final text = value.toString().trim();
  return text.isEmpty ? null : text;
}

class BinResultPage extends StatelessWidget {
  const BinResultPage({
    super.key,
    required this.placa,
    required this.renavam,
    required this.payload,
  });

  final String placa;
  final String renavam;
  final Map<String, dynamic> payload;

  @override
  Widget build(BuildContext context) {
    final structured = _BinStructuredPayload.fromPayload(
      payload,
      fallbackPlaca: placa,
      fallbackRenavam: renavam,
    );

    final sections = structured.sections;
    final fonte = structured.fonte;

    final displayPlaca = structured.displayPlaca;
    final displayRenavam = structured.displayRenavam;
    final displayChassi = structured.displayChassi;
    final displayProprietario = structured.displayProprietario;
    final formattedJson = const JsonEncoder.withIndent('  ').convert(payload);

    return Scaffold(
      appBar: ResponseTopBar(
        title: 'Pesquisa BIN',
        subtitle: 'Placa: $displayPlaca',
        onShare: () => _shareResult(context, structured),
        actions: [
          IconButton(
            tooltip: 'Copiar resultado',
            onPressed: () => _copyToClipboard(context, formattedJson),
            icon: const Icon(Icons.copy_outlined),
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _VehicleSummaryCard(
                placa: displayPlaca,
                renavam: displayRenavam,
                chassi: displayChassi,
                proprietario: displayProprietario,
              ),
              const SizedBox(height: 16),
              _SectionCard(
                title: 'Fonte',
                child: _SectionItemsList(
                  items: _mapToItems(fonte),
                ),
              ),
              for (final section in sections) ...[
                const SizedBox(height: 16),
                _SectionCard(
                  title: section.title,
                  child: _SectionItemsList(items: section.items),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  void _copyToClipboard(BuildContext context, String text) async {
    await Clipboard.setData(ClipboardData(text: text));
    if (!context.mounted) return;
    ScaffoldMessenger.of(context)
      ..clearSnackBars()
      ..showSnackBar(
        const SnackBar(
          content: Text('Dados copiados para a área de transferência.'),
        ),
      );
  }

  Future<void> _shareResult(
    BuildContext context,
    _BinStructuredPayload structured,
  ) async {
    bool dialogOpened = false;
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );
    dialogOpened = true;

    try {
      final generator = _BinPdfGenerator();
      final bytes = await generator.generate(structured);

      if (dialogOpened && context.mounted) {
        Navigator.of(context, rootNavigator: true).pop();
        dialogOpened = false;
      }

      final sanitized = structured.displayPlaca.replaceAll(RegExp(r'[^A-Za-z0-9]'), '');
      final filename =
          'pesquisa_bin_${sanitized.isEmpty ? 'consulta' : sanitized}.pdf';

      await Share.shareXFiles(
        [
          XFile.fromData(
            bytes,
            mimeType: 'application/pdf',
            name: filename,
          ),
        ],
        text: 'Pesquisa BIN',
        subject: 'Pesquisa BIN',
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
            content: Text(
              'Não foi possível gerar o PDF (${error is Exception ? error.toString() : 'erro desconhecido'}).',
            ),
          ),
        );
    }
  }

  static Map<String, dynamic>? _asMap(dynamic value) {
    if (value is Map<String, dynamic>) {
      return value;
    }
    if (value is Map) {
      return value.map(
        (key, dynamic val) => MapEntry(key.toString(), val),
      );
    }
    return null;
  }
}

List<_BinSection> _asSectionList(dynamic value) {
  if (value is! List) return const [];
  return value
      .whereType<Map>()
      .map(
        (raw) => _BinSection(
          title: raw['title']?.toString() ?? '',
          slug: raw['slug']?.toString() ?? '',
          items: _itemsFromRaw(raw['items']),
        ),
      )
      .where((section) => section.title.isNotEmpty && section.items.isNotEmpty)
      .toList();
}

List<_BinItem> _itemsFromRaw(dynamic rawItems) {
  if (rawItems is! List) return const [];
  return rawItems.whereType<Map>().map((raw) {
    final label = raw['label']?.toString() ?? '';
    final value = raw['value'];
    if (label.isEmpty) return null;
    return _BinItem(
      label: label,
      value: value,
      slug: raw['slug']?.toString() ?? '',
    );
  }).whereType<_BinItem>().where((item) => item.displayValue.isNotEmpty).toList();
}

List<_BinItem> _mapToItems(Map<String, dynamic>? map) {
  if (map == null || map.isEmpty) return const [];
  return map.entries
      .map(
        (entry) => _BinItem(
          label: _formatLabel(entry.key),
          value: entry.value,
          slug: entry.key,
        ),
      )
      .where((item) => item.displayValue.isNotEmpty)
      .toList();
}

String _formatLabel(String value) {
  final normalized = value.replaceAll('_', ' ').trim();
  if (normalized.isEmpty) return value;
  return normalized[0].toUpperCase() + normalized.substring(1);
}

class _BinSection {
  const _BinSection({
    required this.title,
    required this.slug,
    required this.items,
  });

  final String title;
  final String slug;
  final List<_BinItem> items;
}

class _BinItem {
  const _BinItem({
    required this.label,
    required this.value,
    required this.slug,
  });

  final String label;
  final dynamic value;
  final String slug;

  String get displayValue {
    if (value == null) return '';
    if (value is String) {
      final trimmed = value.trim();
      return trimmed.isEmpty ? '' : trimmed;
    }
    if (value is num || value is bool) {
      return value.toString();
    }
    try {
      return jsonEncode(value);
    } catch (_) {
      return value.toString();
    }
  }
}

class _VehicleSummaryCard extends StatelessWidget {
  const _VehicleSummaryCard({
    required this.placa,
    required this.renavam,
    this.chassi,
    this.proprietario,
  });

  final String placa;
  final String renavam;
  final String? chassi;
  final String? proprietario;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    Widget _buildTile(String label, String value) {
      return Expanded(
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: colorScheme.outlineVariant.withOpacity(0.3)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: colorScheme.primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 6),
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
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: colorScheme.primary.withOpacity(0.22),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Consulta realizada',
            style: theme.textTheme.titleMedium?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              _buildTile('Placa', placa),
              const SizedBox(width: 12),
              _buildTile('Renavam', renavam),
            ],
          ),
          if (chassi != null && chassi!.isNotEmpty) ...[
            const SizedBox(height: 12),
            Row(
              children: [
                _buildTile('Chassi', chassi!),
                if (proprietario != null && proprietario!.isNotEmpty) ...[
                  const SizedBox(width: 12),
                  _buildTile('Proprietário', proprietario!),
                ],
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({
    required this.title,
    required this.child,
  });

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withOpacity(0.3),
        ),
      ),
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            title,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class _SectionItemsList extends StatelessWidget {
  const _SectionItemsList({required this.items});

  final List<_BinItem> items;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return Text(
        'Sem informações disponíveis.',
        style: Theme.of(context)
            .textTheme
            .bodyMedium
            ?.copyWith(color: Colors.grey.shade600),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: items
          .map(
            (item) => Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.label,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Theme.of(context).colorScheme.primary,
                          fontWeight: FontWeight.w600,
                        ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    item.displayValue,
                    style: Theme.of(context).textTheme.bodyLarge,
                  ),
                ],
              ),
            ),
          )
          .toList(),
    );
  }
}

class _BinStructuredPayload {
  const _BinStructuredPayload({
    required this.payload,
    required this.displayPlaca,
    required this.displayRenavam,
    this.displayChassi,
    this.displayProprietario,
    this.fonte,
    required this.sections,
  });

  final Map<String, dynamic> payload;
  final String displayPlaca;
  final String displayRenavam;
  final String? displayChassi;
  final String? displayProprietario;
  final Map<String, dynamic>? fonte;
  final List<_BinSection> sections;

  factory _BinStructuredPayload.fromPayload(
    Map<String, dynamic> payload, {
    required String fallbackPlaca,
    required String fallbackRenavam,
  }) {
    final normalized = BinResultPage._asMap(payload['normalized']);
    final identificacao = BinResultPage._asMap(
      normalized?['identificacao_do_veiculo_na_bin'],
    );
    final gravames = BinResultPage._asMap(normalized?['gravames']);
    final fonte = BinResultPage._asMap(payload['fonte']);

    final displayPlaca =
        _nonEmptyString(identificacao?['placa']) ?? fallbackPlaca;
    final displayRenavam =
        _nonEmptyString(identificacao?['renavam']) ?? fallbackRenavam;
    final displayChassi = _nonEmptyString(identificacao?['chassi']);
    final displayProprietario = _nonEmptyString(gravames?['nome_financiado']);

    return _BinStructuredPayload(
      payload: payload,
      displayPlaca: displayPlaca,
      displayRenavam: displayRenavam,
      displayChassi: displayChassi,
      displayProprietario: displayProprietario,
      fonte: fonte,
      sections: _asSectionList(payload['sections']),
    );
  }
}

class _BinPdfField {
  const _BinPdfField({required this.label, required this.value});

  final String label;
  final String value;
}

class _BinPdfSection {
  const _BinPdfSection({required this.title, required this.fields});

  final String title;
  final List<_BinPdfField> fields;
}

class _BinPdfGenerator {
  Future<Uint8List> generate(_BinStructuredPayload data) async {
    final doc = pw.Document();
    final logo = await _loadLogo();
    final sections = _buildSections(data);

    doc.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.fromLTRB(32, 28, 32, 36),
        build: (context) => [
          _buildHeader(
            logo: logo,
            placa: data.displayPlaca,
            renavam: data.displayRenavam,
            chassi: data.displayChassi,
            proprietario: data.displayProprietario,
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
    required String renavam,
    String? chassi,
    String? proprietario,
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
                    'Telefone: (13) 99773-1533',
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
                'PESQUISA BIN',
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
            ],
          ),
        ),
        pw.SizedBox(height: 10),
        pw.Text(
          'Placa: $placa   |   Renavam: $renavam   |   Chassi: ${_formatDisplayValue(chassi)}',
          style: const pw.TextStyle(fontSize: 11),
        ),
        if (proprietario != null && proprietario.trim().isNotEmpty)
          pw.Text(
            'Proprietário: $proprietario',
            style: const pw.TextStyle(fontSize: 11),
          ),
      ],
    );
  }

  pw.Widget _buildSection(_BinPdfSection section) {
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

  pw.Widget _buildFieldsGrid(List<_BinPdfField> fields) {
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

  pw.Widget _buildFieldCell(_BinPdfField field) {
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

  List<_BinPdfSection> _buildSections(_BinStructuredPayload data) {
    final sections = <_BinPdfSection?>[
      _pdfSectionFromMap('Fonte', data.fonte),
    ];

    for (final section in data.sections) {
      sections.add(_pdfSectionFromItems(section.title, section.items));
    }

    return sections.whereType<_BinPdfSection>().toList();
  }
}

_BinPdfSection? _pdfSectionFromMap(
  String title,
  Map<String, dynamic>? data,
) {
  if (data == null || data.isEmpty) return null;
  final fields = data.entries
      .map(
        (entry) => _BinPdfField(
          label: _formatLabel(entry.key),
          value: _formatDisplayValue(entry.value),
        ),
      )
      .where((field) => field.value.isNotEmpty && field.value != '—')
      .toList();
  if (fields.isEmpty) return null;
  return _BinPdfSection(title: title, fields: fields);
}

_BinPdfSection? _pdfSectionFromItems(String title, List<_BinItem> items) {
  if (items.isEmpty) return null;
  final fields = items
      .map(
        (item) => _BinPdfField(
          label: item.label,
          value: item.displayValue,
        ),
      )
      .where((field) => field.value.isNotEmpty)
      .toList();
  if (fields.isEmpty) return null;
  return _BinPdfSection(title: title, fields: fields);
}

String _formatDisplayValue(dynamic value) {
  final computed = _nonEmptyString(value);
  return computed ?? '—';
}

String _twoDigits(int value) => value.toString().padLeft(2, '0');
