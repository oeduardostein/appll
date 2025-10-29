import 'package:flutter/material.dart';

import '../shared/common_page_header.dart';

class EcrvProcessPage extends StatelessWidget {
  const EcrvProcessPage({
    super.key,
    this.plate,
    this.chassi,
  });

  final String? plate;
  final String? chassi;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: [
              CommonPageHeader(
                title: 'Andamento do processo e-CRV',
                bottomPadding: 16,
              ),
              const SizedBox(height: 20),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Column(
                  children: [
                    const _SectionHeader(label: 'Ficha cadastral'),
                    const SizedBox(height: 20),
                    _ProcessInfoGrid(
                      plate: plate,
                      chassi: chassi,
                    ),
                    const SizedBox(height: 24),
                    const _StatusHighlightCard(),
                  ],
                ),
              ),
              const SizedBox(height: 32),
            ],
          ),
        ),
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        color: const Color(0xFFE7EDFF),
        borderRadius: BorderRadius.circular(24),
      ),
      padding: const EdgeInsets.symmetric(vertical: 12),
      alignment: Alignment.center,
      child: Text(
        label,
        style: textTheme.titleMedium?.copyWith(
          color: const Color(0xFF123D99),
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _ProcessInfoGrid extends StatelessWidget {
  const _ProcessInfoGrid({
    required this.plate,
    required this.chassi,
  });

  final String? plate;
  final String? chassi;

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    Widget buildField(String label, String value) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label.toUpperCase(),
            style: textTheme.bodySmall?.copyWith(
              color: const Color(0xFF667085),
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 6),
          Container(
            width: double.infinity,
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
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
            child: Text(
              value,
              style: textTheme.bodyLarge?.copyWith(
                color: const Color(0xFF1D2939),
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (plate != null) buildField('Placa', plate!),
        if (plate != null) const SizedBox(height: 16),
        if (chassi != null) buildField('Chassi', chassi!),
        if (chassi != null) const SizedBox(height: 16),
        buildField('Opção', 'Transferência de município/estado'),
        const SizedBox(height: 16),
        buildField('Número da ficha e-CRV', '16035801 / 2025'),
      ],
    );
  }
}

class _StatusHighlightCard extends StatelessWidget {
  const _StatusHighlightCard();

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;
    final primary = Theme.of(context).colorScheme.primary;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
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
                'Status',
                style: textTheme.bodySmall?.copyWith(
                  color: const Color(0xFF667085),
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  color: primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(16),
                ),
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                child: Text(
                  'Ficha cadastral recebida sistema',
                  style: textTheme.bodyLarge?.copyWith(
                    color: primary,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                'Último andamento',
                style: textTheme.bodySmall?.copyWith(
                  color: const Color(0xFF667085),
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Documento protocolado e aguardando validação da equipe.',
                style: textTheme.bodyMedium?.copyWith(
                  color: const Color(0xFF475467),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
