import 'package:flutter/material.dart';

import '../shared/common_page_header.dart';
import '../shared/vehicle_info_content.dart';
import 'renainf_page.dart';

class RenainfNotificationDetailsPage extends StatelessWidget {
  const RenainfNotificationDetailsPage({super.key, required this.infraction});

  final RenainfInfraction infraction;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    final summary = VehicleSummaryData(
      plate: 'BUL8C39',
      description: infraction.modelDescription,
      chips: [
        VehicleSummaryChip(
          label: 'Município da placa',
          value: infraction.municipioPlaca,
        ),
        VehicleSummaryChip(
          label: 'UF Jurisdição',
          value: infraction.ufJuridica,
        ),
      ],
    );

    final sections = [
      VehicleInfoSectionData(
        title: 'Dados da Infração',
        rows: [
          VehicleInfoRowData(
            leftLabel: 'Órgão autuador',
            leftValue: infraction.origin,
            rightLabel: 'UF do órgão',
            rightValue: infraction.ufJuridica,
          ),
          VehicleInfoRowData(
            leftLabel: 'Código',
            leftValue: infraction.codigoInfracao,
            rightLabel: 'Auto de infração',
            rightValue: infraction.code,
          ),
          VehicleInfoRowData(
            leftLabel: 'Data/hora',
            leftValue: _formatDateTime(infraction.date),
            rightLabel: 'Data cadastro',
            rightValue: infraction.dataCadastro,
          ),
          VehicleInfoRowData(
            leftLabel: 'Local',
            leftValue: 'Est Vercelino Onílio Lemos, 19',
            rightLabel: 'Valor da infração',
            rightValue: _formatCurrency(infraction.amount),
          ),
          VehicleInfoRowData(
            leftLabel: 'Tipo auto',
            leftValue: 'Auto',
            rightLabel: 'Data emissão penalidade',
            rightValue: infraction.dataEmissao,
          ),
        ],
      ),
      VehicleInfoSectionData(
        title: 'Dados do Pagamento',
        rows: [
          VehicleInfoRowData(
            leftLabel: 'UF pagamento',
            leftValue: infraction.ufJuridica,
            rightLabel: 'Data pagamento',
            rightValue: infraction.valorPago > 0 ? '28/03/2018' : 'Não pago',
          ),
          VehicleInfoRowData(
            leftLabel: 'Valor pago',
            leftValue: _formatCurrency(infraction.valorPago),
            rightLabel: 'Data registro pagamento',
            rightValue: infraction.valorPago > 0 ? '28/03/2018' : '—',
          ),
        ],
      ),
      VehicleInfoSectionData(
        title: 'Dados do Infrator/Condutor',
        rows: const [
          VehicleInfoRowData(
            leftLabel: 'CNH infrator',
            leftValue: '00000000000',
            rightLabel: 'CNH condutor',
            rightValue: '00000000000',
          ),
        ],
      ),
      VehicleInfoSectionData(
        title: 'Suspensão/Cancelamento',
        rows: const [
          VehicleInfoRowData(
            leftLabel: 'Tipo',
            leftValue: 'Não consta',
            rightLabel: 'Data registro',
            rightValue: 'Não consta',
          ),
          VehicleInfoRowData(
            leftLabel: 'Origem',
            leftValue: 'Não consta',
            rightLabel: 'Aceito UF jurisdição',
            rightValue: 'Não consta',
          ),
        ],
      ),
    ];

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: [
              const CommonPageHeader(
                title: 'Detalhes da Notificação',
                bottomPadding: 24,
              ),
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
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
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
                          Text(
                            'Descrição da infração',
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.w700,
                              color: colorScheme.primary,
                            ),
                          ),
                          const SizedBox(height: 12),
                          Text(
                            infraction.classificacao,
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: theme.textTheme.bodyMedium?.color,
                            ),
                          ),
                        ],
                      ),
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

String _formatDateTime(DateTime dateTime) {
  final date = _formatDate(dateTime);
  final hour = dateTime.hour.toString().padLeft(2, '0');
  final minute = dateTime.minute.toString().padLeft(2, '0');
  return '$date $hour:$minute';
}

String _formatDate(DateTime date) {
  final day = date.day.toString().padLeft(2, '0');
  final month = date.month.toString().padLeft(2, '0');
  final year = date.year.toString();
  return '$day/$month/$year';
}

String _formatCurrency(double value) {
  final formatted = value.toStringAsFixed(2).replaceAll('.', ',');
  return 'R\$ $formatted';
}
