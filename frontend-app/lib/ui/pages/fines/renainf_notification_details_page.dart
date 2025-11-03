import 'package:flutter/material.dart';

import 'package:frontend_app/models/renainf_models.dart';

import '../shared/common_page_header.dart';
import '../shared/vehicle_info_content.dart';

class RenainfNotificationDetailsPage extends StatelessWidget {
  const RenainfNotificationDetailsPage({super.key, required this.infraction});

  final RenainfInfraction infraction;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    String display(String value, {String fallback = '—'}) {
      final trimmed = value.trim();
      if (trimmed.isEmpty || trimmed == '—') {
        return fallback;
      }
      return trimmed;
    }

    final summary = VehicleSummaryData(
      plate: infraction.plate,
      description: infraction.modelDescription,
      chips: [
        VehicleSummaryChip(
          label: 'Município da placa',
          value: display(infraction.municipioPlaca),
        ),
        VehicleSummaryChip(
          label: 'UF Jurisdição',
          value: display(infraction.ufJuridica),
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
            leftValue: _formatDateTime(
              infraction.date,
              fallback: infraction.dateLabel,
            ),
            rightLabel: 'Data cadastro',
            rightValue: display(infraction.dataCadastro),
          ),
          VehicleInfoRowData(
            leftLabel: 'Local',
            leftValue: display(infraction.local),
            rightLabel: 'Valor da infração',
            rightValue: _formatCurrency(infraction.amount),
          ),
          VehicleInfoRowData(
            leftLabel: 'Tipo auto',
            leftValue: display(infraction.tipoAuto),
            rightLabel: 'Data emissão penalidade',
            rightValue: display(infraction.dataEmissao),
          ),
        ],
      ),
      VehicleInfoSectionData(
        title: 'Dados do Pagamento',
        rows: [
          VehicleInfoRowData(
            leftLabel: 'UF pagamento',
            leftValue: display(infraction.ufPagamento),
            rightLabel: 'Data pagamento',
            rightValue: display(
              infraction.dataPagamento,
              fallback: 'Não informado',
            ),
          ),
          VehicleInfoRowData(
            leftLabel: 'Valor pago',
            leftValue: _formatCurrency(infraction.valorPago),
            rightLabel: 'Data registro pagamento',
            rightValue: display(infraction.dataRegistroPagamento),
          ),
        ],
      ),
      VehicleInfoSectionData(
        title: 'Dados do Infrator/Condutor',
        rows: [
          VehicleInfoRowData(
            leftLabel: 'CNH infrator',
            leftValue: display(
              infraction.cnhInfrator,
              fallback: 'Não informado',
            ),
            rightLabel: 'CNH condutor',
            rightValue: display(
              infraction.cnhCondutor,
              fallback: 'Não informado',
            ),
          ),
        ],
      ),
      VehicleInfoSectionData(
        title: 'Suspensão/Cancelamento',
        rows: [
          VehicleInfoRowData(
            leftLabel: 'Tipo',
            leftValue: display(
              infraction.suspensaoTipo,
              fallback: 'Não consta',
            ),
            rightLabel: 'Data registro',
            rightValue: display(
              infraction.suspensaoDataRegistro,
              fallback: 'Não consta',
            ),
          ),
          VehicleInfoRowData(
            leftLabel: 'Origem',
            leftValue: display(
              infraction.suspensaoOrigem,
              fallback: 'Não consta',
            ),
            rightLabel: 'Aceito UF jurisdição',
            rightValue: display(
              infraction.suspensaoAceitoUf,
              fallback: 'Não consta',
            ),
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
                            display(
                              infraction.classificacao,
                              fallback: infraction.description,
                            ),
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

String _formatDateTime(DateTime? dateTime, {String? fallback}) {
  if (dateTime == null) {
    return fallback ?? '—';
  }
  final date = _formatDate(dateTime);
  final hour = dateTime.hour.toString().padLeft(2, '0');
  final minute = dateTime.minute.toString().padLeft(2, '0');
  return '$date $hour:$minute';
}

String _formatDate(DateTime? date) {
  if (date == null) return '—';
  final day = date.day.toString().padLeft(2, '0');
  final month = date.month.toString().padLeft(2, '0');
  final year = date.year.toString();
  return '$day/$month/$year';
}

String _formatCurrency(double value) {
  final formatted = value.toStringAsFixed(2).replaceAll('.', ',');
  return 'R\$ $formatted';
}
