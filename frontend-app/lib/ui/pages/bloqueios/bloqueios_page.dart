import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class BloqueiosPage extends StatelessWidget {
  const BloqueiosPage({
    super.key,
    required this.origin,
    this.plate,
    this.chassi,
    required this.payload,
  });

  final String origin;
  final String? plate;
  final String? chassi;
  final Map<String, dynamic> payload;

  String get _formattedPayload {
    try {
      return const JsonEncoder.withIndent('  ').convert(payload);
    } catch (_) {
      return payload.toString();
    }
  }

  @override
  Widget build(BuildContext context) {
    final structured = _BloqueiosStructuredPayload.tryParse(payload);

    if (structured != null) {
      return _BloqueiosStructuredScreen(
        origin: origin,
        plate: plate,
        chassi: chassi,
        data: structured,
        formattedPayload: _formattedPayload,
      );
    }

    return _BloqueiosFallbackScreen(
      origin: origin,
      plate: plate,
      chassi: chassi,
      payload: payload,
      formattedPayload: _formattedPayload,
    );
  }
}

class _BloqueiosFallbackScreen extends StatelessWidget {
  const _BloqueiosFallbackScreen({
    required this.origin,
    required this.plate,
    required this.chassi,
    required this.payload,
    required this.formattedPayload,
  });

  final String origin;
  final String? plate;
  final String? chassi;
  final Map<String, dynamic> payload;
  final String formattedPayload;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    final message = payload['message'];
    final hasOnlyMessage =
        payload.length == 1 && message is String && message.isNotEmpty;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Bloqueios ativos'),
        actions: [
          if (!hasOnlyMessage)
            IconButton(
              tooltip: 'Copiar resultado',
              onPressed: () async {
                await Clipboard.setData(ClipboardData(text: formattedPayload));
                if (context.mounted) {
                  ScaffoldMessenger.of(context)
                    ..clearSnackBars()
                    ..showSnackBar(
                      const SnackBar(
                        content: Text('Dados copiados para a área de transferência.'),
                      ),
                    );
                }
              },
              icon: const Icon(Icons.copy_outlined),
            ),
        ],
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _QuerySummaryCard(
                colorScheme: colorScheme,
                origin: origin,
                plate: plate,
                chassi: chassi,
              ),
              const SizedBox(height: 16),
              Expanded(
                child: _BloqueiosResultView(
                  payload: payload,
                  formattedPayload: formattedPayload,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _QuerySummaryCard extends StatelessWidget {
  const _QuerySummaryCard({
    required this.colorScheme,
    required this.origin,
    required this.plate,
    required this.chassi,
  });

  final ColorScheme colorScheme;
  final String origin;
  final String? plate;
  final String? chassi;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
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
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 18),
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
          const SizedBox(height: 12),
          Row(
            children: [
              _SummaryPill(label: 'Origem', value: origin),
              if (plate != null) ...[
                const SizedBox(width: 12),
                _SummaryPill(label: 'Placa', value: plate!),
              ],
              if (chassi != null) ...[
                const SizedBox(width: 12),
                _SummaryPill(label: 'Chassi', value: chassi!),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _SummaryPill extends StatelessWidget {
  const _SummaryPill({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.12),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: Colors.white70,
                    fontWeight: FontWeight.w500,
                  ),
            ),
            const SizedBox(height: 4),
            Text(
              value,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                  ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BloqueiosResultView extends StatelessWidget {
  const _BloqueiosResultView({
    required this.payload,
    required this.formattedPayload,
  });

  final Map<String, dynamic> payload;
  final String formattedPayload;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final message = payload['message'];
    final hasOnlyMessage =
        payload.length == 1 && message is String && message.isNotEmpty;

    if (hasOnlyMessage) {
      return Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: theme.colorScheme.surface,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: theme.colorScheme.outlineVariant.withOpacity(0.4),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(
              Icons.info_outline,
              color: theme.colorScheme.primary,
            ),
            const SizedBox(height: 12),
            Text(
              'Mensagem da consulta',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              style: theme.textTheme.bodyLarge,
            ),
          ],
        ),
      );
    }

    return Container(
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withOpacity(0.4),
        ),
      ),
      clipBehavior: Clip.antiAlias,
      child: Scrollbar(
        thumbVisibility: true,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: SelectableText(
            formattedPayload,
            style: const TextStyle(
              fontFamily: 'monospace',
              fontSize: 13,
              height: 1.4,
            ),
          ),
        ),
      ),
    );
  }
}

