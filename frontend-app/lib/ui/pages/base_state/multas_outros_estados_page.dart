import 'package:flutter/material.dart';

class MultasOutrosEstadosPage extends StatelessWidget {
  const MultasOutrosEstadosPage({super.key});

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
                    _AlertCard(),
                    SizedBox(height: 16),
                    _DebitsCard(),
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
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
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
                  'Multas e débitos',
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

class _AlertCard extends StatelessWidget {
  const _AlertCard();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF3F1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE03B3B)),
      ),
      child: Row(
        children: [
          Container(
            decoration: BoxDecoration(
              color: const Color(0xFFFFE6E1),
              borderRadius: BorderRadius.circular(16),
            ),
            padding: const EdgeInsets.all(10),
            child: const Icon(
              Icons.error_outline,
              color: Color(0xFFD92D20),
              size: 24,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Total em aberto: R\$ 0,00',
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: const Color(0xFFB42318),
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
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

class _DebitsCard extends StatelessWidget {
  const _DebitsCard();

  static final _items = [
    _DebitRowData(
      label: 'IPVA / Licenciamento',
      amount: 'R\$ 0,00',
      status: 'Nenhum',
    ),
    _DebitRowData(label: 'DPVAT', amount: 'R\$ 0,00', status: 'Nenhum'),
    _DebitRowData(label: 'Multas', amount: 'R\$ 0,00', status: 'Nenhum'),
  ];

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFF1D4ED8), width: 2),
      ),
      child: Column(
        children: [
          for (var i = 0; i < _items.length; i++) ...[
            if (i > 0)
              const Divider(
                height: 1,
                thickness: 1,
                indent: 20,
                endIndent: 20,
                color: Color(0xFFE4E7EC),
              ),
            _DebitRow(data: _items[i]),
          ],
        ],
      ),
    );
  }
}

class _DebitRow extends StatelessWidget {
  const _DebitRow({required this.data});

  final _DebitRowData data;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  data.label,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                    color: const Color(0xFF1D2939),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  data.amount,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: const Color(0xFF039855),
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Text(
            data.status,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: const Color(0xFF039855),
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _DebitRowData {
  const _DebitRowData({
    required this.label,
    required this.amount,
    required this.status,
  });

  final String label;
  final String amount;
  final String status;
}
