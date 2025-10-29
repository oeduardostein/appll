import 'package:flutter/material.dart';

import '../shared/common_page_header.dart';

class BloqueiosPage extends StatelessWidget {
  const BloqueiosPage({
    super.key,
    required this.origin,
    this.plate,
    this.chassi,
  });

  final String origin;
  final String? plate;
  final String? chassi;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final textTheme = theme.textTheme;

    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      body: SafeArea(
        child: CustomScrollView(
          slivers: [
            SliverToBoxAdapter(
              child: Column(
                children: [
                  CommonPageHeader(
                    title: 'Bloqueios ativos',
                    bottomPadding: 32,
                  ),
                  const SizedBox(height: 24),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    child: _ResultCard(
                      origin: origin,
                      plate: plate,
                      chassi: chassi,
                      colorScheme: colorScheme,
                      textTheme: textTheme,
                    ),
                  ),
                  const SizedBox(height: 32),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ResultCard extends StatelessWidget {
  const _ResultCard({
    required this.origin,
    required this.plate,
    required this.chassi,
    required this.colorScheme,
    required this.textTheme,
  });

  final String origin;
  final String? plate;
  final String? chassi;
  final ColorScheme colorScheme;
  final TextTheme textTheme;

  @override
  Widget build(BuildContext context) {
    final highlightColor = colorScheme.primary;

    Widget buildSection({
      required String label,
      required String value,
    }) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label.toUpperCase(),
            style: textTheme.bodySmall?.copyWith(
              color: highlightColor,
              letterSpacing: 0.8,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            value,
            style: textTheme.bodyLarge?.copyWith(
              color: const Color(0xFF1D2939),
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      );
    }

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: const Color(0x1410283A),
            blurRadius: 24,
            offset: const Offset(0, 16),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(24, 24, 24, 32),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: highlightColor.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(24),
            ),
            child: Text(
              'Resultado ${origin.toUpperCase()}',
              style: textTheme.labelMedium?.copyWith(
                color: highlightColor,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const SizedBox(height: 24),
          if (plate != null)
            buildSection(
              label: 'Placa',
              value: plate!,
            ),
          if (plate != null) const SizedBox(height: 16),
          if (chassi != null)
            buildSection(
              label: 'Chassi',
              value: chassi!,
            ),
          const SizedBox(height: 24),
          buildSection(
            label: 'Bloqueio',
            value: 'Bloqueio $origin',
          ),
          const SizedBox(height: 16),
          buildSection(
            label: 'Data da inclusão',
            value: '24/10/2025',
          ),
          const SizedBox(height: 16),
          buildSection(
            label: 'Nº do processo',
            value: '000000000000000000000/',
          ),
          const SizedBox(height: 16),
          buildSection(
            label: 'Município do bloqueio',
            value: 'Não encontrado',
          ),
          const SizedBox(height: 16),
          buildSection(
            label: 'Motivo do bloqueio',
            value:
                'Veículo bloqueado para regularização: apresentar CRV anterior, ATPV-e ou nota fiscal e decalque do chassi conforme art.124 (incisos I e II) do CTB.',
          ),
        ],
      ),
    );
  }
}

