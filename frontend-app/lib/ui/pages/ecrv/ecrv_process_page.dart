import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:share_plus/share_plus.dart';

class EcrvProcessPage extends StatelessWidget {
  const EcrvProcessPage({
    super.key,
    required this.placa,
    required this.numeroFicha,
    required this.anoFicha,
    required this.fichaPayload,
    required this.andamentoPayload,
  });

  final String placa;
  final String numeroFicha;
  final String anoFicha;
  final Map<String, dynamic> fichaPayload;
  final Map<String, dynamic> andamentoPayload;

  @override
  Widget build(BuildContext context) {
    final fichaNormalized =
        _asMap(_asMap(fichaPayload['normalized'])?['dados_da_ficha_cadastral']);
    final andamentoNormalized =
        _asMap(andamentoPayload['normalized']) ?? const {};
    final andamentoInfo =
        _asMap(andamentoNormalized['andamento_do_processo']) ?? const {};
    final datasInfo = _asMap(andamentoNormalized['datas']) ?? const {};
    final anexosInfo = _asMap(andamentoNormalized['documentos_anexos']);

    final status = _nonEmptyString(
          andamentoInfo['status_registro'],
        ) ??
        _nonEmptyString(andamentoInfo['status']) ??
        '—';
    final retorno = _nonEmptyString(andamentoInfo['retorno_consistencia']);

    final reportData = _EcrvReportData(
      placa: placa,
      numeroFicha: numeroFicha,
      anoFicha: anoFicha,
      status: status,
      retornoConsistencia: retorno,
      fichaData: fichaNormalized ?? const {},
      andamentoInfo: andamentoInfo,
      datasInfo: datasInfo,
      anexosInfo: anexosInfo ?? const {},
    );

    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        title: const Text('Andamento do processo e-CRV'),
        actions: [
          IconButton(
            tooltip: 'Compartilhar PDF',
            onPressed: () => _shareReport(context, reportData),
            icon: const Icon(Icons.share_outlined),
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _ResumoCard(
                placa: placa,
                numeroFicha: numeroFicha,
                anoFicha: anoFicha,
                fichaData: fichaNormalized,
                status: status,
                retornoConsistencia: retorno,
              ),
              const SizedBox(height: 20),
              if (andamentoInfo.isNotEmpty)
                _InfoSection(
                  title: 'Andamento do processo',
                  entries: andamentoInfo,
                ),
              if (datasInfo.isNotEmpty) ...[
                const SizedBox(height: 16),
                _InfoSection(title: 'Datas', entries: datasInfo),
              ],
              if (anexosInfo != null && anexosInfo.isNotEmpty) ...[
                const SizedBox(height: 16),
                _InfoSection(title: 'Documentos anexos', entries: anexosInfo),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _shareReport(
    BuildContext context,
    _EcrvReportData report,
  ) async {
    bool dialogOpened = false;
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );
    dialogOpened = true;

    try {
      final generator = _EcrvPdfGenerator();
      final bytes = await generator.generate(report);

      if (dialogOpened && context.mounted) {
        Navigator.of(context, rootNavigator: true).pop();
        dialogOpened = false;
      }

      final sanitized =
          '${report.placa}_${report.numeroFicha}_${report.anoFicha}'
              .replaceAll(RegExp(r'[^A-Za-z0-9]'), '');
      final filename =
          'processo_ecrv_${sanitized.isEmpty ? 'consulta' : sanitized}.pdf';

      await Share.shareXFiles(
        [
          XFile.fromData(
            bytes,
            mimeType: 'application/pdf',
            name: filename,
          ),
        ],
        text: 'Andamento do processo e-CRV',
        subject: 'Andamento do processo e-CRV',
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
        dialogOpened = false;
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

class _ResumoCard extends StatelessWidget {
  const _ResumoCard({
    required this.placa,
    required this.numeroFicha,
    required this.anoFicha,
    required this.fichaData,
    required this.status,
    required this.retornoConsistencia,
  });

  final String placa;
  final String numeroFicha;
  final String anoFicha;
  final Map<String, dynamic>? fichaData;
  final String status;
  final String? retornoConsistencia;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    String? _value(String key) {
      final value = fichaData?[key];
      if (value == null) return null;
      final text = value.toString().trim();
      if (text.isEmpty) return null;
      if (RegExp(r'^-+$').hasMatch(text)) return null;
      return text;
    }

    Widget buildTile(String label, String? value) {
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
                value ?? '—',
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
            color: colorScheme.primary.withOpacity(0.22),
            blurRadius: 20,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Resumo da consulta',
            style: theme.textTheme.titleMedium?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              SizedBox(
                width: double.infinity,
                child: buildTile(
                  'Placa',
                  placa,
                ),
              ),
              SizedBox(
                width: double.infinity,
                child: buildTile(
                  'Número / Ano da ficha',
                  '$numeroFicha / $anoFicha',
                ),
              ),
              if (_value('renavam') != null)
                SizedBox(
                  width: double.infinity,
                  child: buildTile('Renavam', _value('renavam')),
                ),
              if (_value('chassi') != null)
                SizedBox(
                  width: double.infinity,
                  child: buildTile('Chassi', _value('chassi')),
                ),
              if (_value('municipio') != null)
                SizedBox(
                  width: double.infinity,
                  child: buildTile('Município', _value('municipio')),
                ),
            ],
          ),
          const SizedBox(height: 20),
          Container(
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.1),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.white.withOpacity(0.12)),
            ),
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Status do registro',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: Colors.white70,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  status,
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (retornoConsistencia != null &&
                    retornoConsistencia!.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(
                    retornoConsistencia!,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: Colors.white70,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoSection extends StatelessWidget {
  const _InfoSection({
    required this.title,
    required this.entries,
  });

  final String title;
  final Map<String, dynamic> entries;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final orderedEntries = entries.entries
        .map(
          (entry) => MapEntry(
            _formatLabel(entry.key),
            _formatValue(entry.value),
          ),
        )
        .where((entry) => entry.value.isNotEmpty)
        .toList();

    if (orderedEntries.isEmpty) {
      return const SizedBox.shrink();
    }

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: const [
          BoxShadow(
            color: Color(0x0F101828),
            blurRadius: 12,
            offset: Offset(0, 8),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(18, 18, 18, 22),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 16),
          for (final entry in orderedEntries) ...[
            Text(
              entry.key,
              style: theme.textTheme.bodySmall?.copyWith(
                color: const Color(0xFF667085),
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              entry.value,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: const Color(0xFF1D2939),
                fontWeight: FontWeight.w600,
              ),
            ),
            if (entry != orderedEntries.last) ...[
              const SizedBox(height: 12),
              const Divider(color: Color(0xFFE4E7EC)),
              const SizedBox(height: 12),
            ],
          ],
        ],
      ),
    );
  }

  String _formatLabel(String value) {
    final normalized = value.replaceAll('_', ' ').trim();
    if (normalized.isEmpty) return value;
    return normalized[0].toUpperCase() + normalized.substring(1);
  }

  String _formatValue(dynamic value) {
    if (value == null) return '';
    if (value is String) {
      final text = value.trim();
      if (text.isEmpty) return '';
      if (RegExp(r'^-+$').hasMatch(text)) return '';
      return text;
    }
    return value.toString();
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

class _EcrvReportData {
  const _EcrvReportData({
    required this.placa,
    required this.numeroFicha,
    required this.anoFicha,
    required this.status,
    this.retornoConsistencia,
    required this.fichaData,
    required this.andamentoInfo,
    required this.datasInfo,
    required this.anexosInfo,
  });

  final String placa;
  final String numeroFicha;
  final String anoFicha;
  final String status;
  final String? retornoConsistencia;
  final Map<String, dynamic> fichaData;
  final Map<String, dynamic> andamentoInfo;
  final Map<String, dynamic> datasInfo;
  final Map<String, dynamic> anexosInfo;
}

class _EcrvPdfField {
  const _EcrvPdfField({required this.label, required this.value});

  final String label;
  final String value;
}

class _EcrvPdfSection {
  const _EcrvPdfSection({required this.title, required this.fields});

  final String title;
  final List<_EcrvPdfField> fields;
}

class _EcrvPdfGenerator {
  Future<Uint8List> generate(_EcrvReportData data) async {
    final doc = pw.Document();
    final logo = await _loadLogo();

    final sections = <_EcrvPdfSection?>[
      _sectionFromFields(
        'Resumo da ficha',
        [
          _field('Placa', data.placa),
          _field('Número da ficha', data.numeroFicha),
          _field('Ano da ficha', data.anoFicha),
          _field('Renavam', data.fichaData['renavam']),
          _field('Chassi', data.fichaData['chassi']),
          _field('Município', data.fichaData['municipio']),
        ],
      ),
      _sectionFromFields(
        'Status do registro',
        [
          _field('Status', data.status),
          _field('Retorno de consistência', data.retornoConsistencia),
        ],
      ),
      _sectionFromMap('Andamento do processo', data.andamentoInfo),
      _sectionFromMap('Datas', data.datasInfo),
      _sectionFromMap('Documentos anexos', data.anexosInfo),
    ];

    doc.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.fromLTRB(32, 28, 32, 36),
        build: (context) => [
          _buildHeader(logo, data),
          pw.SizedBox(height: 18),
          ...sections.whereType<_EcrvPdfSection>().map(_buildSection),
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

  pw.Widget _buildHeader(pw.MemoryImage? logo, _EcrvReportData data) {
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
                'ANDAMENTO DO PROCESSO E-CRV',
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
          'Placa: ${data.placa}   |   Ficha: ${data.numeroFicha}/${data.anoFicha}',
          style: const pw.TextStyle(fontSize: 11),
        ),
      ],
    );
  }

  pw.Widget _buildSection(_EcrvPdfSection section) {
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

  pw.Widget _buildFieldsGrid(List<_EcrvPdfField> fields) {
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

  pw.Widget _buildFieldCell(_EcrvPdfField field) {
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

  _EcrvPdfSection? _sectionFromMap(
    String title,
    Map<String, dynamic> source,
  ) {
    final fields = <_EcrvPdfField>[];
    for (final entry in source.entries) {
      final label = _formatShareLabel(entry.key);
      final value = _nonEmptyString(entry.value);
      if (value == null) continue;
      fields.add(_EcrvPdfField(label: label, value: value));
    }
    if (fields.isEmpty) return null;
    return _EcrvPdfSection(title: title, fields: fields);
  }

  _EcrvPdfSection? _sectionFromFields(
    String title,
    List<_EcrvPdfField?> fields,
  ) {
    final filtered = fields.whereType<_EcrvPdfField>().toList();
    if (filtered.isEmpty) return null;
    return _EcrvPdfSection(title: title, fields: filtered);
  }

  _EcrvPdfField? _field(String label, dynamic value) {
    final text = _nonEmptyString(value);
    if (text == null) return null;
    return _EcrvPdfField(label: label, value: text);
  }
}

String _formatShareLabel(String value) {
  final normalized = value.replaceAll('_', ' ').trim();
  if (normalized.isEmpty) return value;
  return normalized[0].toUpperCase() + normalized.substring(1);
}

String _twoDigits(int value) => value.toString().padLeft(2, '0');