class _BloqueiosStructuredScreen extends StatelessWidget {
  const _BloqueiosStructuredScreen({
    required this.origin,
    required this.plate,
    required this.chassi,
    required this.data,
    required this.formattedPayload,
  });

  final String origin;
  final String? plate;
  final String? chassi;
  final _BloqueiosStructuredPayload data;
  final String formattedPayload;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      backgroundColor: theme.colorScheme.surfaceVariant.withOpacity(0.08),
      body: SafeArea(
        child: Stack(
          children: [
            Container(
              height: 220,
              decoration: BoxDecoration(
                color: theme.colorScheme.primary,
                borderRadius: const BorderRadius.only(
                  bottomLeft: Radius.circular(32),
                  bottomRight: Radius.circular(32),
                ),
              ),
            ),
            Column(
              children: [
                _BloqueiosHeader(
                  onBack: () => Navigator.of(context).pop(),
                  onShare: () => _copyBloqueiosJsonToClipboard(
                    context,
                    formatted: formattedPayload,
                  ),
                ),
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
                    child: Column(
                      children: [
                        const SizedBox(height: 12),
                        _BloqueiosSummaryCard(
                          origin: origin,
                          plate: plate,
                          chassi: chassi,
                          data: data,
                        ),
                        const SizedBox(height: 16),
                        _BloqueiosStatusCard(bloqueios: data.bloqueios),
                        if (data.inspecaoAmbiental != null &&
                            data.inspecaoAmbiental!.trim().isNotEmpty) ...[
                          const SizedBox(height: 16),
                          _BloqueiosInfoBanner(
                            title: 'Inspeção ambiental',
                            description: data.inspecaoAmbiental!,
                          ),
                        ],
                        const SizedBox(height: 24),
                        _BloqueiosSectionCard(
                          title: 'Veículo',
                          children: _buildInfoRows(
                            data.veiculo,
                            const {
                              'placa': 'Placa',
                              'renavam': 'Renavam',
                              'chassi': 'Chassi',
                              'tipo': 'Tipo',
                              'procedencia': 'Procedência',
                              'combustivel': 'Combustível',
                              'cor': 'Cor',
                              'marca': 'Marca',
                              'categoria': 'Categoria',
                              'ano_fabricacao': 'Ano fabricação',
                              'ano_modelo': 'Ano modelo',
                              'municipio': 'Município',
                            },
                          ),
                        ),
                        if (data.proprietario != null) ...[
                          const SizedBox(height: 16),
                          _BloqueiosSectionCard(
                            title: 'Proprietário',
                            children: _buildInfoRows(
                              data.proprietario!,
                              const {
                                'nome': 'Nome',
                              },
                            ),
                          ),
                        ],
                        if (data.crvCrlvAtualizacao != null) ...[
                          const SizedBox(height: 16),
                          _BloqueiosSectionCard(
                            title: 'CRV / CRLV',
                            children: _buildInfoRows(
                              data.crvCrlvAtualizacao!,
                              const {
                                'exercicio_licenciamento': 'Exercício licenciamento',
                                'data_licenciamento': 'Data licenciamento',
                              },
                            ),
                          ),
                        ],
                        if (data.comunicacaoVendas != null) ...[
                          const SizedBox(height: 16),
                          _BloqueiosSectionCard(
                            title: 'Comunicação de vendas',
                            children: [
                              ..._buildInfoRows(
                                data.comunicacaoVendas!,
                                const {
                                  'status': 'Status',
                                  'inclusao': 'Inclusão',
                                  'tipo_doc_comprador': 'Tipo documento comprador',
                                  'cnpj_cpf_comprador': 'CNPJ/CPF comprador',
                                  'origem': 'Origem',
                                },
                              ),
                              if (data.comunicacaoVendasDatas != null) ...[
                                const SizedBox(height: 12),
                                const _BloqueiosSectionSubheading('Datas'),
                                ..._buildInfoRows(
                                  data.comunicacaoVendasDatas!,
                                  const {
                                    'venda': 'Venda',
                                    'nota_fiscal': 'Nota fiscal',
                                    'protocolo_detran': 'Protocolo DETRAN',
                                  },
                                ),
                              ],
                            ],
                          ),
                        ],
                        const SizedBox(height: 16),
                        _BloqueiosSectionCard(
                          title: 'Fonte',
                          children: _buildInfoRows(
                            data.fonte,
                            const {
                              'titulo': 'Título',
                              'gerado_em': 'Gerado em',
                            },
                          ),
                        ),
                        const SizedBox(height: 16),
                        Align(
                          alignment: Alignment.centerRight,
                          child: TextButton.icon(
                            onPressed: () => _showRawResultDialog(
                              context,
                              formattedPayload,
                            ),
                            icon: const Icon(Icons.code),
                            label: const Text('Ver resposta completa'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _showRawResultDialog(BuildContext context, String payload) {
    showDialog<void>(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: const Text('Resposta completa'),
          content: SizedBox(
            width: double.maxFinite,
            child: Scrollbar(
              thumbVisibility: true,
              child: SingleChildScrollView(
                child: SelectableText(
                  payload,
                  style: const TextStyle(
                    fontFamily: 'monospace',
                    fontSize: 13,
                    height: 1.4,
                  ),
                ),
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(),
              child: const Text('Fechar'),
            ),
          ],
        );
      },
    );
  }
}

class _BloqueiosHeader extends StatelessWidget {
  const _BloqueiosHeader({
    this.onBack,
    this.onShare,
  });

  final VoidCallback? onBack;
  final VoidCallback? onShare;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
      child: Row(
        children: [
          _HeaderCircleButton(
            icon: Icons.arrow_back,
            onTap: onBack ?? () => Navigator.of(context).pop(),
          ),
          Expanded(
            child: Center(
              child: Image.asset(
                'assets/images/logoLL.png',
                height: 40,
              ),
            ),
          ),
          _HeaderCircleButton(
            icon: Icons.share_outlined,
            onTap: onShare,
            enabled: onShare != null,
          ),
        ],
      ),
    );
  }
}

class _HeaderCircleButton extends StatelessWidget {
  const _HeaderCircleButton({
    required this.icon,
    this.onTap,
    this.enabled = true,
  });

  final IconData icon;
  final VoidCallback? onTap;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white.withOpacity(enabled ? 0.18 : 0.08),
      shape: const CircleBorder(),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: enabled ? onTap : null,
        child: SizedBox(
          width: 44,
          height: 44,
          child: Icon(
            icon,
            color: Colors.white.withOpacity(enabled ? 1 : 0.5),
          ),
        ),
      ),
    );
  }
}

class _BloqueiosSummaryCard extends StatelessWidget {
  const _BloqueiosSummaryCard({
    required this.origin,
    required this.plate,
    required this.chassi,
    required this.data,
  });

  final String origin;
  final String? plate;
  final String? chassi;
  final _BloqueiosStructuredPayload data;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final veiculo = data.veiculo;
    final placa = _formatDisplayValue(veiculo['placa']);
    final marca = _parseMarca(veiculo['marca']);
    final municipio = _formatDisplayValue(veiculo['municipio']);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.06),
            blurRadius: 24,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary.withOpacity(0.18),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: const Icon(
                  Icons.shield_outlined,
                  color: Colors.white,
                  size: 32,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Bloqueios $origin',
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      placa,
                      style: theme.textTheme.titleMedium?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      marca,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.outline,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _SummaryInfoRow(
                  label: 'Município',
                  value: municipio,
                ),
              ),
              if (chassi != null) ...[
                const SizedBox(width: 12),
                Expanded(
                  child: _SummaryInfoRow(
                    label: 'Chassi consultado',
                    value: chassi!,
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  static String _parseMarca(dynamic value) {
    final text = _formatDisplayValue(value);
    if (text == '—') return 'Marca não informada';
    final parts = text.split(' - ');
    if (parts.length >= 2) {
      final joined = parts.sublist(1).join(' - ').trim();
      return joined.isEmpty ? text : joined;
    }
    return text;
  }
}

class _SummaryInfoRow extends StatelessWidget {
  const _SummaryInfoRow({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: theme.textTheme.labelSmall?.copyWith(
            color: theme.colorScheme.outline,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          value,
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w700,
          ),
        ),
      ],
    );
  }
}

class _BloqueiosStatusCard extends StatelessWidget {
  const _BloqueiosStatusCard({required this.bloqueios});

  final Map<String, dynamic>? bloqueios;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final entries = _buildEntries(bloqueios);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.06),
            blurRadius: 24,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Bloqueios ativos',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 16),
          if (entries.isEmpty)
            Text(
              'Nenhum bloqueio informado.',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.outline,
              ),
            )
          else
            Column(
              children: entries
                  .map(
                    (entry) => Padding(
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      child: Row(
                        children: [
                          Expanded(
                            child: Text(
                              entry.label,
                              style: theme.textTheme.titleSmall?.copyWith(
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                          _StatusBadge(
                            value: entry.value,
                            isWarning: entry.isWarning,
                          ),
                        ],
                      ),
                    ),
                  )
                  .toList(),
            ),
        ],
      ),
    );
  }

  static List<_BloqueioEntry> _buildEntries(Map<String, dynamic>? raw) {
    if (raw == null) return [];
    final mapping = <String, String>{
      'renajud': 'RENAJUD',
      'judicial': 'Judicial',
      'administrativas': 'Administrativas',
      'tributaria': 'Tributária',
      'furto': 'Furto',
      'guincho': 'Guincho',
    };
    final List<_BloqueioEntry> entries = [];
    for (final entry in mapping.entries) {
      final value = _formatDisplayValue(raw[entry.key]);
      final isWarning = value != 'Nada Consta' && value != '—';
      entries.add(
        _BloqueioEntry(
          label: entry.value,
          value: value,
          isWarning: isWarning,
        ),
      );
    }
    return entries;
  }
}

class _BloqueioEntry {
  const _BloqueioEntry({
    required this.label,
    required this.value,
    required this.isWarning,
  });

