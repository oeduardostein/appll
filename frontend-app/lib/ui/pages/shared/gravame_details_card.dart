import 'package:flutter/material.dart';

class GravameDetailsCard extends StatelessWidget {
  const GravameDetailsCard({
    super.key,
    this.highlightBorder = false,
    this.borderColor = const Color(0xFF1D4ED8),
    this.iconBackgroundColor = const Color(0xFFF2F6FF),
    this.iconColor = const Color(0xFF123D99),
  });

  final bool highlightBorder;
  final Color borderColor;
  final Color iconBackgroundColor;
  final Color iconColor;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

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
                    value,
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
        border: highlightBorder
            ? Border.all(color: borderColor, width: 2)
            : null,
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
                      'Gravame: Ativo',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontSize: 18,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Inclusão: 07/06/2022',
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
            'Alienação Fiduciária',
            trailing: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFFFFF7E6),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Text(
                'Ativo',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: const Color(0xFFB78103),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ),
          const Divider(color: Color(0xFFE4E7EC)),
          buildRow('Agente Financeiro', 'Bradesco ADM Consórcio LTDA'),
          const Divider(color: Color(0xFFE4E7EC)),
          buildRow('Arrendatário / Financiado', 'L B de Lima Vistorias ME'),
          const Divider(color: Color(0xFFE4E7EC)),
          buildRow('CNPJ do Financiado', '24.836.877/0001-85'),
        ],
      ),
    );
  }
}
