import 'package:flutter/material.dart';

import 'gravame_outros_estados_page.dart';
import 'multas_outros_estados_page.dart';
import 'restricoes_outros_estados_page.dart';
import 'vehicle_info_outros_estados_page.dart';

class BaseOutrosEstadosPage extends StatelessWidget {
  const BaseOutrosEstadosPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: const [
              _HeaderSection(),
              SizedBox(height: 20),
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 20),
                child: Column(
                  children: [
                    _VehicleSummaryCard(),
                    SizedBox(height: 16),
                    _OutrosEstadosOptionList(),
                    SizedBox(height: 24),
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

class _HeaderSection extends StatelessWidget {
  const _HeaderSection();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      decoration: const BoxDecoration(
        color: Color(0xFF123D99),
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(32)),
      ),
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 28),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 24,
                backgroundColor: Colors.white,
                child: Padding(
                  padding: const EdgeInsets.all(4),
                  child: Image.asset(
                    'assets/images/logoLL.png',
                    fit: BoxFit.contain,
                  ),
                ),
              ),
              const Spacer(),
              Container(
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: IconButton(
                  onPressed: () {},
                  icon: const Icon(
                    Icons.share_outlined,
                    color: Colors.white,
                    size: 20,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          InkWell(
            borderRadius: BorderRadius.circular(24),
            onTap: () => Navigator.of(context).maybePop(),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(
                  Icons.arrow_back_ios_new,
                  color: Colors.white,
                  size: 18,
                ),
                const SizedBox(width: 8),
                Text(
                  'Base de Outros Estados',
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: Colors.white,
                    fontSize: 18,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _VehicleSummaryCard extends StatelessWidget {
  const _VehicleSummaryCard();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    Widget buildInfo(String label, String value) {
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
              fontWeight: FontWeight.w600,
              color: const Color(0xFF1D2939),
            ),
          ),
        ],
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
                  color: const Color(0xFFF2F6FF),
                  borderRadius: BorderRadius.circular(18),
                ),
                padding: const EdgeInsets.all(16),
                child: const Icon(
                  Icons.directions_car_filled_outlined,
                  color: Color(0xFF123D99),
                  size: 36,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'GEP-1E11',
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Hyundai HB20 Vision\n2021/2022',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: const Color(0xFF475467),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          const Divider(color: Color(0xFFE4E7EC)),
          const SizedBox(height: 16),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: buildInfo('Proprietário', 'L B DE LIMA VISTORIAS'),
              ),
              const SizedBox(width: 16),
              Expanded(child: buildInfo('CPF/CNPJ', '000.000.000-00')),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(child: buildInfo('Município', 'Guarujá/SP')),
              const SizedBox(width: 16),
              Expanded(
                child: buildInfo('Situação do veículo', 'Em circulação'),
              ),
            ],
          ),
          const SizedBox(height: 12),
          buildInfo('Última atualização', '00/00/0000'),
        ],
      ),
    );
  }
}

class _OutrosEstadosOptionList extends StatelessWidget {
  const _OutrosEstadosOptionList();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: const [
        _OutrosEstadosOption(
          icon: Icons.directions_car_outlined,
          label: 'Características do Veículo',
          destinationBuilder: VehicleInfoOutrosEstadosPage.new,
        ),
        SizedBox(height: 12),
        _OutrosEstadosOption(
          icon: Icons.assignment_outlined,
          label: 'Gravame',
          destinationBuilder: GravameOutrosEstadosPage.new,
        ),
        SizedBox(height: 12),
        _OutrosEstadosOption(
          icon: Icons.warning_amber_outlined,
          label: 'Multas e débitos',
          destinationBuilder: MultasOutrosEstadosPage.new,
        ),
        SizedBox(height: 12),
        _OutrosEstadosOption(
          icon: Icons.lock_outline,
          label: 'Restrições',
          destinationBuilder: RestricoesOutrosEstadosPage.new,
        ),
      ],
    );
  }
}

class _OutrosEstadosOption extends StatelessWidget {
  const _OutrosEstadosOption({
    required this.icon,
    required this.label,
    required this.destinationBuilder,
  });

  final IconData icon;
  final String label;
  final Widget Function() destinationBuilder;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: () {
          Navigator.of(
            context,
          ).push(MaterialPageRoute(builder: (_) => destinationBuilder()));
        },
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(18),
            boxShadow: const [
              BoxShadow(
                color: Color(0x0D101828),
                blurRadius: 12,
                offset: Offset(0, 6),
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
                    fontWeight: FontWeight.w600,
                    color: const Color(0xFF1D2939),
                  ),
                ),
              ),
              const Icon(Icons.chevron_right, color: Color(0xFF98A2B3)),
            ],
          ),
        ),
      ),
    );
  }
}
