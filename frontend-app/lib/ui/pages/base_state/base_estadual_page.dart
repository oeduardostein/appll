import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:share_plus/share_plus.dart';

class BaseEstadualPage extends StatelessWidget {
  const BaseEstadualPage({
    super.key,
    required this.placa,
    required this.renavam,
    required this.payload,
    this.pageTitle = 'Base estadual',
  });

  final String placa;
  final String renavam;
  final Map<String, dynamic> payload;
  final String pageTitle;

  String get _formattedPayload {
    try {
      return const JsonEncoder.withIndent('  ').convert(payload);
    } catch (_) {
      return payload.toString();
    }
  }

  @override
  Widget build(BuildContext context) {
    final structuredPayload =
        _BaseEstadualStructuredPayload.tryParse(payload);

    if (structuredPayload != null) {
      return _BaseEstadualStructuredScreen(
        title: pageTitle,
        data: structuredPayload,
        formattedPayload: _formattedPayload,
      );
    }

    return _BaseEstadualFallbackScreen(
      title: pageTitle,
      placa: placa,
      renavam: renavam,
      payload: payload,
      formattedPayload: _formattedPayload,
    );
  }
}

class _VehicleQueryCard extends StatelessWidget {
  const _VehicleQueryCard({
    required this.colorScheme,
    required this.placa,
    required this.renavam,
  });

