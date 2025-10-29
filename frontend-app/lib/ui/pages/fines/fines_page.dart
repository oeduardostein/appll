import 'package:flutter/material.dart';

class FinesPage extends StatelessWidget {
  const FinesPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: [
              const _HeaderSection(),
              const SizedBox(height: 20),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Column(
                  children: [
                    const _FilterTabs(),
                    const SizedBox(height: 20),
                    const _OutstandingAlert(),
                    const SizedBox(height: 16),
                    const _MultasBreakdownCard(),
                    const SizedBox(height: 16),
                    const _DebitsSummaryCard(),
                    const SizedBox(height: 16),
                    const _LastUpdateInfo(),
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

class _FilterTabs extends StatelessWidget {
  const _FilterTabs();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    Widget buildTab({required String label, required bool isActive}) {
      final backgroundColor = isActive ? const Color(0xFF123D99) : Colors.white;
      final textColor = isActive ? Colors.white : const Color(0xFF1D2939);

      return Expanded(
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: backgroundColor,
            borderRadius: BorderRadius.circular(18),
            border: isActive
                ? null
                : Border.all(color: const Color(0xFFE4E7EC)),
            boxShadow: isActive
                ? const [
                    BoxShadow(
                      color: Color(0x1A101828),
                      blurRadius: 12,
                      offset: Offset(0, 6),
                    ),
                  ]
                : null,
          ),
          alignment: Alignment.center,
          child: Text(
            label,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: textColor,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      );
    }

    return Row(
      children: [
        buildTab(label: 'Multas', isActive: true),
        const SizedBox(width: 12),
        buildTab(label: 'Débitos', isActive: false),
      ],
    );
  }
}

class _OutstandingAlert extends StatelessWidget {
  const _OutstandingAlert();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF3F1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFF97066)),
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
                  'Total em aberto',
                  style: theme.textTheme.labelMedium?.copyWith(
                    color: const Color(0xFFB42318),
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'R\$ 390,00',
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: const Color(0xFFB42318),
                    fontSize: 20,
                  ),
                ),
              ],
            ),
          ),
          Container(
            decoration: BoxDecoration(
              color: const Color(0xFFFEF0C7),
              borderRadius: BorderRadius.circular(16),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            child: Text(
              '3 em aberto',
              style: theme.textTheme.bodySmall?.copyWith(
                color: const Color(0xFFB54708),
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MultasBreakdownCard extends StatelessWidget {
  const _MultasBreakdownCard();

  static final _items = [
    _FineRowData(label: 'DERSA', amount: 'R\$ 0,00', status: 'Nenhum'),
    _FineRowData(label: 'DER', amount: 'R\$ 130,16', status: 'Em aberto'),
    _FineRowData(label: 'DETRAN', amount: 'R\$ 0,00', status: 'Nenhum'),
    _FineRowData(label: 'CETESB', amount: 'R\$ 0,00', status: 'Nenhum'),
    _FineRowData(label: 'RENAINF', amount: 'R\$ 130,16', status: 'Em aberto'),
    _FineRowData(
      label: 'Municipais',
      amount: 'R\$ 130,16',
      status: 'Em aberto',
      highlight: true,
    ),
    _FineRowData(
      label: 'Polícia Rodoviária Federal',
      amount: 'R\$ 0,00',
      status: 'Nenhum',
    ),
    _FineRowData(label: 'IPVA', amount: 'R\$ 0,00', status: 'Nenhum'),
  ];

  Color _amountColor(String amount) {
    return amount.contains('0,00')
        ? const Color(0xFF039855)
        : const Color(0xFFD92D20);
  }

  Color _statusColor(String status) {
    return status == 'Nenhum'
        ? const Color(0xFF039855)
        : const Color(0xFFD92D20);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

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
        children: [
          Row(
            children: [
              Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFF2F6FF),
                  borderRadius: BorderRadius.circular(16),
                ),
                padding: const EdgeInsets.all(12),
                child: const Icon(
                  Icons.receipt_long_outlined,
                  color: Color(0xFF123D99),
                  size: 26,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Resumo por órgão autuador',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Veja o valor consolidado em cada entidade.',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: const Color(0xFF667085),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          const Divider(color: Color(0xFFE4E7EC)),
          for (var i = 0; i < _items.length; i++) ...[
            if (i > 0) const Divider(color: Color(0xFFE4E7EC)),
            _FineRow(
              data: _items[i],
              amountColor: _amountColor(_items[i].amount),
              statusColor: _statusColor(_items[i].status),
            ),
          ],
        ],
      ),
    );
  }
}

class _FineRow extends StatelessWidget {
  const _FineRow({
    required this.data,
    required this.amountColor,
    required this.statusColor,
  });

  final _FineRowData data;
  final Color amountColor;
  final Color statusColor;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    final rowContent = Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                data.label,
                style: theme.textTheme.bodyLarge?.copyWith(
                  fontWeight: FontWeight.w600,
                  color: const Color(0xFF1D2939),
                ),
              ),
              const SizedBox(height: 6),
              Text(
                data.amount,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: amountColor,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(width: 12),
        Container(
          decoration: BoxDecoration(
            color: const Color(0xFFF9FAFB),
            borderRadius: BorderRadius.circular(14),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          child: Text(
            data.status,
            style: theme.textTheme.bodySmall?.copyWith(
              color: statusColor,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ],
    );

    if (!data.highlight) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: rowContent,
      );
    }

    return Container(
      margin: const EdgeInsets.symmetric(vertical: 8),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1D4ED8), width: 1.4),
        color: const Color(0xFFF8FAFF),
      ),
      child: rowContent,
    );
  }
}

