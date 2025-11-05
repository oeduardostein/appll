import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

String? _nonEmptyString(dynamic value) {
  if (value == null) return null;
  final text = value.toString().trim();
  return text.isEmpty ? null : text;
}

class BinResultPage extends StatelessWidget {
  const BinResultPage({
    super.key,
    required this.placa,
    required this.renavam,
    required this.payload,
  });

  final String placa;
  final String renavam;
  final Map<String, dynamic> payload;

  @override
  Widget build(BuildContext context) {
    final normalized = _asMap(payload['normalized']);
    final identificacao =
        _asMap(normalized?['identificacao_do_veiculo_na_bin']);
    final gravames = _asMap(normalized?['gravames']);
    final sections = _asSectionList(payload['sections']);
    final fonte = _asMap(payload['fonte']);

    final displayPlaca = _nonEmptyString(identificacao?['placa']) ?? placa;
    final displayRenavam =
        _nonEmptyString(identificacao?['renavam']) ?? renavam;
    final displayChassi = _nonEmptyString(identificacao?['chassi']);
    final displayProprietario =
        _nonEmptyString(gravames?['nome_financiado']);
    final formattedJson = const JsonEncoder.withIndent('  ').convert(payload);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Pesquisa BIN'),
        actions: [
          IconButton(
            tooltip: 'Copiar resultado',
            onPressed: () async {
              await Clipboard.setData(ClipboardData(text: formattedJson));
              if (context.mounted) {
                ScaffoldMessenger.of(context)
                  ..clearSnackBars()
                  ..showSnackBar(
                    const SnackBar(
                      content:
                          Text('Dados copiados para a área de transferência.'),
                    ),
                  );
              }
            },
            icon: const Icon(Icons.copy_outlined),
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _VehicleSummaryCard(
                placa: displayPlaca,
                renavam: displayRenavam,
                chassi: displayChassi,
                proprietario: displayProprietario,
              ),
              const SizedBox(height: 16),
              _SectionCard(
                title: 'Fonte',
                child: _SectionItemsList(
                  items: _mapToItems(fonte),
                ),
              ),
              for (final section in sections) ...[
                const SizedBox(height: 16),
                _SectionCard(
                  title: section.title,
                  child: _SectionItemsList(items: section.items),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  static Map<String, dynamic>? _asMap(dynamic value) {
    if (value is Map<String, dynamic>) {
      return value;
    }
    if (value is Map) {
      return value.map(
        (key, dynamic val) => MapEntry(key.toString(), val),
      );
    }
    return null;
  }
}

List<_BinSection> _asSectionList(dynamic value) {
  if (value is! List) return const [];
  return value
      .whereType<Map>()
      .map(
        (raw) => _BinSection(
          title: raw['title']?.toString() ?? '',
          slug: raw['slug']?.toString() ?? '',
          items: _itemsFromRaw(raw['items']),
        ),
      )
      .where((section) => section.title.isNotEmpty && section.items.isNotEmpty)
      .toList();
}

List<_BinItem> _itemsFromRaw(dynamic rawItems) {
  if (rawItems is! List) return const [];
  return rawItems.whereType<Map>().map((raw) {
    final label = raw['label']?.toString() ?? '';
    final value = raw['value'];
    if (label.isEmpty) return null;
    return _BinItem(
      label: label,
      value: value,
      slug: raw['slug']?.toString() ?? '',
    );
  }).whereType<_BinItem>().where((item) => item.displayValue.isNotEmpty).toList();
}

List<_BinItem> _mapToItems(Map<String, dynamic>? map) {
  if (map == null || map.isEmpty) return const [];
  return map.entries
      .map(
        (entry) => _BinItem(
          label: _formatLabel(entry.key),
          value: entry.value,
          slug: entry.key,
        ),
      )
      .where((item) => item.displayValue.isNotEmpty)
      .toList();
}

String _formatLabel(String value) {
  final normalized = value.replaceAll('_', ' ').trim();
  if (normalized.isEmpty) return value;
  return normalized[0].toUpperCase() + normalized.substring(1);
}

class _BinSection {
  const _BinSection({
    required this.title,
    required this.slug,
    required this.items,
  });

  final String title;
  final String slug;
  final List<_BinItem> items;
}

class _BinItem {
  const _BinItem({
    required this.label,
    required this.value,
    required this.slug,
  });

  final String label;
  final dynamic value;
  final String slug;

  String get displayValue {
    if (value == null) return '';
    if (value is String) {
      final trimmed = value.trim();
      return trimmed.isEmpty ? '' : trimmed;
    }
    if (value is num || value is bool) {
      return value.toString();
    }
    try {
      return jsonEncode(value);
    } catch (_) {
      return value.toString();
    }
  }
}

class _VehicleSummaryCard extends StatelessWidget {
  const _VehicleSummaryCard({
    required this.placa,
    required this.renavam,
    this.chassi,
    this.proprietario,
  });

  final String placa;
  final String renavam;
  final String? chassi;
  final String? proprietario;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    Widget _buildTile(String label, String value) {
      return Expanded(
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: colorScheme.outlineVariant.withOpacity(0.3)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: colorScheme.primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                value,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Container(
      decoration: BoxDecoration(
        color: colorScheme.primary,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: colorScheme.primary.withOpacity(0.22),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Consulta realizada',
            style: theme.textTheme.titleMedium?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              _buildTile('Placa', placa),
              const SizedBox(width: 12),
              _buildTile('Renavam', renavam),
            ],
          ),
          if (chassi != null && chassi!.isNotEmpty) ...[
            const SizedBox(height: 12),
            Row(
              children: [
                _buildTile('Chassi', chassi!),
                if (proprietario != null && proprietario!.isNotEmpty) ...[
                  const SizedBox(width: 12),
                  _buildTile('Proprietário', proprietario!),
                ],
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({
    required this.title,
    required this.child,
  });

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withOpacity(0.3),
        ),
      ),
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            title,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class _SectionItemsList extends StatelessWidget {
  const _SectionItemsList({required this.items});

  final List<_BinItem> items;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return Text(
        'Sem informações disponíveis.',
        style: Theme.of(context)
            .textTheme
            .bodyMedium
            ?.copyWith(color: Colors.grey.shade600),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: items
          .map(
            (item) => Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.label,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Theme.of(context).colorScheme.primary,
                          fontWeight: FontWeight.w600,
                        ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    item.displayValue,
                    style: Theme.of(context).textTheme.bodyLarge,
                  ),
                ],
              ),
            ),
          )
          .toList(),
    );
  }
}