  final String label;
  final String value;
  final bool isWarning;
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({
    required this.value,
    required this.isWarning,
  });

  final String value;
  final bool isWarning;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final Color color = isWarning ? theme.colorScheme.error : Colors.green.shade600;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: color.withOpacity(0.14),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Text(
        value,
        style: theme.textTheme.labelSmall?.copyWith(
          color: color,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _BloqueiosInfoBanner extends StatelessWidget {
  const _BloqueiosInfoBanner({
    required this.title,
    required this.description,
  });

  final String title;
  final String description;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: theme.colorScheme.secondary.withOpacity(0.1),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            description,
            style: theme.textTheme.bodyMedium,
          ),
        ],
      ),
    );
  }
}

class _BloqueiosSectionCard extends StatelessWidget {
  const _BloqueiosSectionCard({
    required this.title,
    required this.children,
  });

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final content = <Widget>[];
    for (final child in children) {
      if (content.isNotEmpty) {
        content.add(const SizedBox(height: 12));
      }
      content.add(child);
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 24,
            offset: const Offset(0, 16),
          ),
        ],
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withOpacity(0.12),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
              color: theme.colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 16),
          ...content,
        ],
      ),
    );
  }
}

class _BloqueiosSectionSubheading extends StatelessWidget {
  const _BloqueiosSectionSubheading(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Text(
      text,
      style: theme.textTheme.labelLarge?.copyWith(
        fontWeight: FontWeight.w600,
        color: theme.colorScheme.primary,
      ),
    );
  }
}