class _FineRowData {
  const _FineRowData({
    required this.label,
    required this.amount,
    required this.status,
    this.highlight = false,
  });

  final String label;
  final String amount;
  final String status;
  final bool highlight;
}

class _DebitsSummaryCard extends StatelessWidget {
  const _DebitsSummaryCard();

  static final _items = [
    _DebitRowData(
      label: 'IPVA 2024',
      status: 'Em aberto',
      amount: 'R\$ 0,00',
      isWarning: false,
    ),
    _DebitRowData(
      label: 'Licenciamento 2024',
      status: 'Pago',
      amount: 'R\$ 128,95',
      isWarning: false,
    ),
    _DebitRowData(
      label: 'DPVAT / Seguro obrigatório',
      status: 'Isento',
      amount: 'R\$ 0,00',
      isWarning: false,
    ),
    _DebitRowData(
      label: 'Parcelamentos',
      status: '3 parcelas em aberto',
      amount: 'R\$ 390,00',
      isWarning: true,
    ),
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

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
        children: [
          Row(
            children: [
              Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFF2F6FF),
                  borderRadius: BorderRadius.circular(16),
                ),
                padding: const EdgeInsets.all(12),
                child: const Icon(
                  Icons.payments_outlined,
                  color: Color(0xFF123D99),
                  size: 26,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Débitos estaduais',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Situação dos débitos vinculados ao veículo.',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: const Color(0xFF667085),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          const Divider(color: Color(0xFFE4E7EC)),
          Column(
            children: [
              for (var i = 0; i < _items.length; i++) ...[
                if (i > 0) const Divider(color: Color(0xFFE4E7EC)),
                _DebitRow(data: _items[i]),
              ],
            ],
          ),
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

    final accentColor = data.isWarning
        ? const Color(0xFFD92D20)
        : const Color(0xFF039855);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  data.label,
                  style: theme.textTheme.bodyLarge?.copyWith(
                    fontWeight: FontWeight.w600,
                    color: const Color(0xFF1D2939),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  data.status,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: accentColor,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Text(
            data.amount,
            style: theme.textTheme.bodyMedium?.copyWith(
              fontWeight: FontWeight.w600,
              color: const Color(0xFF1D2939),
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
    required this.status,
    required this.amount,
    required this.isWarning,
  });

  final String label;
  final String status;
  final String amount;
  final bool isWarning;
}

class _LastUpdateInfo extends StatelessWidget {
  const _LastUpdateInfo();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE4E7EC)),
      ),
      child: Row(
        children: [
          const Icon(Icons.info_outline, color: Color(0xFF123D99), size: 20),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Última atualização em 19/04/2024 às 08:12',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: const Color(0xFF1D2939),
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'As informações são fornecidas diretamente pelos órgãos estaduais.',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: const Color(0xFF667085),
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