  final ColorScheme colorScheme;
  final String placa;
  final String renavam;

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
              Expanded(
                child: _QueryInfoTile(
                  label: 'Placa',
                  value: placa,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _QueryInfoTile(
                  label: 'Renavam',
                  value: renavam,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _QueryInfoTile extends StatelessWidget {
  const _QueryInfoTile({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
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
    );
  }
}

class _BaseEstadualResultView extends StatelessWidget {
  const _BaseEstadualResultView({
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

class _BaseEstadualFallbackScreen extends StatelessWidget {
  const _BaseEstadualFallbackScreen({
    required this.title,
    required this.placa,
    required this.renavam,
    required this.payload,
    required this.formattedPayload,
  });

  final String title;
  final String placa;
  final String renavam;
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
        title: Text(title),
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
              _VehicleQueryCard(
                colorScheme: colorScheme,
                placa: placa,
                renavam: renavam,
              ),
              const SizedBox(height: 16),
              Expanded(
                child: _BaseEstadualResultView(
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

class _BaseEstadualStructuredScreen extends StatelessWidget {
  const _BaseEstadualStructuredScreen({
    required this.title,
    required this.data,
    required this.formattedPayload,
  });

  final String title;
  final _BaseEstadualStructuredPayload data;
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
                _BlueHeader(
                  title: title,
                  onBack: () => Navigator.of(context).pop(),
                  onShare: () => _shareBaseEstadualPdf(context, data),
                ),
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
                    child: Column(
                      children: [
                        const SizedBox(height: 12),
                        _VehicleSummaryCard(
                          data: data,
                          onViewFull: () => _openVehiclePage(context),
                        ),
                        const SizedBox(height: 16),
                        _ActionMenuCard(
                          onVehicleTap: () => _openVehiclePage(context),
                          onGravameTap: () => _openGravamePage(context),
                          onDebitosTap: () => _openDebitosPage(context),
                          onRestricoesTap: () => _openRestricoesPage(context),
                          onComunicacaoTap: () => _openComunicacaoPage(context),
                        ),
                        const SizedBox(height: 24),
                        _SectionCard(
                          title: 'Fonte',
                          children: _buildInfoRows(
                            data.fonte,
                            const {
                              'titulo': 'Título',
                              'gerado_em': 'Gerado em',
                            },
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

  void _openVehiclePage(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _VehicleInfoPage(data: data),
      ),
    );
  }

  void _openGravamePage(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _GravameInfoPage(data: data),
      ),
    );
  }

  void _openDebitosPage(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _DebitosMultasPage(data: data),
      ),
    );
  }

  void _openRestricoesPage(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _RestricoesInfoPage(data: data),
      ),
    );
  }

  void _openComunicacaoPage(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _ComunicacaoVendasPage(data: data),
      ),
    );
  }

}

class _BlueHeader extends StatelessWidget {
  const _BlueHeader({
    required this.title,
    this.onBack,
    this.onShare,
  });

  final String title;
  final VoidCallback? onBack;
  final VoidCallback? onShare;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              _HeaderCircleButton(
                icon: Icons.arrow_back,
                onTap: onBack ?? () => Navigator.of(context).pop(),
              ),
              Expanded(
                child: Center(
                  child: Image.asset(
                    'assets/images/logoll.png',
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
          const SizedBox(height: 12),
          Text(
            title,
            style: theme.textTheme.titleLarge?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
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

class _VehicleSummaryCard extends StatelessWidget {
  const _VehicleSummaryCard({
    required this.data,
    required this.onViewFull,
  });

  final _BaseEstadualStructuredPayload data;
  final VoidCallback onViewFull;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final veiculo = data.veiculo;
    final placa = _formatDisplayValue(veiculo['placa']);
    final marca = _parseMarca(veiculo['marca']);
    final anoModelo = _formatDisplayValue(veiculo['ano_modelo']);
    final anoFab = _formatDisplayValue(veiculo['ano_fabricacao']);
    final municipio = _formatDisplayValue(veiculo['municipio']);
    final proprietario = _formatDisplayValue(data.proprietario?['nome']);
    final licenciamentoEx = _formatDisplayValue(
      data.crvCrlvAtualizacao?['exercicio_licenciamento'],
    );
    final licenciamentoData = _formatDisplayValue(
      data.crvCrlvAtualizacao?['data_licenciamento'],
    );
    final licStatus = licenciamentoData != '—' ? 'em dia' : 'Não informado';

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
                  Icons.directions_car_filled_outlined,
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
                      placa,
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      marca,
                      style: theme.textTheme.titleMedium?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      _buildAnoModelo(anoModelo, anoFab),
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
                child: _VehicleSummaryTile(
                  label: 'Licenciamento',
                  value: licenciamentoEx,
                  badge: licStatus,
                  badgeColor: Colors.greenAccent.shade400,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _VehicleSummaryTile(
                  label: 'Município',
                  value: municipio,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Proprietário',
                      style: theme.textTheme.labelSmall?.copyWith(
                        color: theme.colorScheme.outline,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      proprietario,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
              TextButton(
                onPressed: onViewFull,
                child: const Text('Ver completo'),
              ),
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

  static String _buildAnoModelo(String modelo, String fabricacao) {
    if (modelo == '—' && fabricacao == '—') {
      return 'Ano não informado';
    }
    if (modelo != '—' && fabricacao != '—') {
      return '$modelo / $fabricacao';
    }
    return modelo != '—' ? modelo : fabricacao;
  }
}

class _VehicleSummaryTile extends StatelessWidget {
  const _VehicleSummaryTile({
    required this.label,
    required this.value,
    this.badge,
    this.badgeColor,
  });

  final String label;
  final String value;
  final String? badge;
  final Color? badgeColor;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceVariant.withOpacity(0.4),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: theme.textTheme.labelSmall?.copyWith(
              color: theme.colorScheme.outline,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            value,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          if (badge != null) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: (badgeColor ?? theme.colorScheme.primary)
                    .withOpacity(0.15),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                badge!,
                style: theme.textTheme.labelSmall?.copyWith(
                  color: badgeColor ?? theme.colorScheme.primary,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _ActionMenuCard extends StatelessWidget {
  const _ActionMenuCard({
    required this.onVehicleTap,
    required this.onGravameTap,
    required this.onDebitosTap,
    required this.onRestricoesTap,
    required this.onComunicacaoTap,
  });

  final VoidCallback onVehicleTap;
  final VoidCallback onGravameTap;
  final VoidCallback onDebitosTap;
  final VoidCallback onRestricoesTap;
  final VoidCallback onComunicacaoTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final dividerColor = theme.colorScheme.outlineVariant.withOpacity(0.35);

    return Card(
      elevation: 6,
      shadowColor: Colors.black.withOpacity(0.08),
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(24),
      ),
      child: Column(
        children: [
          _ActionMenuItem(
            icon: Icons.directions_car_outlined,
            label: 'Informações do veículo',
            onTap: onVehicleTap,
          ),
          Divider(height: 1, thickness: 1, color: dividerColor),
          _ActionMenuItem(
            icon: Icons.credit_card,
            label: 'Gravame',
            onTap: onGravameTap,
          ),
          Divider(height: 1, thickness: 1, color: dividerColor),
          _ActionMenuItem(
            icon: Icons.warning_amber_outlined,
            label: 'Multas e débitos',
            onTap: onDebitosTap,
          ),
          Divider(height: 1, thickness: 1, color: dividerColor),
          _ActionMenuItem(
            icon: Icons.lock_outline,
            label: 'Restrições',
            onTap: onRestricoesTap,
          ),
          Divider(height: 1, thickness: 1, color: dividerColor),
          _ActionMenuItem(
            icon: Icons.forum_outlined,
            label: 'Comunicações de venda',
            onTap: onComunicacaoTap,
          ),
        ],
      ),
    );
  }
}

class _ActionMenuItem extends StatelessWidget {
  const _ActionMenuItem({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(24),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        child: Row(
          children: [
            Icon(icon, color: theme.colorScheme.primary),
            const SizedBox(width: 16),
            Expanded(
              child: Text(
                label,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
            Icon(
              Icons.chevron_right,
              color: theme.colorScheme.outline,
            ),
          ],
        ),
      ),
    );
  }
}

class _VehicleInfoPage extends StatelessWidget {
  const _VehicleInfoPage({required this.data});

  final _BaseEstadualStructuredPayload data;

  @override
  Widget build(BuildContext context) {
    final content = <Widget>[];

    final vehicleRows = _buildInfoRows(
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
    );
    if (vehicleRows.isNotEmpty) {
      content.add(
        _SectionCard(
          title: 'Veículo',
          children: vehicleRows,
        ),
      );
    }

    final proprietarioRows = _buildInfoRows(
      data.proprietario,
      const {
        'nome': 'Nome',
      },
    );
    if (proprietarioRows.isNotEmpty) {
      content.add(
        _SectionCard(
          title: 'Proprietário',
          children: proprietarioRows,
        ),
      );
    }

    final crvRows = _buildInfoRows(
      data.crvCrlvAtualizacao,
      const {
        'exercicio_licenciamento': 'Exercício licenciamento',
        'data_licenciamento': 'Data licenciamento',
      },
    );
    if (crvRows.isNotEmpty) {
      content.add(
        _SectionCard(
          title: 'CRV / CRLV',
          children: crvRows,
        ),
      );
    }

    return _BaseEstadualDetailScaffold(
      title: 'Informações do veículo',
      children: content,
      onShare: () => _shareBaseEstadualPdf(context, data),
      emptyMessage: 'Nenhuma informação do veículo disponível.',
    );
  }
}

class _GravameInfoPage extends StatelessWidget {
  const _GravameInfoPage({required this.data});

  final _BaseEstadualStructuredPayload data;

  @override
  Widget build(BuildContext context) {
    final content = <Widget>[];

    content.add(_GravameSummaryCard(data: data));

    final gravamesRows = _buildInfoRows(
      data.gravames,
      const {
        'restricao_financeira': 'Restrição financeira',
        'nome_agente': 'Nome do agente',
        'arrendatario': 'Arrendatário',
        'cnpj_cpf_financiado': 'CNPJ/CPF financiado',
      },
    );
    final gravamesDatasRows = _buildInfoRows(
      data.gravamesDatas,
      const {
        'inclusao_financiamento': 'Inclusão financiamento',
      },
    );

    if (gravamesRows.isNotEmpty || gravamesDatasRows.isNotEmpty) {
      content.add(
        _SectionCard(
          title: 'Gravame atual',
          children: [
            ...gravamesRows,
            if (gravamesDatasRows.isNotEmpty) ...[
              const SizedBox(height: 12),
              const _SectionSubheading('Datas'),
              ...gravamesDatasRows,
            ],
          ],
        ),
      );
    }

    final intencaoRows = _buildInfoRows(
      data.intencaoGravame,
      const {
        'restricao_financeira': 'Restrição financeira',
        'agente_financeiro': 'Agente financeiro',
        'nome_financiado': 'Nome financiado',
        'cnpj_cpf': 'CNPJ/CPF',
        'data_inclusao': 'Data inclusão',
      },
    );

    if (intencaoRows.isNotEmpty) {
      content.add(
        _SectionCard(
          title: 'Intenção de gravame',
          children: intencaoRows,
        ),
      );
    }

    return _BaseEstadualDetailScaffold(
      title: 'Gravame',
      children: content,
      onShare: () => _shareBaseEstadualPdf(context, data),
      emptyMessage: 'Nenhuma informação de gravame encontrada.',
    );
  }
}

class _GravameSummaryCard extends StatelessWidget {
  const _GravameSummaryCard({required this.data});

  final _BaseEstadualStructuredPayload data;

  bool get _hasGravameInfo => (data.gravames ?? {}).isNotEmpty;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final gravame = data.gravames ?? <String, dynamic>{};
    final restricao = _formatDisplayValue(gravame['restricao_financeira']);
    final agente = _formatDisplayValue(gravame['nome_agente']);
    final arrendatario = _formatDisplayValue(gravame['arrendatario']);
    final cnpj = _formatDisplayValue(gravame['cnpj_cpf_financiado']);
    final inclusao = _formatDisplayValue(
      data.gravamesDatas?['inclusao_financiamento'],
    );

    final ativo = _hasGravameInfo &&
        restricao != '—' &&
        !RegExp(r'nada\s*consta', caseSensitive: false).hasMatch(restricao);
    final statusLabel = ativo ? 'Ativo' : 'Inativo';
    final statusColor =
        ativo ? Colors.orangeAccent : theme.colorScheme.outline;
    final statusBackground = ativo
        ? Colors.orangeAccent.withOpacity(0.18)
        : theme.colorScheme.outline.withOpacity(0.18);

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
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary.withOpacity(0.15),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Icon(
                  Icons.credit_card,
                  color: theme.colorScheme.primary,
                  size: 28,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Gravame: $statusLabel',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      inclusao == '—'
                          ? 'Inclusão não informada'
                          : 'Inclusão: $inclusao',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.outline,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: statusBackground,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Text(
                  statusLabel,
                  style: theme.textTheme.labelSmall?.copyWith(
                    color: statusColor,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          _GravameDetailLine(
            label: 'Restrição financeira',
            value: restricao,
            chipLabel: ativo ? 'Ativo' : 'Inativo',
            chipColor: statusColor,
          ),
          const Divider(height: 24),
          _GravameDetailLine(
            label: 'Agente financeiro',
            value: agente,
          ),
          const Divider(height: 24),
          _GravameDetailLine(
            label: 'Arrendatário / Financiado',
            value: arrendatario,
          ),
          const Divider(height: 24),
          _GravameDetailLine(
            label: 'CNPJ do financiado',
            value: cnpj,
          ),
        ],
      ),
    );
  }
}

class _GravameDetailLine extends StatelessWidget {
  const _GravameDetailLine({
    required this.label,
    required this.value,
    this.chipLabel,
    this.chipColor,
  });

  final String label;
  final String value;
  final String? chipLabel;
  final Color? chipColor;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                label,
                style: theme.textTheme.labelSmall?.copyWith(
                  color: theme.colorScheme.outline,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
            if (chipLabel != null)
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color:
                      (chipColor ?? theme.colorScheme.primary).withOpacity(0.18),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  chipLabel!,
                  style: theme.textTheme.labelSmall?.copyWith(
                    color: chipColor ?? theme.colorScheme.primary,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
          ],
        ),
        const SizedBox(height: 6),
        Text(
          value,
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

class _DebitosMultasPage extends StatelessWidget {
  const _DebitosMultasPage({required this.data});

  final _BaseEstadualStructuredPayload data;

  @override
  Widget build(BuildContext context) {
    return _BaseEstadualDetailScaffold(
      title: 'Multas e débitos',
      children: [
        _DebitosSummaryCard(data: data),
      ],
      onShare: () => _shareBaseEstadualPdf(context, data),
      emptyMessage: 'Nenhum débito informado.',
    );
  }
}

class _DebitosSummaryCard extends StatelessWidget {
  const _DebitosSummaryCard({required this.data});

  final _BaseEstadualStructuredPayload data;

  static const Map<String, String> _labels = {
    'dersa': 'DERSA',
    'der': 'DER',
    'detran': 'DETRAN',
    'cetesb': 'CETESB',
    'renainf': 'RENAINF',
    'municipais': 'Municipais',
    'prf': 'Polícia Rodoviária Federal',
    'ipva': 'IPVA',
  };

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final debitos = data.debitosMultas ?? <String, dynamic>{};

    double total = 0;
    final entries = <_DebitoEntry>[];

    for (final entry in _labels.entries) {
      final raw = debitos[entry.key];
      final amount = _parseCurrencyValue(raw);
      total += amount;
      entries.add(
        _DebitoEntry(
          label: entry.value,
          amount: _formatCurrencyBr(raw),
          isOpen: amount > 0,
        ),
      );
    }

    final hasAny = entries.any((e) => e.isOpen);

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
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: (hasAny
                          ? theme.colorScheme.error
                          : Colors.green.shade600)
                      .withOpacity(0.15),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Icon(
                  hasAny ? Icons.warning_amber_rounded : Icons.check_circle,
                  color: hasAny
                      ? theme.colorScheme.error
                      : Colors.green.shade600,
                  size: 30,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Total em aberto',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'R\$ ${_formatCurrency(total)}',
                      style: theme.textTheme.headlineSmall?.copyWith(
                        color: hasAny
                            ? theme.colorScheme.error
                            : Colors.green.shade600,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          ..._buildEntryWidgets(entries),
        ],
      ),
    );
  }

  static List<Widget> _buildEntryWidgets(List<_DebitoEntry> entries) {
    final widgets = <Widget>[];
    for (var i = 0; i < entries.length; i++) {
      widgets.add(_DebitoItemRow(entry: entries[i]));
      if (i < entries.length - 1) {
        widgets.add(const SizedBox(height: 12));
      }
    }
    return widgets;
  }

  static double _parseCurrencyValue(dynamic raw) {
    if (raw == null) return 0;
    if (raw is num) return raw.toDouble();
    final text = raw.toString().trim();
    if (text.isEmpty) return 0;

    final sanitized = text.replaceAll(RegExp(r'[^\d.,-]'), '');
    if (sanitized.isEmpty) return 0;

    final lastDot = sanitized.lastIndexOf('.');
    final lastComma = sanitized.lastIndexOf(',');
    var decimalIndex = -1;

    if (lastDot >= 0 || lastComma >= 0) {
      final candidateIndex = lastDot > lastComma ? lastDot : lastComma;
      final digitsAfterSeparator = sanitized.length - candidateIndex - 1;
      if (digitsAfterSeparator <= 2) {
        decimalIndex = candidateIndex;
      }
    }

    final buffer = StringBuffer();
    for (var i = 0; i < sanitized.length; i++) {
      final char = sanitized[i];
      if (char == '.' || char == ',') {
        if (i == decimalIndex) {
          buffer.write('.');
        }
        continue;
      }
      if (char == '-' && buffer.isEmpty) {
        buffer.write(char);
        continue;
      }
      buffer.write(char);
    }

    final normalized = buffer.toString();
    if (normalized.isEmpty || normalized == '-' || normalized == '+') {
      return 0;
    }

    return double.tryParse(normalized) ?? 0;
  }

  static String _formatCurrency(double value) {
    return value.toStringAsFixed(2).replaceAll('.', ',');
  }

  static String _formatCurrencyBr(dynamic raw) {
    if (raw == null) return '0,00';
    if (raw is num) {
      return raw.toStringAsFixed(2).replaceAll('.', ',');
    }
    final text = raw.toString().trim();
    if (text.isEmpty) return '0,00';
    return text;
  }
}

class _DebitoEntry {
  const _DebitoEntry({
    required this.label,
    required this.amount,
    required this.isOpen,
  });

  final String label;
  final String amount;
  final bool isOpen;
}

class _DebitoItemRow extends StatelessWidget {
  const _DebitoItemRow({required this.entry});

  final _DebitoEntry entry;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final bool isOpen = entry.isOpen;
    final statusText = isOpen ? 'Em aberto' : 'Nenhum';
    final statusColor =
        isOpen ? theme.colorScheme.error : Colors.green.shade600;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceVariant.withOpacity(0.25),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        children: [
          Expanded(
            child: Text(
              entry.label,
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                'R\$ ${entry.amount}',
                style: theme.textTheme.titleSmall?.copyWith(
                  color: isOpen
                      ? theme.colorScheme.error
                      : Colors.green.shade600,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                statusText,
                style: theme.textTheme.labelSmall?.copyWith(
                  color: statusColor,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _RestricoesInfoPage extends StatelessWidget {
  const _RestricoesInfoPage({required this.data});

  final _BaseEstadualStructuredPayload data;

  @override
  Widget build(BuildContext context) {
    final restricoesRows = _buildInfoRows(
      data.restricoes,
      const {
        'furto': 'Furto',
        'bloqueio_guincho': 'Bloqueio de guincho',
        'administrativas': 'Administrativas',
        'judicial': 'Judicial',
        'tributaria': 'Tributária',
        'renajud': 'RENAJUD',
        'inspecao_ambiental': 'Inspeção ambiental',
      },
    );

    final content = <Widget>[];
    if (restricoesRows.isNotEmpty) {
      content.add(
        _SectionCard(
          title: 'Restrições',
          children: restricoesRows,
        ),
      );
    }

    return _BaseEstadualDetailScaffold(
      title: 'Restrições',
      children: content,
      onShare: () => _shareBaseEstadualPdf(context, data),
      emptyMessage: 'Nenhuma restrição informada.',
    );
  }
}

class _ComunicacaoVendasPage extends StatelessWidget {
  const _ComunicacaoVendasPage({required this.data});

  final _BaseEstadualStructuredPayload data;

  @override
  Widget build(BuildContext context) {
    final comunicacaoRows = _buildInfoRows(
      data.comunicacaoVendas,
      const {
        'status': 'Status',
        'inclusao': 'Inclusão',
        'tipo_doc_comprador': 'Tipo documento comprador',
        'cnpj_cpf_comprador': 'CNPJ/CPF comprador',
        'origem': 'Origem',
      },
    );

    final datasRows = _buildInfoRows(
      data.comunicacaoVendasDatas,
      const {
        'venda': 'Venda',
        'nota_fiscal': 'Nota fiscal',
        'protocolo_detran': 'Protocolo DETRAN',
      },
    );

    final content = <Widget>[];
    if (comunicacaoRows.isNotEmpty || datasRows.isNotEmpty) {
      content.add(
        _SectionCard(
          title: 'Comunicações de venda',
          children: [
            ...comunicacaoRows,
            if (datasRows.isNotEmpty) ...[
              const SizedBox(height: 12),
              const _SectionSubheading('Datas'),
              ...datasRows,
            ],
          ],
        ),
      );
    }

    return _BaseEstadualDetailScaffold(
      title: 'Comunicações de venda',
      children: content,
      onShare: () => _shareBaseEstadualPdf(context, data),
      emptyMessage: 'Nenhuma comunicação de venda registrada.',
    );
  }
}

class _BaseEstadualDetailScaffold extends StatelessWidget {
  const _BaseEstadualDetailScaffold({
    required this.title,
    required this.children,
    required this.emptyMessage,
    this.onShare,
  });

  final String title;
  final List<Widget> children;
  final String emptyMessage;
  final VoidCallback? onShare;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      backgroundColor: theme.colorScheme.surfaceVariant.withOpacity(0.08),
      body: SafeArea(
        child: Stack(
          children: [
            Container(
              height: 200,
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
                _BlueHeader(
                  title: title,
                  onBack: () => Navigator.of(context).pop(),
                  onShare: onShare,
                ),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
                    child: children.isEmpty
                        ? _EmptySectionMessage(emptyMessage)
                        : Scrollbar(
                            thumbVisibility: true,
                            child: SingleChildScrollView(
                              child: Column(
                                children: _withSpacing(children),
                              ),
                            ),
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

  List<Widget> _withSpacing(List<Widget> items) {
    final spaced = <Widget>[];
    for (final item in items) {
      if (spaced.isNotEmpty) {
        spaced.add(const SizedBox(height: 16));
      }
      spaced.add(item);
    }
    return spaced;
  }
}

class _EmptySectionMessage extends StatelessWidget {
  const _EmptySectionMessage(this.message);

  final String message;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Center(
      child: Text(
        message,
        style: theme.textTheme.bodyLarge?.copyWith(
          color: theme.colorScheme.outline,
        ),
        textAlign: TextAlign.center,
      ),
    );
  }
}

Future<void> _copyJsonToClipboard(
  BuildContext context,
  Map<String, dynamic?> data,
) async {
  final text = const JsonEncoder.withIndent('  ').convert(data);
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

Future<void> _shareBaseEstadualPdf(
  BuildContext context,
  _BaseEstadualStructuredPayload data,
) async {
  bool dialogOpened = false;
  showDialog<void>(
    context: context,
    barrierDismissible: false,
    builder: (_) => const Center(child: CircularProgressIndicator()),
  );
  dialogOpened = true;

  try {
    final generator = _BaseEstadualPdfGenerator();
    final bytes = await generator.generate(data);
    if (dialogOpened && context.mounted) {
      Navigator.of(context, rootNavigator: true).pop();
      dialogOpened = false;
    }

    final placa = _formatDisplayValue(data.veiculo['placa']);
    final sanitized = placa.replaceAll(RegExp(r'[^A-Za-z0-9]'), '');
    final filename =
        'pesquisa_base_estadual_${sanitized.isEmpty ? 'consulta' : sanitized}.pdf';

    await Share.shareXFiles(
      [
        XFile.fromData(
          bytes,
          mimeType: 'application/pdf',
          name: filename,
        ),
      ],
      text: 'Pesquisa Base Estadual',
      subject: 'Pesquisa Base Estadual',
    );

    if (context.mounted) {
      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          const SnackBar(
            content: Text('PDF gerado. Escolha o app para compartilhar.'),
          ),
        );
    }
  } catch (error) {
    if (dialogOpened && context.mounted) {
      Navigator.of(context, rootNavigator: true).pop();
    }
    if (context.mounted) {
      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          SnackBar(
            content: Text(
              'Não foi possível gerar o PDF (${error.toString()}).',
            ),
          ),
        );
    }
  }
}

class _PdfField {
  const _PdfField({required this.label, required this.value});

  final String label;
  final String value;
}

class _PdfSection {
  const _PdfSection({required this.title, required this.fields});

  final String title;
  final List<_PdfField> fields;
}

class _BaseEstadualPdfGenerator {
  Future<Uint8List> generate(_BaseEstadualStructuredPayload data) async {
    final doc = pw.Document();
    final logo = await _loadLogo();
    final sections = _buildSections(data);

    doc.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.fromLTRB(32, 28, 32, 36),
        build: (context) => [
          _buildHeader(logo, data),
          pw.SizedBox(height: 18),
          ...sections.map(_buildSection),
        ],
      ),
    );

    return doc.save();
  }

  Future<pw.MemoryImage?> _loadLogo() async {
    try {
      final data = await rootBundle.load('assets/images/logoll.png');
      return pw.MemoryImage(data.buffer.asUint8List());
    } catch (_) {
      return null;
    }
  }

  pw.Widget _buildHeader(
    pw.MemoryImage? logo,
    _BaseEstadualStructuredPayload data,
  ) {
    final now = DateTime.now();
    final dateFormatted =
        '${_twoDigits(now.day)}/${_twoDigits(now.month)}/${now.year} - '
        '${_twoDigits(now.hour)}:${_twoDigits(now.minute)}:${_twoDigits(now.second)}';
    final placa = _formatDisplayValue(data.veiculo['placa']);
    final renavam = _formatDisplayValue(data.veiculo['renavam']);

    return pw.Column(
      crossAxisAlignment: pw.CrossAxisAlignment.start,
      children: [
        pw.Row(
          crossAxisAlignment: pw.CrossAxisAlignment.start,
          children: [
            if (logo != null)
              pw.Container(
                width: 70,
                height: 70,
                decoration: pw.BoxDecoration(
                  borderRadius: pw.BorderRadius.circular(16),
                ),
                child: pw.Image(logo),
              ),
            if (logo != null) pw.SizedBox(width: 16),
            pw.Expanded(
              child: pw.Column(
                crossAxisAlignment: pw.CrossAxisAlignment.start,
                children: [
                  pw.Text(
                    'LL DESPACHANTE',
                    style: pw.TextStyle(
                      fontSize: 18,
                      fontWeight: pw.FontWeight.bold,
                      color: PdfColors.blue900,
                    ),
                  ),
                  pw.Text(
                    'Telefone: (13) 99773-1533',
                    style: const pw.TextStyle(fontSize: 10),
                  ),
                ],
              ),
            ),
          ],
        ),
        pw.SizedBox(height: 14),
        pw.Center(
          child: pw.Column(
            children: [
              pw.Text(
                'PESQUISA BASE ESTADUAL',
                style: pw.TextStyle(
                  fontSize: 14,
                  fontWeight: pw.FontWeight.bold,
                  color: PdfColors.blue800,
                ),
              ),
              pw.Text(
                'Data da pesquisa: $dateFormatted',
                style: const pw.TextStyle(fontSize: 10),
              ),
            ],
          ),
        ),
        pw.SizedBox(height: 10),
        pw.Text(
          'Placa: $placa   |   Renavam: $renavam',
          style: const pw.TextStyle(fontSize: 11),
        ),
      ],
    );
  }

  pw.Widget _buildSection(_PdfSection section) {
    return pw.Container(
      margin: const pw.EdgeInsets.only(bottom: 16),
      padding: const pw.EdgeInsets.all(14),
      decoration: pw.BoxDecoration(
        borderRadius: pw.BorderRadius.circular(10),
        border: pw.Border.all(color: PdfColors.blueGrey300, width: 0.6),
      ),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.Text(
            section.title,
            style: pw.TextStyle(
              fontSize: 12,
              fontWeight: pw.FontWeight.bold,
              color: PdfColors.blue800,
            ),
          ),
          pw.SizedBox(height: 8),
          _buildFieldsGrid(section.fields),
        ],
      ),
    );
  }

  pw.Widget _buildFieldsGrid(List<_PdfField> fields) {
    final rows = <pw.TableRow>[];
    for (var i = 0; i < fields.length; i += 2) {
      final first = fields[i];
      final second = i + 1 < fields.length ? fields[i + 1] : null;
      rows.add(
        pw.TableRow(
          children: [
            _buildFieldCell(first),
            if (second != null) _buildFieldCell(second) else pw.Container(),
          ],
        ),
      );
    }

    return pw.Table(
      columnWidths: const {
        0: pw.FlexColumnWidth(1),
        1: pw.FlexColumnWidth(1),
      },
      defaultVerticalAlignment: pw.TableCellVerticalAlignment.middle,
      children: rows,
    );
  }

  pw.Widget _buildFieldCell(_PdfField field) {
    return pw.Padding(
      padding: const pw.EdgeInsets.all(4),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.Text(
            field.label,
            style: pw.TextStyle(
              fontSize: 9,
              fontWeight: pw.FontWeight.bold,
              color: PdfColors.blueGrey700,
            ),
          ),
          pw.SizedBox(height: 2),
          pw.Text(
            field.value,
            style: const pw.TextStyle(fontSize: 10),
          ),
        ],
      ),
    );
  }

  List<_PdfSection> _buildSections(_BaseEstadualStructuredPayload data) {
    final sections = <_PdfSection>[];

    sections.add(
      _PdfSection(
        title: 'Identificação do veículo',
        fields: _filterFields([
          _field('Proprietário', data.proprietario?['nome']),
          _field(
            'CPF/CNPJ',
            data.proprietario?['cpf_cnpj'] ??
                data.proprietario?['documento'] ??
                data.proprietario?['cpf'] ??
                data.proprietario?['cnpj'],
          ),
          _field('Placa', data.veiculo['placa']),
          _field('Renavam', data.veiculo['renavam']),
          _field('Chassi', data.veiculo['chassi']),
          _field('Município', data.veiculo['municipio']),
          _field('UF', data.veiculo['uf']),
          _field('Data da inclusão', data.veiculo['data_inclusao']),
          _field('Exercício licenciamento',
              data.crvCrlvAtualizacao?['exercicio_licenciamento']),
          _field('Data licenciamento',
              data.crvCrlvAtualizacao?['data_licenciamento']),
          _field('Data emissão CRV', data.veiculo['data_emissao_crv']),
          _field('Número do motor', data.veiculo['numero_motor']),
          _field('Proprietário anterior', data.veiculo['proprietario_anterior']),
        ]),
      ),
    );

    sections.add(
      _PdfSection(
        title: 'Características do veículo',
        fields: _filterFields([
          _field('Marca/Modelo', data.veiculo['marca']),
          _field('Ano fabricação', data.veiculo['ano_fabricacao']),
          _field('Ano modelo', data.veiculo['ano_modelo']),
          _field('Tipo', data.veiculo['tipo']),
          _field('Cor', data.veiculo['cor']),
          _field('Procedência', data.veiculo['procedencia']),
          _field('Categoria', data.veiculo['categoria']),
          _field('Combustível', data.veiculo['combustivel']),
          _field('Capacidade', data.veiculo['capacidade']),
          _field('Cilindrada', data.veiculo['cilindrada']),
          _field('Potência', data.veiculo['potencia']),
          _field('Capac. carga', data.veiculo['capacidade_carga']),
        ]),
      ),
    );

    sections.add(
      _PdfSection(
        title: 'Gravame',
        fields: _filterFields([
          _field('Restrição financeira', data.gravames?['restricao_financeira']),
          _field('Agente financeiro', data.gravames?['nome_agente']),
          _field('Arrendatário/Financiado', data.gravames?['arrendatario']),
          _field('CPF/CNPJ financiado', data.gravames?['cnpj_cpf_financiado']),
          _field('Inclusão financiamento',
              data.gravamesDatas?['inclusao_financiamento']),
          _field('Agente intenção', data.intencaoGravame?['agente_financeiro']),
          _field('Nome financiado', data.intencaoGravame?['nome_financiado']),
          _field('CPF/CNPJ intenção', data.intencaoGravame?['cnpj_cpf']),
          _field('Data inclusão intenção', data.intencaoGravame?['data_inclusao']),
        ]),
      ),
    );

    sections.add(
      _PdfSection(
        title: 'Restrições / Bloqueios',
        fields: _filterFields([
          _field('Furto', data.restricoes?['furto']),
          _field('Guincho', data.restricoes?['bloqueio_guincho']),
          _field('Administrativas', data.restricoes?['administrativas']),
          _field('Judicial', data.restricoes?['judicial']),
          _field('Tributária', data.restricoes?['tributaria']),
          _field('RENAJUD', data.restricoes?['renajud']),
          _field('Inspeção ambiental', data.restricoes?['inspecao_ambiental']),
        ]),
      ),
    );

    sections.add(
      _PdfSection(
        title: 'Multas e débitos',
        fields: _filterFields([
          _currencyField('DER', data.debitosMultas?['der']),
          _currencyField('DETRAN', data.debitosMultas?['detran']),
          _currencyField('DERSA', data.debitosMultas?['dersa']),
          _currencyField('CETESB', data.debitosMultas?['cetesb']),
          _currencyField('RENAINF', data.debitosMultas?['renainf']),
          _currencyField('Municipais', data.debitosMultas?['municipais']),
          _currencyField('IPVA', data.debitosMultas?['ipva']),
          _currencyField('Polícia Rod. Fed.', data.debitosMultas?['prf']),
        ]),
      ),
    );

    sections.add(
      _PdfSection(
        title: 'Comunicação de venda',
        fields: _filterFields([
          _field('Status', data.comunicacaoVendas?['status']),
          _field('Inclusão', data.comunicacaoVendas?['inclusao']),
          _field('Tipo doc. comprador',
              data.comunicacaoVendas?['tipo_doc_comprador']),
          _field('CPF/CNPJ comprador',
              data.comunicacaoVendas?['cnpj_cpf_comprador']),
          _field('Origem', data.comunicacaoVendas?['origem']),
          _field('Data venda', data.comunicacaoVendasDatas?['venda']),
          _field('Nota fiscal', data.comunicacaoVendasDatas?['nota_fiscal']),
          _field('Protocolo Detran',
              data.comunicacaoVendasDatas?['protocolo_detran']),
        ]),
      ),
    );

    return sections.where((section) => section.fields.isNotEmpty).toList();
  }

  List<_PdfField> _filterFields(List<_PdfField?> items) {
    return items.whereType<_PdfField>().toList();
  }

  _PdfField? _field(String label, dynamic value) {
    final text = _formatDisplayValue(value);
    if (text == '—') return null;
    return _PdfField(label: label, value: text);
  }

  _PdfField? _currencyField(String label, dynamic value) {
    if (value == null) return null;
    final text = value.toString().trim();
    if (text.isEmpty) return null;
    return _PdfField(label: label, value: 'R\$ $text');
  }

  String _twoDigits(int value) => value.toString().padLeft(2, '0');
}

List<Widget> _buildInfoRows(
  Map<String, dynamic>? source,
  Map<String, String> labels,
) {
  if (source == null) {
    return [];
  }
  return labels.entries
      .map(
        (entry) => _InfoRow(
          label: entry.value,
          value: source[entry.key],
        ),
      )
      .toList();
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({
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

class _SectionSubheading extends StatelessWidget {
  const _SectionSubheading(this.text);

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

class _InfoRow extends StatelessWidget {
  const _InfoRow({
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

class _BaseEstadualStructuredPayload {
  const _BaseEstadualStructuredPayload({
    required this.fonte,
    required this.veiculo,
    this.proprietario,
    this.gravames,
    this.gravamesDatas,
    this.intencaoGravame,
    this.debitosMultas,
    this.restricoes,
    this.crvCrlvAtualizacao,
    this.comunicacaoVendas,
    this.comunicacaoVendasDatas,
  });

  final Map<String, dynamic> fonte;
  final Map<String, dynamic> veiculo;
  final Map<String, dynamic>? proprietario;
  final Map<String, dynamic>? gravames;
  final Map<String, dynamic>? gravamesDatas;
  final Map<String, dynamic>? intencaoGravame;
  final Map<String, dynamic>? debitosMultas;
  final Map<String, dynamic>? restricoes;
  final Map<String, dynamic>? crvCrlvAtualizacao;
  final Map<String, dynamic>? comunicacaoVendas;
  final Map<String, dynamic>? comunicacaoVendasDatas;

  static _BaseEstadualStructuredPayload? tryParse(
    Map<String, dynamic> payload,
  ) {
    final fonte = _asMap(payload['fonte']);
    final veiculo = _asMap(payload['veiculo']);

    if (fonte == null || veiculo == null) {
      return null;
    }

    final gravames = _asMap(payload['gravames']);
    final comunicacao = _asMap(payload['comunicacao_vendas']);

    return _BaseEstadualStructuredPayload(
      fonte: fonte,
      veiculo: veiculo,
      proprietario: _asMap(payload['proprietario']),
      gravames: gravames,
      gravamesDatas: _asMap(gravames?['datas']),
      intencaoGravame: _asMap(payload['intencao_gravame']),
      debitosMultas: _asMap(payload['debitos_multas']),
      restricoes: _asMap(payload['restricoes']),
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