class _BloqueiosInfoRow extends StatelessWidget {
  const _BloqueiosInfoRow({
    required this.label,
    required this.value,
  });

  final String label;
  final dynamic value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: theme.textTheme.labelSmall?.copyWith(
            fontWeight: FontWeight.w600,
            color: theme.colorScheme.outline,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          _formatDisplayValue(value),
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w700,
            color: theme.colorScheme.onSurface,
          ),
        ),
      ],
    );
  }
}

Future<void> _copyBloqueiosJsonToClipboard(
  BuildContext context, {
  Map<String, dynamic?>? payload,
  String? formatted,
}) async {
  final text = formatted ??
      const JsonEncoder.withIndent('  ').convert(payload ?? <String, dynamic>{});
  await Clipboard.setData(ClipboardData(text: text));
  if (!context.mounted) return;
  ScaffoldMessenger.of(context)
    ..clearSnackBars()
    ..showSnackBar(
      const SnackBar(
        content: Text('Dados copiados para a área de transferência.'),
      ),
    );
}

List<Widget> _buildInfoRows(
  Map<String, dynamic>? source,
  Map<String, String> labels,
) {
  if (source == null) {
    return [];
  }
  return labels.entries
      .map<Widget>(
        (entry) => _BloqueiosInfoRow(
          label: entry.value,
          value: source[entry.key],
        ),
      )
      .toList();
}

