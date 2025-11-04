import 'package:flutter/material.dart';

class GravameDetailsCard extends StatelessWidget {
  const GravameDetailsCard({
    super.key,
    required this.statusLabel,
    required this.inclusionDate,
    required this.restricaoFinanceira,
    required this.agenteFinanceiro,
    required this.nomeFinanciado,
    required this.documentoFinanciado,
    this.arrendatario,
    this.iconBackgroundColor = const Color(0xFFF2F6FF),
    this.iconColor = const Color(0xFF123D99),
  });

  final String statusLabel;
  final String inclusionDate;
  final String restricaoFinanceira;
  final String agenteFinanceiro;
  final String nomeFinanciado;
  final String documentoFinanciado;
  final String? arrendatario;
  final Color iconBackgroundColor;
  final Color iconColor;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final normalizedStatus = statusLabel.trim().toLowerCase();

    final bool isActive =
        normalizedStatus == 'ativo' || normalizedStatus == 'ativo(a)';
    final Color statusBackground = isActive
        ? const Color(0xFFFFF7E6)
        : const Color(0xFFE4E7EC);
    final Color statusColor =
        isActive ? const Color(0xFFB78103) : const Color(0xFF475467);

    Widget buildRow(String label, String value, {Widget? trailing}) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
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
                    value.isEmpty ? '—' : value,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: const Color(0xFF1D2939),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
            if (trailing != null) trailing,
          ],
        ),
      );
    }

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
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                decoration: BoxDecoration(
                  color: iconBackgroundColor,
                  borderRadius: BorderRadius.circular(18),
                ),
                padding: const EdgeInsets.all(16),
                child: Icon(Icons.credit_card, color: iconColor, size: 32),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Gravame: $statusLabel',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontSize: 18,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Inclusão: $inclusionDate',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: const Color(0xFF475467),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          const Divider(color: Color(0xFFE4E7EC)),
          buildRow(
            'Restrição Financeira',
            restricaoFinanceira,
            trailing: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: statusBackground,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Text(
                statusLabel,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: statusColor,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ),
          const Divider(color: Color(0xFFE4E7EC)),
          buildRow('Agente Financeiro', agenteFinanceiro),
          const Divider(color: Color(0xFFE4E7EC)),
          buildRow('Nome do Financiado', nomeFinanciado),
          if (arrendatario != null && arrendatario!.isNotEmpty) ...[
            const Divider(color: Color(0xFFE4E7EC)),
            buildRow('Arrendatário / Financiado', arrendatario!),
          ],
          const Divider(color: Color(0xFFE4E7EC)),
          buildRow('Documento do Financiado', documentoFinanciado),
        ],
      ),
    );
  }
}
