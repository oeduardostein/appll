import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:share_plus/share_plus.dart';

import 'package:frontend_app/models/renainf_models.dart';

import 'package:frontend_app/ui/widgets/response_top_bar.dart';

import '../shared/vehicle_info_content.dart';
import 'renainf_notification_details_page.dart';

class RenainfPage extends StatelessWidget {
  const RenainfPage({
    super.key,
    required this.result,
  });

  final RenainfResult result;

  @override
  Widget build(BuildContext context) {
    final isNewFormat =
        result.occurrences.isNotEmpty || result.consulta != null || result.sourceTitle != null;

    final infractions = result.statusCode == 1
        ? result.infractions.where((infraction) => infraction.isOpen).toList()
        : result.infractions;

    final totalValue = result.summary.totalValue;
    final openValue = result.summary.openValue;
    final lastUpdate = result.summary.lastUpdatedAt != null
        ? _formatDateTime(result.summary.lastUpdatedAt)
        : (result.summary.lastUpdatedLabel ?? '—');

    String display(String? value) {
      if (value == null) return '—';
      final trimmed = value.trim();
      return trimmed.isEmpty ? '—' : trimmed;
    }

    final consulta = result.consulta;
    final summaryDescription =
        display(result.sourceTitle ?? '').replaceAll(RegExp(r'^—$'), 'Consulta RENAINF');

    final summaryChips = <VehicleSummaryChip>[
      VehicleSummaryChip(
        label: 'Período pesquisado',
        value:
            '${_formatDate(result.startDate)} • ${_formatDate(result.endDate)}',
      ),
      VehicleSummaryChip(
        label: 'UF pesquisada',
        value: result.uf,
      ),
      if (isNewFormat && display(consulta?.ufEmplacamento) != '—')
        VehicleSummaryChip(
          label: 'UF de emplacamento',
          value: display(consulta?.ufEmplacamento),
        ),
      if (isNewFormat && display(consulta?.indicadorExigibilidade) != '—')
        VehicleSummaryChip(
          label: 'Indicador de exigibilidade',
          value: display(consulta?.indicadorExigibilidade),
        ),
      if (!isNewFormat)
        VehicleSummaryChip(
          label: 'Filtro de status',
          value: result.statusLabel,
        ),
      if (isNewFormat)
        VehicleSummaryChip(
          label: 'Ocorrências encontradas',
          value: '${result.occurrencesCount ?? result.occurrences.length}',
        ),
    ];

    final summary = VehicleSummaryData(
      plate: result.plate,
      description: summaryDescription == '—' ? 'Consulta RENAINF' : summaryDescription,
      chips: summaryChips,
    );

    final sections = <VehicleInfoSectionData>[];

    if (isNewFormat) {
      sections.add(
        VehicleInfoSectionData(
          title: 'Dados da consulta',
          rows: [
            VehicleInfoRowData(
              leftLabel: 'Placa consultada',
              leftValue: display(consulta?.placa ?? result.plate),
              rightLabel: 'UF de emplacamento',
              rightValue: display(consulta?.ufEmplacamento ?? result.uf),
            ),
            VehicleInfoRowData(
              leftLabel: 'Indicador de exigibilidade',
              leftValue: display(consulta?.indicadorExigibilidade ?? result.statusLabel),
              rightLabel: 'UF pesquisada',
              rightValue: result.uf,
            ),
            VehicleInfoRowData(
              leftLabel: 'Quantidade de ocorrências',
              leftValue: '${result.occurrencesCount ?? result.occurrences.length}',
              rightLabel: 'Filtro de status',
              rightValue: result.statusLabel,
            ),
          ],
        ),
      );

      if (result.sourceTitle != null || result.sourceGeneratedAt != null) {
        sections.add(
          VehicleInfoSectionData(
            title: 'Fonte',
            rows: [
              VehicleInfoRowData(
                leftLabel: 'Sistema',
                leftValue: display(result.sourceTitle),
                rightLabel: 'Gerado em',
                rightValue: display(result.sourceGeneratedAt),
              ),
            ],
          ),
        );
      }
    } else {
      sections.add(
        VehicleInfoSectionData(
          title: 'Resumo da consulta',
          rows: [
            VehicleInfoRowData(
              leftLabel: 'Total de infrações',
              leftValue: '${infractions.length}',
              rightLabel: 'Valor total',
              rightValue: _formatCurrency(totalValue),
            ),
            VehicleInfoRowData(
              leftLabel: 'Valor em aberto',
              leftValue: _formatCurrency(openValue),
              rightLabel: 'Última atualização',
              rightValue: lastUpdate,
            ),
          ],
        ),
      );
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: ResponseTopBar(
        title: 'RENAINF',
        subtitle: 'Placa: ${result.plate}',
        onShare: () => _shareResult(context, isNewFormat),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            child: Column(
              children: [
                VehicleInfoContent(
                  summary: summary,
                  sections: sections,
                ),
                const SizedBox(height: 20),
                if (isNewFormat)
                  _RenainfOccurrencesSection(
                    occurrences: result.occurrences,
                    totalCount: result.occurrencesCount ?? result.occurrences.length,
                  )
                else
                  RenainfInfractionsSection(
                    infractions: infractions,
                  ),
                const SizedBox(height: 28),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _shareResult(BuildContext context, bool isNewFormat) async {
    bool dialogOpened = false;
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );
    dialogOpened = true;

    try {
      final generator = _RenainfPdfGenerator();
      final bytes = await generator.generate(result, isNewFormat: isNewFormat);

      if (dialogOpened && context.mounted) {
        Navigator.of(context, rootNavigator: true).pop();
        dialogOpened = false;
      }

      final sanitized = result.plate.replaceAll(RegExp(r'[^A-Za-z0-9]'), '');
      final filename =
          'pesquisa_renainf_${sanitized.isEmpty ? 'consulta' : sanitized}.pdf';

      await Share.shareXFiles(
        [
          XFile.fromData(
            bytes,
            mimeType: 'application/pdf',
            name: filename,
          ),
        ],
        text: 'Consulta RENAINF',
        subject: 'Consulta RENAINF',
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

class RenainfInfractionsSection extends StatelessWidget {
  const RenainfInfractionsSection({super.key, required this.infractions});

  final List<RenainfInfraction> infractions;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    if (infractions.isEmpty) {
      return Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
          boxShadow: const [
            BoxShadow(
              color: Color(0x0D101828),
              blurRadius: 18,
              offset: Offset(0, 10),
            ),
          ],
        ),
        padding: const EdgeInsets.all(24),
        alignment: Alignment.center,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Icon(
              Icons.fact_check_outlined,
              color: Theme.of(context).colorScheme.primary,
              size: 36,
            ),
            const SizedBox(height: 12),
            Text(
              'Nenhuma infração encontrada para o filtro selecionado.',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: const Color(0xFF475467),
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Infrações encontradas',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 12),
        for (var i = 0; i < infractions.length; i++) ...[
          if (i > 0) const SizedBox(height: 12),
          _RenainfInfractionCard(infraction: infractions[i]),
        ],
      ],
    );
  }
}

class _RenainfInfractionCard extends StatelessWidget {
  const _RenainfInfractionCard({required this.infraction});

  final RenainfInfraction infraction;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final statusColor = _statusColor(infraction.status);

    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(24),
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => RenainfNotificationDetailsPage(
                infraction: infraction,
              ),
            ),
          );
        },
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: const [
              BoxShadow(
                color: Color(0x0D101828),
                blurRadius: 18,
                offset: Offset(0, 10),
              ),
            ],
          ),
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Auto ${infraction.code}',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  Container(
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                    child: Text(
                      infraction.status,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: statusColor,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                infraction.description,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: const Color(0xFF475467),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: _RenainfInfoTile(
                      label: 'Data e hora',
                      value: _formatDateTime(
                        infraction.date,
                        fallback: infraction.dateLabel,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _RenainfInfoTile(
                      label: 'Órgão autuador',
                      value: infraction.origin,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              _RenainfInfoTile(
                label: 'Valor',
                value: _formatCurrency(infraction.amount),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _RenainfInfoTile extends StatelessWidget {
  const _RenainfInfoTile({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
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
          style: theme.textTheme.bodyMedium?.copyWith(
            color: const Color(0xFF1D2939),
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

class _RenainfOccurrencesSection extends StatelessWidget {
  const _RenainfOccurrencesSection({
    required this.occurrences,
    required this.totalCount,
  });

  final List<RenainfOccurrence> occurrences;
  final int totalCount;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Ocorrências encontradas',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 12),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: const [
              BoxShadow(
                color: Color(0x0D101828),
                blurRadius: 18,
                offset: Offset(0, 10),
              ),
            ],
          ),
          child: Column(
            children: [
              const _OccurrenceRow.header(),
              const Divider(height: 1, color: Color(0xFFE4E7EC)),
              if (occurrences.isEmpty)
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Text(
                    'Nenhuma ocorrência retornada para os filtros informados.',
                    textAlign: TextAlign.center,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: const Color(0xFF475467),
                    ),
                  ),
                )
              else
                for (var i = 0; i < occurrences.length; i++) ...[
                  if (i > 0) const Divider(height: 1, color: Color(0xFFE4E7EC)),
                  _OccurrenceRow(occurrence: occurrences[i]),
                ],
            ],
          ),
        ),
        const SizedBox(height: 12),
        Text(
          'Total informado: $totalCount',
          style: theme.textTheme.bodySmall?.copyWith(
            color: const Color(0xFF475467),
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

class _OccurrenceRow extends StatelessWidget {
  const _OccurrenceRow({required this.occurrence}) : isHeader = false;

  const _OccurrenceRow.header()
      : occurrence = null,
        isHeader = true;

  final RenainfOccurrence? occurrence;
  final bool isHeader;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final labelStyle = theme.textTheme.labelSmall?.copyWith(
      color: const Color(0xFF667085),
      fontWeight: FontWeight.w600,
    );
    final valueStyle = theme.textTheme.bodyMedium?.copyWith(
      color: const Color(0xFF1D2939),
      fontWeight: FontWeight.w600,
    );

    String display(String? value) {
      if (value == null) return '—';
      final trimmed = value.trim();
      return trimmed.isEmpty ? '—' : trimmed;
    }

    final cells = isHeader
        ? const [
            'Órgão autuador',
            'Auto de infração',
            'Infração',
            'Data da infração',
            'Exigibilidade',
          ]
        : [
            display(occurrence!.orgaoAutuador),
            display(occurrence!.autoInfracao),
            display(occurrence!.infracao),
            display(occurrence!.dataInfracao),
            display(occurrence!.exigibilidade),
          ];

    final textStyle = isHeader ? labelStyle : valueStyle;

    final weights = const [2, 3, 2, 2, 2];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      child: Row(
        children: [
          for (var i = 0; i < cells.length; i++) ...[
            Expanded(
              flex: weights[i],
              child: Text(
                cells[i],
                style: textStyle,
              ),
            ),
            if (i < cells.length - 1) const SizedBox(width: 12),
          ],
        ],
      ),
    );
  }
}

String _formatDate(DateTime? date, {String? fallback}) {
  if (date == null) {
    return fallback ?? '—';
  }
  final day = date.day.toString().padLeft(2, '0');
  final month = date.month.toString().padLeft(2, '0');
  final year = date.year.toString().padLeft(4, '0');
  return '$day/$month/$year';
}

String _formatDateTime(DateTime? dateTime, {String? fallback}) {
  if (dateTime == null) {
    return fallback ?? '—';
  }
  final date = _formatDate(dateTime);
  final hour = dateTime.hour.toString().padLeft(2, '0');
  final minute = dateTime.minute.toString().padLeft(2, '0');
  return '$date $hour:$minute';
}

String _formatCurrency(double value) {
  final formatted = value.toStringAsFixed(2).replaceAll('.', ',');
  return 'R\$ $formatted';
}

Color _statusColor(String status) {
  final normalized = status.toLowerCase();
  if (normalized.contains('abert') || normalized.contains('cobran')) {
    return const Color(0xFFD92D20);
  }
  if (normalized.contains('pago') || normalized.contains('baixad')) {
    return const Color(0xFF039855);
  }
  if (normalized.contains('recurso') || normalized.contains('defes')) {
    return const Color(0xFFF79009);
  }
  return const Color(0xFF475467);
}

class _RenainfPdfField {
  const _RenainfPdfField({required this.label, required this.value});

  final String label;
  final String value;
}

class _RenainfPdfSection {
  const _RenainfPdfSection({required this.title, required this.fields});

  final String title;
  final List<_RenainfPdfField> fields;
}

class _RenainfPdfGenerator {
  Future<Uint8List> generate(
    RenainfResult result, {
    required bool isNewFormat,
  }) async {
    final doc = pw.Document();
    final logo = await _loadLogo();
    final sections = _buildSections(result, isNewFormat);

    doc.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.fromLTRB(32, 28, 32, 36),
        build: (context) => [
          _buildHeader(logo, result),
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

  pw.Widget _buildHeader(pw.MemoryImage? logo, RenainfResult result) {
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
                'CONSULTA RENAINF',
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
          'Placa: ${result.plate}   |   UF pesquisada: ${result.uf}',
          style: const pw.TextStyle(fontSize: 11),
        ),
      ],
    );
  }

  pw.Widget _buildSection(_RenainfPdfSection section) {
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

  pw.Widget _buildFieldsGrid(List<_RenainfPdfField> fields) {
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

  pw.Widget _buildFieldCell(_RenainfPdfField field) {
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

  List<_RenainfPdfSection> _buildSections(
    RenainfResult result,
    bool isNewFormat,
  ) {
    final sections = <_RenainfPdfSection?>[];

    sections.add(
      _RenainfPdfSection(
        title: 'Dados da consulta',
        fields: _filterFields(
          [
            _field('Placa consultada', result.plate),
            _field('UF pesquisada', result.uf),
            _field(
              'Período pesquisado',
              '${_formatDate(result.startDate)} • ${_formatDate(result.endDate)}',
            ),
            _field('Status do filtro', result.statusLabel),
          ],
        ),
      ),
    );

    if (result.consulta != null) {
      sections.add(
        _RenainfPdfSection(
          title: 'Consulta RENAINF',
          fields: _filterFields(
            [
              _field('UF de emplacamento', result.consulta?.ufEmplacamento),
              _field('Indicador de exigibilidade', result.consulta?.indicadorExigibilidade),
            ],
          ),
        ),
      );
    }

    sections.add(
      _RenainfPdfSection(
        title: 'Resumo financeiro',
        fields: _filterFields(
          [
            _field('Total de infrações', result.summary.totalInfractions),
            _currencyField('Valor total', result.summary.totalValue),
            _currencyField('Valor em aberto', result.summary.openValue),
            _field(
              'Última atualização',
              _formatDateTime(
                result.summary.lastUpdatedAt,
                fallback: result.summary.lastUpdatedLabel,
              ),
            ),
          ],
        ),
      ),
    );

    if (result.sourceTitle != null || result.sourceGeneratedAt != null) {
      sections.add(
        _RenainfPdfSection(
          title: 'Fonte',
          fields: _filterFields(
            [
              _field('Sistema', result.sourceTitle),
              _field('Gerado em', result.sourceGeneratedAt),
            ],
          ),
        ),
      );
    }

    if (isNewFormat) {
      sections.add(
        _RenainfPdfSection(
          title: 'Ocorrências',
          fields: _filterFields(
            [
              _field(
                'Quantidade',
                result.occurrencesCount ?? result.occurrences.length,
              ),
              _field(
                'Indicador de exigibilidade',
                result.consulta?.indicadorExigibilidade ?? result.statusLabel,
              ),
            ],
          ),
        ),
      );
      sections.addAll(_buildOccurrenceSections(result.occurrences));
    } else {
      sections.addAll(_buildInfractionSections(result.infractions));
    }

    return sections.whereType<_RenainfPdfSection>().toList();
  }

  List<_RenainfPdfSection> _buildOccurrenceSections(
    List<RenainfOccurrence> occurrences,
  ) {
    final sections = <_RenainfPdfSection>[];
    for (var i = 0; i < occurrences.length; i++) {
      final occurrence = occurrences[i];
      sections.add(
        _RenainfPdfSection(
          title: 'Ocorrência ${i + 1}',
          fields: _filterFields(
            [
              _field('Órgão autuador', occurrence.orgaoAutuador),
              _field('Auto de infração', occurrence.autoInfracao),
              _field('Infração', occurrence.infracao),
              _field('Data da infração', occurrence.dataInfracao),
              _field('Exigibilidade', occurrence.exigibilidade),
            ],
          ),
        ),
      );
    }
    return sections;
  }

  List<_RenainfPdfSection> _buildInfractionSections(
    List<RenainfInfraction> infractions,
  ) {
    final sections = <_RenainfPdfSection>[];
    for (var i = 0; i < infractions.length; i++) {
      final infraction = infractions[i];
      sections.add(
        _RenainfPdfSection(
          title: 'Infração ${i + 1}',
          fields: _filterFields(
            [
              _field('Auto', infraction.code),
              _field('Descrição', infraction.description),
              _field('Status', infraction.status),
              _currencyField('Valor', infraction.amount),
              _field('Data', infraction.date != null
                  ? _formatDate(infraction.date)
                  : infraction.dateLabel),
              _field('Órgão autuador', infraction.origin),
              _field('Local', infraction.local),
              _field('Município placa', infraction.municipioPlaca),
              _field('Classificação', infraction.classificacao),
              _field('Tipo de auto', infraction.tipoAuto),
            ],
          ),
        ),
      );
    }
    return sections;
  }
}

List<_RenainfPdfField> _filterFields(List<_RenainfPdfField?> fields) {
  return fields.whereType<_RenainfPdfField>().toList();
}

_RenainfPdfField? _field(String label, dynamic value) {
  final text = _formatDisplayValue(value);
  if (text == '—') return null;
  return _RenainfPdfField(label: label, value: text);
}

_RenainfPdfField? _currencyField(String label, double? value) {
  if (value == null) return null;
  return _RenainfPdfField(label: label, value: _formatCurrency(value));
}

String _formatDisplayValue(dynamic value) {
  if (value == null) return '—';
  if (value is String) {
    final trimmed = value.trim();
    if (trimmed.isEmpty || RegExp(r'^-+$').hasMatch(trimmed)) {
      return '—';
    }
    return trimmed;
  }
  if (value is num) {
    return value.toString();
  }
  if (value is bool) {
    return value ? 'Sim' : 'Não';
  }
  return value.toString();
}

String _twoDigits(int value) => value.toString().padLeft(2, '0');
