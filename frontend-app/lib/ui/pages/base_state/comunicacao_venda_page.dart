import 'package:flutter/material.dart';

class ComunicacaoVendaPage extends StatelessWidget {
  const ComunicacaoVendaPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      body: SafeArea(
        child: Column(
          children: [
            const _HeaderSection(),
            const SizedBox(height: 20),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: const _SaleInfoCard(),
              ),
            ),
            const SizedBox(height: 24),
          ],
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
                backgroundImage: const AssetImage('assets/images/logoLL.png'),
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
                  'Comunicação de Venda',
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

class _SaleInfoCard extends StatelessWidget {
  const _SaleInfoCard();

  static final _items = [
    _SaleInfo(label: 'Data da venda', value: '00/00/0000'),
    _SaleInfo(label: 'Data da inclusão', value: '00/00/0000'),
    _SaleInfo(label: 'Origem', value: 'DETRAN'),
    _SaleInfo(label: 'Protocolo no DETRAN', value: '000000000'),
    _SaleInfo(label: 'CPF / CNPJ do comprador', value: '000.000.000-00'),
    _SaleInfo(label: 'Nº da Nota Fiscal da venda', value: '0000'),
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFF1D4ED8), width: 2),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      child: Column(
        children: [
          for (var i = 0; i < _items.length; i++) ...[
            if (i > 0) const Divider(color: Color(0xFFE4E7EC), height: 20),
            Align(
              alignment: Alignment.centerLeft,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _items[i].label,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: const Color(0xFF475467),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    _items[i].value,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: const Color(0xFF1D2939),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _SaleInfo {
  const _SaleInfo({required this.label, required this.value});

  final String label;
  final String value;
}