String _formatDisplayValue(dynamic value) {
  if (value == null) {
    return '—';
  }
  if (value is String) {
    final trimmed = value.trim();
    return trimmed.isEmpty ? '—' : trimmed;
  }
  return value.toString();
}

class _BloqueiosStructuredPayload {
  const _BloqueiosStructuredPayload({
    required this.fonte,
    required this.veiculo,
    this.proprietario,
    this.bloqueios,
    this.inspecaoAmbiental,
    this.crvCrlvAtualizacao,
    this.comunicacaoVendas,
    this.comunicacaoVendasDatas,
  });

  final Map<String, dynamic> fonte;
  final Map<String, dynamic> veiculo;
  final Map<String, dynamic>? proprietario;
  final Map<String, dynamic>? bloqueios;
  final String? inspecaoAmbiental;
  final Map<String, dynamic>? crvCrlvAtualizacao;
  final Map<String, dynamic>? comunicacaoVendas;
  final Map<String, dynamic>? comunicacaoVendasDatas;

  static _BloqueiosStructuredPayload? tryParse(
    Map<String, dynamic> payload,
  ) {
    final fonte = _asMap(payload['fonte']);
    final veiculo = _asMap(payload['veiculo']);

    if (fonte == null || veiculo == null) {
      return null;
    }

    final comunicacao = _asMap(payload['comunicacao_vendas']);

    return _BloqueiosStructuredPayload(
      fonte: fonte,
      veiculo: veiculo,
      proprietario: _asMap(payload['proprietario']),
      bloqueios: _asMap(payload['bloqueios']),
      inspecaoAmbiental: payload['inspecao_ambiental'] as String?,
      crvCrlvAtualizacao: _asMap(payload['crv_crlv_atualizacao']),
      comunicacaoVendas: comunicacao,
      comunicacaoVendasDatas: _asMap(comunicacao?['datas']),
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
