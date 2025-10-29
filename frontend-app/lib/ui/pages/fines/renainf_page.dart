import 'package:flutter/material.dart';

import '../shared/common_page_header.dart';
import '../shared/vehicle_info_content.dart';
import 'renainf_notification_details_page.dart';

class RenainfPage extends StatelessWidget {
  const RenainfPage({
    super.key,
    required this.plate,
    required this.status,
    required this.startDate,
    required this.endDate,
  });

  final String plate;
  final String status;
  final DateTime startDate;
  final DateTime endDate;

  @override
  Widget build(BuildContext context) {
    final filteredInfractions = _mockInfractions.where((infraction) {
      if (status == 'Todas') return true;
      return infraction.status == status;
    }).toList();

    final totalValue = filteredInfractions.fold<double>(
      0,
      (sum, infraction) => sum + infraction.amount,
    );

    final openValue = filteredInfractions
        .where((infraction) => infraction.status == 'Em aberto')
        .fold<double>(0, (sum, infraction) => sum + infraction.amount);

    final summary = VehicleSummaryData(
      plate: plate,
      description: 'Consulta RENAINF',
      chips: [
        VehicleSummaryChip(
          label: 'Período pesquisado',
          value: '${_formatDate(startDate)} • ${_formatDate(endDate)}',
        ),
        VehicleSummaryChip(
          label: 'Filtro de status',
          value: status,
        ),
      ],
    );

    final sections = [
      VehicleInfoSectionData(
        title: 'Resumo da consulta',
        rows: [
          VehicleInfoRowData(
            leftLabel: 'Total de infrações',
            leftValue: '${filteredInfractions.length}',
            rightLabel: 'Valor total',
            rightValue: _formatCurrency(totalValue),
          ),
          VehicleInfoRowData(
            leftLabel: 'Valor em aberto',
            leftValue: _formatCurrency(openValue),
            rightLabel: 'Última atualização',
            rightValue: _formatDateTime(DateTime.now()),
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
                      infractions: filteredInfractions,
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
                      value: _formatDateTime(infraction.date),
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

class RenainfInfraction {
  const RenainfInfraction({
    required this.code,
    required this.description,
    required this.status,
    required this.date,
    required this.amount,
    required this.origin,
    this.municipioPlaca = 'Criciúma / SC',
    this.ufJuridica = 'SC',
    this.modelDescription = 'Hyundai HB20 Vision',
    this.codigoInfracao = '5550',
    this.classificacao = 'Estacionar em local/horário proibidos pela sinalização',
    this.dataCadastro = '07/02/2017',
    this.dataEmissao = '24/03/2017',
    this.valorPago = 0,
  });

  final String code;
  final String description;
  final String status;
  final DateTime date;
  final double amount;
  final String origin;
  final String municipioPlaca;
  final String ufJuridica;
  final String modelDescription;
  final String codigoInfracao;
  final String classificacao;
  final String dataCadastro;
  final String dataEmissao;
  final double valorPago;
}

final List<RenainfInfraction> _mockInfractions = [
  RenainfInfraction(
    code: '746-61',
    description:
        'Transitar em velocidade superior à máxima permitida em até 20%.',
    status: 'Em aberto',
    date: DateTime(2024, 6, 14, 14, 18),
    amount: 195.23,
    origin: 'DER - SC',
    municipioPlaca: 'Criciúma / SC',
    ufJuridica: 'SC',
    modelDescription: 'Hyundai HB20 Vision',
    codigoInfracao: '74661',
    classificacao: 'Velocidade superior à máxima permitida em até 20%.',
    dataCadastro: '15/06/2024',
    dataEmissao: '20/06/2024',
  ),
  RenainfInfraction(
    code: '518-00',
    description: 'Avançar o sinal vermelho do semáforo.',
    status: 'Pago',
    date: DateTime(2024, 3, 2, 9, 42),
    amount: 230.57,
    origin: 'CET - PR',
    municipioPlaca: 'Joinville / SC',
    ufJuridica: 'PR',
    modelDescription: 'Chevrolet Onix LTZ',
    codigoInfracao: '51800',
    classificacao: 'Avançar o sinal vermelho do semáforo.',
    dataCadastro: '05/03/2024',
    dataEmissao: '12/03/2024',
    valorPago: 230.57,
  ),
  RenainfInfraction(
    code: '554-02',
    description:
        'Estacionar em local sinalizado com proibição ou regulamentação específica.',
    status: 'Em recurso',
    date: DateTime(2023, 11, 20, 18, 5),
    amount: 130.16,
    origin: 'SMT - RS',
    municipioPlaca: 'Porto Alegre / RS',
    ufJuridica: 'RS',
    modelDescription: 'Volkswagen Gol',
    codigoInfracao: '55402',
    classificacao:
        'Estacionar em local sinalizado com proibição ou regulamentação específica.',
    dataCadastro: '25/11/2023',
    dataEmissao: '10/12/2023',
  ),
];

String _formatDate(DateTime date) {
  final day = date.day.toString().padLeft(2, '0');
  final month = date.month.toString().padLeft(2, '0');
  final year = date.year.toString();
  return '$day/$month/$year';
}

String _formatDateTime(DateTime dateTime) {
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
  switch (status) {
    case 'Em aberto':
      return const Color(0xFFD92D20);
    case 'Pago':
      return const Color(0xFF039855);
    case 'Em recurso':
      return const Color(0xFFF79009);
    default:
      return const Color(0xFF475467);
  }
}
