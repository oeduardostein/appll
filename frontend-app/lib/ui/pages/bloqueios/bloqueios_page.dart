import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'package:frontend_app/ui/widgets/response_top_bar.dart';
import 'package:frontend_app/utils/pdf_share_helper.dart';

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
        data: structured,
        formattedPayload: _formattedPayload,
        rawPayload: payload,
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
      appBar: ResponseTopBar(
        title: 'Bloqueios ativos',
        subtitle: 'Origem: $origin',
        onShare: hasOnlyMessage ? null : () => _sharePayload(context),
        actions: [
          if (!hasOnlyMessage)
            IconButton(
              tooltip: 'Copiar resultado',
              onPressed: () => _copyToClipboard(context),
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

  void _copyToClipboard(BuildContext context) async {
    await Clipboard.setData(ClipboardData(text: formattedPayload));
    if (!context.mounted) return;
    ScaffoldMessenger.of(context)
      ..clearSnackBars()
      ..showSnackBar(
        const SnackBar(content: Text('Dados copiados para a área de transferência.')),
      );
  }

  Future<void> _sharePayload(BuildContext context) async {
    try {
      await PdfShareHelper.share(
        title: 'Bloqueios ativos',
        filenamePrefix: 'pesquisa_bloqueios',
        data: payload,
        subtitle: 'Origem: $origin',
      );
      if (!context.mounted) return;
      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          const SnackBar(
            content: Text('PDF gerado. Selecione o app para compartilhar.'),
          ),
        );
    } catch (error) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          SnackBar(
            content: Text('Não foi possível gerar o PDF (${error.toString()}).'),
          ),
        );
    }
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
    required this.data,
    required this.formattedPayload,
    required this.rawPayload,
  });

  final String origin;
  final _BloqueiosStructuredPayload data;
  final String formattedPayload;
  final Map<String, dynamic> rawPayload;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: ResponseTopBar(
        title: 'Bloqueios ativos',
        subtitle: 'Origem: $origin',
        onShare: () => _sharePayload(context),
        actions: [
          IconButton(
            tooltip: 'Copiar resultado',
            onPressed: () => _copyJson(context),
            icon: const Icon(Icons.copy_outlined),
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
          child: Column(
            children: [
              _BloqueiosSummaryCard(
                origin: origin,
                data: data,
              ),
              const SizedBox(height: 24),
              _BloqueiosSectionCard(
                title: 'Consulta',
                children: [
                  _BloqueiosInfoRow(
                    label: 'Placa',
                    value: data.consulta['placa'],
                  ),
                  _BloqueiosInfoRow(
                    label: 'Município da placa',
                    value: data.consulta['municipio_placa'],
                  ),
                  _BloqueiosInfoRow(
                    label: 'Chassi',
                    value: data.consulta['chassi'],
                  ),
                  if (data.ocorrenciasEncontradas != null ||
                      data.ocorrenciasExibidas != null) ...[
                    const SizedBox(height: 12),
                    const _BloqueiosSectionSubheading('Ocorrências'),
                    if (data.ocorrenciasEncontradas != null)
                      _BloqueiosInfoRow(
                        label: 'Encontradas',
                        value: data.ocorrenciasEncontradas,
                      ),
                    if (data.ocorrenciasExibidas != null)
                      _BloqueiosInfoRow(
                        label: 'Exibidas',
                        value: data.ocorrenciasExibidas,
                      ),
                  ],
                ],
              ),
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
              _BloqueiosSectionCard(
                title: 'Bloqueios RENAJUD',
                children: [
                  if (data.renajud.isEmpty)
                    Text(
                      'Nenhum bloqueio RENAJUD encontrado.',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: Theme.of(context).colorScheme.outline,
                          ),
                    )
                  else
                    ...data.renajud
                        .map((entry) => _RenajudEntryTile(entry: entry))
                        .toList(),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _sharePayload(BuildContext context) async {
    try {
      await PdfShareHelper.share(
        title: 'Bloqueios ativos',
        filenamePrefix: 'pesquisa_bloqueios',
        data: rawPayload,
        subtitle: 'Origem: $origin',
      );
      if (!context.mounted) return;
      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          const SnackBar(
            content: Text('PDF gerado. Selecione o app para compartilhar.'),
          ),
        );
    } catch (error) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          SnackBar(
            content: Text('Não foi possível gerar o PDF (${error.toString()}).'),
          ),
        );
    }
  }

  void _copyJson(BuildContext context) async {
    await Clipboard.setData(ClipboardData(text: formattedPayload));
    if (!context.mounted) return;
    ScaffoldMessenger.of(context)
      ..clearSnackBars()
      ..showSnackBar(
        const SnackBar(
          content: Text('Dados copiados para a área de transferência.'),
        ),
      );
  }
}

class _BloqueiosSummaryCard extends StatelessWidget {
  const _BloqueiosSummaryCard({
    required this.origin,
    required this.data,
  });

  final String origin;
  final _BloqueiosStructuredPayload data;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final placa = data.placa ?? '—';
    final municipio = data.municipioPlaca ?? '—';
    final chassi = data.chassi ?? '—';
    final fonteTitulo = data.tituloFonte ?? 'Fonte não informada';
    final geradoEm = data.geradoEm ?? '—';

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
                      fonteTitulo,
                      style: theme.textTheme.titleMedium?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Gerado em $geradoEm',
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
                  label: 'Placa',
                  value: placa,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _SummaryInfoRow(
                  label: 'Município da placa',
                  value: municipio,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _SummaryInfoRow(
                  label: 'Chassi consultado',
                  value: chassi,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _SummaryInfoRow(
                  label: 'Ocorrências encontradas',
                  value: data.ocorrenciasEncontradas ?? '—',
                ),
              ),
            ],
          ),
          if (data.ocorrenciasExibidas != null) ...[
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _SummaryInfoRow(
                    label: 'Ocorrências exibidas',
                    value: data.ocorrenciasExibidas!,
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
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


class _RenajudEntryTile extends StatelessWidget {
  const _RenajudEntryTile({required this.entry});

  final Map<String, dynamic> entry;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final dataInclusao = _formatDisplayValue(entry['data_inclusao']);
    final horaInclusao = _formatDisplayValue(entry['hora_inclusao']);
    final tipoRestricao = _formatDisplayValue(entry['tipo_restricao_judicial']);
    final numeroProcesso = _formatDisplayValue(entry['numero_processo']);
    final codigoTribunal = _formatDisplayValue(entry['codigo_tribunal']);
    final codigoOrgao = _formatDisplayValue(entry['codigo_orgao_judicial']);
    final nomeOrgao = _formatDisplayValue(entry['nome_orgao_judicial']);

    final hasData = dataInclusao != '—';
    final hasHora = horaInclusao != '—';
    final dataHora = hasData
        ? hasHora
            ? '$dataInclusao às $horaInclusao'
            : dataInclusao
        : null;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceVariant.withOpacity(0.2),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withOpacity(0.2),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary.withOpacity(0.14),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  codigoTribunal == '—' ? 'RENAJUD' : 'RENAJUD • $codigoTribunal',
                  style: theme.textTheme.labelSmall?.copyWith(
                    color: theme.colorScheme.primary,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            tipoRestricao,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          if (dataHora != null) ...[
            const SizedBox(height: 4),
            Text(
              dataHora,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.outline,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
          const SizedBox(height: 12),
          if (nomeOrgao != '—')
            Text(
              nomeOrgao,
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          if (codigoOrgao != '—')
            Text(
              'Órgão judicial: $codigoOrgao',
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.outline,
              ),
            ),
          if (numeroProcesso != '—')
            Text(
              'Número do processo: $numeroProcesso',
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.outline,
              ),
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
    required this.consulta,
    this.quantidade,
    required this.renajud,
  });

  final Map<String, dynamic> fonte;
  final Map<String, dynamic> consulta;
  final Map<String, dynamic>? quantidade;
  final List<Map<String, dynamic>> renajud;

  String? get placa => _formatDisplayValue(consulta['placa']) == '—'
      ? null
      : _formatDisplayValue(consulta['placa']);
  String? get municipioPlaca =>
      _formatDisplayValue(consulta['municipio_placa']) == '—'
          ? null
          : _formatDisplayValue(consulta['municipio_placa']);
  String? get chassi =>
      _formatDisplayValue(consulta['chassi']) == '—'
          ? null
          : _formatDisplayValue(consulta['chassi']);
  String? get geradoEm =>
      _formatDisplayValue(fonte['gerado_em']) == '—'
          ? null
          : _formatDisplayValue(fonte['gerado_em']);
  String? get tituloFonte =>
      _formatDisplayValue(fonte['titulo']) == '—'
          ? null
          : _formatDisplayValue(fonte['titulo']);
  String? get ocorrenciasEncontradas =>
      _formatDisplayValue(quantidade?['ocorrencias_encontradas']) == '—'
          ? null
          : _formatDisplayValue(quantidade?['ocorrencias_encontradas']);
  String? get ocorrenciasExibidas =>
      _formatDisplayValue(quantidade?['ocorrencias_exibidas']) == '—'
          ? null
          : _formatDisplayValue(quantidade?['ocorrencias_exibidas']);

  static _BloqueiosStructuredPayload? tryParse(
    Map<String, dynamic> payload,
  ) {
    final fonte = _asMap(payload['fonte']);
    final consulta = _asMap(payload['consulta']);

    if (fonte == null || consulta == null) {
      return null;
    }

    return _BloqueiosStructuredPayload(
      fonte: fonte,
      consulta: consulta,
      quantidade: _asMap(consulta['quantidade']),
      renajud: _asListOfMap(payload['renajud']),
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

  static List<Map<String, dynamic>> _asListOfMap(dynamic value) {
    if (value is List) {
      return value
          .where((element) => element is Map)
          .map((element) => (element as Map).map(
                (key, dynamic val) => MapEntry(key.toString(), val),
              ))
          .toList(growable: false);
    }
    return const [];
  }
}
