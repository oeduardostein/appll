import 'package:flutter/material.dart';

import 'package:frontend_app/models/renainf_models.dart';

import '../shared/common_page_header.dart';
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
    final infractions = result.statusCode == 1
        ? result.infractions.where((infraction) => infraction.isOpen).toList()
        : result.infractions;

    final totalValue = result.summary.totalValue;
    final openValue = result.summary.openValue;
    final lastUpdate = result.summary.lastUpdatedAt != null
        ? _formatDateTime(result.summary.lastUpdatedAt)
        : (result.summary.lastUpdatedLabel ?? '—');

    final summary = VehicleSummaryData(
      plate: result.plate,
      description: 'Consulta RENAINF',
      chips: [
        VehicleSummaryChip(
          label: 'Período pesquisado',
          value:
              '${_formatDate(result.startDate)} • ${_formatDate(result.endDate)}',
        ),
        VehicleSummaryChip(
          label: 'Filtro de status',
          value: result.statusLabel,
        ),
        VehicleSummaryChip(
          label: 'UF',
          value: result.uf,
        ),
      ],
    );

    final sections = [
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
    ];

    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: [
              const CommonPageHeader(title: 'RENAINF', bottomPadding: 24),
              const SizedBox(height: 20),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Column(
                  children: [
                    VehicleInfoContent(
                      summary: summary,
                      sections: sections,
                    ),
                    const SizedBox(height: 20),
                    RenainfInfractionsSection(
                      infractions: infractions,
                    ),
                    const SizedBox(height: 28),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
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
