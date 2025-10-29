import 'package:flutter/material.dart';

import '../fines/fines_page.dart';
import '../gravame/gravame_page.dart';
import 'comunicacao_venda_page.dart';
import 'restricoes_page.dart';
import 'vehicle_info_page.dart';

class BaseEstadualPage extends StatelessWidget {
  const BaseEstadualPage({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: [
              Container(
                decoration: const BoxDecoration(
                  color: Color(0xFF123D99),
                  borderRadius: BorderRadius.vertical(
                    bottom: Radius.circular(32),
                  ),
                ),
                padding: const EdgeInsets.fromLTRB(20, 24, 20, 28),
                child: Column(
                  children: [
                    Row(
                      children: [
                        IconButton(
                          onPressed: () => Navigator.of(context).maybePop(),
                          icon: const Icon(
                            Icons.arrow_back_ios_new,
                            color: Colors.white,
                            size: 18,
                          ),
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            'Base estadual',
                            style: theme.textTheme.titleMedium?.copyWith(
                              color: Colors.white,
                              fontSize: 18,
                            ),
                          ),
                        ),
                        IconButton(
                          onPressed: () {},
                          icon: const Icon(
                            Icons.share_outlined,
                            color: Colors.white,
                            size: 22,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    _VehicleSummaryCard(colorScheme: colorScheme),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Column(
                  children: [
                    _BaseEstadualOption(
                      icon: Icons.directions_car_outlined,
                      label: 'Informações do veículo',
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => const VehicleInfoPage(),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 12),
                    _BaseEstadualOption(
                      icon: Icons.assignment_outlined,
                      label: 'Gravame',
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => const GravamePage(),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 12),
                    _BaseEstadualOption(
                      icon: Icons.warning_amber_outlined,
                      label: 'Multas e débitos',
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const FinesPage()),
                        );
                      },
                    ),
                    const SizedBox(height: 12),
                    _BaseEstadualOption(
                      icon: Icons.lock_outline,
                      label: 'Restrições',
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => const RestricoesPage(),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 12),
                    _BaseEstadualOption(
                      icon: Icons.receipt_long_outlined,
                      label: 'Comunicações de venda',
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => const ComunicacaoVendaPage(),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 24),
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

class _VehicleSummaryCard extends StatelessWidget {
  const _VehicleSummaryCard({required this.colorScheme});

  final ColorScheme colorScheme;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: const Color(0x0D101828),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFF2F6FF),
                  borderRadius: BorderRadius.circular(18),
                ),
                padding: const EdgeInsets.all(12),
                child: Icon(
                  Icons.directions_car_filled_outlined,
                  size: 36,
                  color: colorScheme.primary,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'GEP-1E11',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontSize: 18,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Hyundai HB20 Vision\n2021/2022',
                      style: theme.textTheme.bodyMedium,
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: _VehicleDetailTile(
                  title: 'Licenciamento',
                  value: '2024',
                  status: 'em dia',
                  statusColor: const Color(0xFF12B76A),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _VehicleDetailTile(
                  title: 'Município',
                  value: 'Guarujá / SP',
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          _VehicleOwnerTile(colorScheme: colorScheme),
        ],
      ),
    );
  }
}

class _VehicleDetailTile extends StatelessWidget {
  const _VehicleDetailTile({
    required this.title,
    required this.value,
    this.status,
    this.statusColor,
  });

  final String title;
  final String value;
  final String? status;
  final Color? statusColor;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: theme.textTheme.bodySmall?.copyWith(
            color: const Color(0xFF667085),
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(height: 4),
        Row(
          children: [
            Text(value, style: theme.textTheme.titleMedium),
            if (status != null) ...[
              const SizedBox(width: 6),
              Text(
                status!,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: statusColor ?? const Color(0xFF12B76A),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ],
        ),
      ],
    );
  }
}

class _VehicleOwnerTile extends StatelessWidget {
  const _VehicleOwnerTile({required this.colorScheme});

  final ColorScheme colorScheme;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8F9FC),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Proprietário',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: const Color(0xFF667085),
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                'LB DE LIMA VISTORIAS M...',
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: const Color(0xFF1D2939),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          const Spacer(),
          TextButton(
            onPressed: () {},
            style: TextButton.styleFrom(
              foregroundColor: colorScheme.primary,
              textStyle: const TextStyle(fontWeight: FontWeight.w600),
            ),
            child: const Text('Ver completo'),
          ),
        ],
      ),
    );
  }
}

class _BaseEstadualOption extends StatelessWidget {
  const _BaseEstadualOption({
    required this.icon,
    required this.label,
    this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(18),
            boxShadow: [
              BoxShadow(
                color: const Color(0x0D101828),
                blurRadius: 12,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Row(
            children: [
              Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFF2F6FF),
                  borderRadius: BorderRadius.circular(14),
                ),
                padding: const EdgeInsets.all(10),
                child: Icon(icon, color: colorScheme.primary, size: 22),
              ),
              const SizedBox(width: 18),
              Expanded(
                child: Text(
                  label,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: const Color(0xFF1D2939),
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              const Icon(
                Icons.arrow_forward_ios_rounded,
                color: Color(0xFF98A2B3),
                size: 18,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
