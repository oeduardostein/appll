import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class BaseOutrosEstadosPage extends StatelessWidget {
  const BaseOutrosEstadosPage({
    super.key,
    required this.chassi,
    required this.payload,
  });

  final String chassi;
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
    final structuredPayload =
        _BaseOutrosEstadosStructuredPayload.tryParse(payload);

    if (structuredPayload != null) {
      return _BaseOutrosEstadosStructuredScreen(
        chassi: chassi,
        data: structuredPayload,
        formattedPayload: _formattedPayload,
      );
    }

    return _BaseOutrosEstadosFallbackScreen(
      chassi: chassi,
      payload: payload,
      formattedPayload: _formattedPayload,
    );
  }
}

class _BaseOutrosEstadosFallbackScreen extends StatelessWidget {
  const _BaseOutrosEstadosFallbackScreen({
    required this.chassi,
    required this.payload,
    required this.formattedPayload,
  });

  final String chassi;
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
        title: const Text('Base outros estados'),
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
              _ChassiQueryCard(
                colorScheme: colorScheme,
                chassi: chassi,
              ),
              const SizedBox(height: 16),
              Expanded(
                child: _BaseOutrosEstadosResultView(
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

class _ChassiQueryCard extends StatelessWidget {
  const _ChassiQueryCard({
    required this.colorScheme,
    required this.chassi,
  });

  final ColorScheme colorScheme;
  final String chassi;

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
          _QueryInfoTile(
            label: 'Chassi',
            value: chassi,
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

class _BaseOutrosEstadosResultView extends StatelessWidget {
  const _BaseOutrosEstadosResultView({
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

class _BaseOutrosEstadosStructuredScreen extends StatelessWidget {
  const _BaseOutrosEstadosStructuredScreen({
    required this.chassi,
    required this.data,
    required this.formattedPayload,
  });

  final String chassi;
  final _BaseOutrosEstadosStructuredPayload data;
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
                _OutrosEstadosHeader(
                  title: 'Base outros estados',
                  onBack: () => Navigator.of(context).pop(),
                  onShare: () => _copyJsonToClipboard(
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
                        _OutrosEstadosSummaryCard(
                          chassi: chassi,
                          data: data,
                          onViewVehicle: () => _openVehiclePage(context),
                        ),
                        const SizedBox(height: 16),
                        _OutrosEstadosActionMenu(
                          onVehicleTap: () => _openVehiclePage(context),
                          onGravameTap: () => _openGravamePage(context),
                          onDebitosTap: () => _openDebitosPage(context),
                          onRestricoesTap: () => _openRestricoesPage(context),
                          onComunicacaoTap: () => _openComunicacaoPage(context),
                        ),
                        const SizedBox(height: 24),
                        _OutrosSectionCard(
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
        builder: (_) => _OutrosEstadosVehiclePage(chassi: chassi, data: data),
      ),
    );
  }

  void _openGravamePage(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _OutrosEstadosGravamePage(data: data),
      ),
    );
  }

  void _openDebitosPage(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _OutrosEstadosDebitosPage(data: data),
      ),
    );
  }

  void _openRestricoesPage(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _OutrosEstadosRestricoesPage(data: data),
      ),
    );
  }

  void _openComunicacaoPage(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _OutrosEstadosComunicacaoPage(data: data),
      ),
    );
  }
}

class _OutrosEstadosHeader extends StatelessWidget {
  const _OutrosEstadosHeader({
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

class _OutrosEstadosSummaryCard extends StatelessWidget {
  const _OutrosEstadosSummaryCard({
    required this.chassi,
    required this.data,
    required this.onViewVehicle,
  });

  final String chassi;
  final _BaseOutrosEstadosStructuredPayload data;
  final VoidCallback onViewVehicle;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final veiculo = data.veiculo;
    final placa = _formatDisplayValue(veiculo['placa']);
    final marca = _parseMarca(veiculo['marca']);
    final municipio = _formatDisplayValue(veiculo['municipio']);
    final proprietario = _formatDisplayValue(data.proprietario?['nome']);
    final anoModelo = _formatDisplayValue(veiculo['ano_modelo']);
    final anoFab = _formatDisplayValue(veiculo['ano_fabricacao']);

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
          _SummaryInfoRow(
            label: 'Chassi consultado',
            value: chassi,
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _SummaryTile(
                  label: 'Município',
                  value: municipio,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _SummaryTile(
                  label: 'Proprietário',
                  value: proprietario,
                  maxLines: 2,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Align(
            alignment: Alignment.centerRight,
            child: TextButton(
              onPressed: onViewVehicle,
              child: const Text('Ver completo'),
            ),
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
    if (modelo == '—' && fabricacao == '—') return 'Ano não informado';
    if (modelo != '—' && fabricacao != '—') {
      return '$modelo / $fabricacao';
    }
    return modelo != '—' ? modelo : fabricacao;
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

class _SummaryTile extends StatelessWidget {
  const _SummaryTile({
    required this.label,
    required this.value,
    this.maxLines = 1,
  });

  final String label;
  final String value;
  final int maxLines;

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
            maxLines: maxLines,
            overflow: TextOverflow.ellipsis,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _OutrosEstadosActionMenu extends StatelessWidget {
  const _OutrosEstadosActionMenu({
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

class _OutrosEstadosVehiclePage extends StatelessWidget {
  const _OutrosEstadosVehiclePage({
    required this.chassi,
    required this.data,
  });

  final String chassi;
  final _BaseOutrosEstadosStructuredPayload data;

  @override
  Widget build(BuildContext context) {
    final sections = <Widget>[];

    final veiculoRows = _buildInfoRows(
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
    if (veiculoRows.isNotEmpty) {
      sections.add(
        _OutrosSectionCard(
          title: 'Veículo',
          children: veiculoRows,
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
      sections.add(
        _OutrosSectionCard(
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
      sections.add(
        _OutrosSectionCard(
          title: 'CRV / CRLV',
          children: crvRows,
        ),
      );
    }

    sections.add(
      _OutrosSectionCard(
        title: 'Consulta',
        children: [
          _SummaryInfoRow(
            label: 'Chassi consultado',
            value: chassi,
          ),
        ],
      ),
    );

    return _OutrosEstadosDetailScaffold(
      title: 'Informações do veículo',
      children: sections,
      emptyMessage: 'Nenhuma informação do veículo disponível.',
      onShare: () => _copyJsonToClipboard(
        context,
        payload: {
          'chassi': chassi,
          'veiculo': data.veiculo,
          'proprietario': data.proprietario,
          'crv_crlv_atualizacao': data.crvCrlvAtualizacao,
        },
      ),
    );
  }
}

class _OutrosEstadosGravamePage extends StatelessWidget {
  const _OutrosEstadosGravamePage({required this.data});

  final _BaseOutrosEstadosStructuredPayload data;

  @override
  Widget build(BuildContext context) {
    final sections = <Widget>[];

    sections.add(_GravameSummaryCard(data: data));

    final gravameRows = _buildInfoRows(
      data.gravames,
      const {
        'restricao_financeira': 'Restrição financeira',
        'nome_agente': 'Nome do agente',
        'arrendatario': 'Arrendatário',
        'cnpj_cpf_financiado': 'CNPJ/CPF financiado',
      },
      excludeKeys: const {'datas'},
    );

    final gravameDatas = _buildInfoRows(
      data.gravamesDatas,
      const {
        'inclusao_financiamento': 'Inclusão financiamento',
      },
    );

    if (gravameRows.isNotEmpty || gravameDatas.isNotEmpty) {
      sections.add(
        _OutrosSectionCard(
          title: 'Gravame atual',
          children: [
            ...gravameRows,
            if (gravameDatas.isNotEmpty) ...[
              const SizedBox(height: 12),
              const _OutrosSectionSubheading('Datas'),
              ...gravameDatas,
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
      sections.add(
        _OutrosSectionCard(
          title: 'Intenção de gravame',
          children: intencaoRows,
        ),
      );
    }

    return _OutrosEstadosDetailScaffold(
      title: 'Gravame',
      children: sections,
      emptyMessage: 'Nenhuma informação de gravame encontrada.',
      onShare: () => _copyJsonToClipboard(
        context,
        payload: {
          'gravames': data.gravames,
          'gravames_datas': data.gravamesDatas,
          'intencao_gravame': data.intencaoGravame,
        },
      ),
    );
  }
}

class _OutrosEstadosDebitosPage extends StatelessWidget {
  const _OutrosEstadosDebitosPage({required this.data});

  final _BaseOutrosEstadosStructuredPayload data;

  @override
  Widget build(BuildContext context) {
    return _OutrosEstadosDetailScaffold(
      title: 'Multas e débitos',
      children: [
        _DebitosSummaryCard(data: data),
      ],
      emptyMessage: 'Nenhum débito informado.',
      onShare: () => _copyJsonToClipboard(
        context,
        payload: {
          'debitos_multas': data.debitosMultas,
        },
      ),
    );
  }
}

class _OutrosEstadosRestricoesPage extends StatelessWidget {
  const _OutrosEstadosRestricoesPage({required this.data});

  final _BaseOutrosEstadosStructuredPayload data;

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

    final sections = <Widget>[];
    if (restricoesRows.isNotEmpty) {
      sections.add(
        _OutrosSectionCard(
          title: 'Restrições',
          children: restricoesRows,
        ),
      );
    }

    return _OutrosEstadosDetailScaffold(
      title: 'Restrições',
      children: sections,
      emptyMessage: 'Nenhuma restrição informada.',
      onShare: () => _copyJsonToClipboard(
        context,
        payload: {
          'restricoes': data.restricoes,
        },
      ),
    );
  }
}

class _OutrosEstadosComunicacaoPage extends StatelessWidget {
  const _OutrosEstadosComunicacaoPage({required this.data});

  final _BaseOutrosEstadosStructuredPayload data;

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
      excludeKeys: const {'datas'},
    );

    final datasRows = _buildInfoRows(
      data.comunicacaoVendasDatas,
      const {
        'venda': 'Venda',
        'nota_fiscal': 'Nota fiscal',
        'protocolo_detran': 'Protocolo DETRAN',
      },
    );

    final sections = <Widget>[];
    if (comunicacaoRows.isNotEmpty || datasRows.isNotEmpty) {
      sections.add(
        _OutrosSectionCard(
          title: 'Comunicações de venda',
          children: [
            ...comunicacaoRows,
            if (datasRows.isNotEmpty) ...[
              const SizedBox(height: 12),
              const _OutrosSectionSubheading('Datas'),
              ...datasRows,
            ],
          ],
        ),
      );
    }

    return _OutrosEstadosDetailScaffold(
      title: 'Comunicações de venda',
      children: sections,
      emptyMessage: 'Nenhuma comunicação de venda registrada.',
      onShare: () => _copyJsonToClipboard(
        context,
        payload: {
          'comunicacao_vendas': data.comunicacaoVendas,
          'comunicacao_vendas_datas': data.comunicacaoVendasDatas,
        },
      ),
    );
  }
}

class _OutrosEstadosDetailScaffold extends StatelessWidget {
  const _OutrosEstadosDetailScaffold({
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
                _OutrosEstadosHeader(
                  title: title,
                  onBack: () => Navigator.of(context).pop(),
                  onShare: onShare,
                ),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
                    child: children.isEmpty
                        ? const _EmptySectionMessage('Nenhuma informação encontrada.')
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

class _GravameSummaryCard extends StatelessWidget {
  const _GravameSummaryCard({required this.data});

  final _BaseOutrosEstadosStructuredPayload data;

  bool get _hasGravameInfo => (data.gravames ?? {}).isNotEmpty;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final gravame = data.gravames ?? <String, dynamic>{};
    final restricao = _formatDisplayValue(gravame['restricao_financeira']);
    final inclusao = _formatDisplayValue(
      data.gravamesDatas?['inclusao_financiamento'],
    );
    final ativo = _hasGravameInfo &&
        restricao != '—' &&
        !RegExp(r'nada\s*consta', caseSensitive: false).hasMatch(restricao);
    final statusColor =
        ativo ? Colors.orangeAccent : theme.colorScheme.outline;
    final background = statusColor.withOpacity(0.18);

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
                      'Gravame',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      restricao,
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
                  color: background,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Text(
                  ativo ? 'Ativo' : 'Inativo',
                  style: theme.textTheme.labelSmall?.copyWith(
                    color: statusColor,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          _SummaryInfoRow(
            label: 'Inclusão',
            value: inclusao,
          ),
        ],
      ),
    );
  }
}

class _DebitosSummaryCard extends StatelessWidget {
  const _DebitosSummaryCard({required this.data});

  final _BaseOutrosEstadosStructuredPayload data;

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
          amount: _formatCurrency(raw),
          isOpen: amount > 0,
        ),
      );
    }

    final hasOpen = entries.any((it) => it.isOpen);

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
                  color: (hasOpen
                          ? theme.colorScheme.error
                          : Colors.green.shade600)
                      .withOpacity(0.15),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Icon(
                  hasOpen ? Icons.warning_amber_rounded : Icons.check_circle,
                  color: hasOpen
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
                      'R\$ ${total.toStringAsFixed(2).replaceAll('.', ',')}',
                      style: theme.textTheme.headlineSmall?.copyWith(
                        color: hasOpen
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
          ..._buildDebitEntries(entries, theme),
        ],
      ),
    );
  }

  static List<Widget> _buildDebitEntries(
    List<_DebitoEntry> entries,
    ThemeData theme,
  ) {
    final widgets = <Widget>[];
    for (var i = 0; i < entries.length; i++) {
      final entry = entries[i];
      widgets.add(
        Container(
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
                      color: entry.isOpen
                          ? theme.colorScheme.error
                          : Colors.green.shade600,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    entry.isOpen ? 'Em aberto' : 'Nenhum',
                    style: theme.textTheme.labelSmall?.copyWith(
                      color: entry.isOpen
                          ? theme.colorScheme.error
                          : Colors.green.shade600,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      );
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
    final normalized = text.replaceAll('.', '').replaceAll(',', '.');
    return double.tryParse(normalized) ?? 0;
  }

  static String _formatCurrency(dynamic raw) {
    if (raw == null) return '0,00';
    if (raw is num) return raw.toStringAsFixed(2).replaceAll('.', ',');
    final text = raw.toString().trim();
    return text.isEmpty ? '0,00' : text;
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

class _OutrosSectionCard extends StatelessWidget {
  const _OutrosSectionCard({
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

class _OutrosSectionSubheading extends StatelessWidget {
  const _OutrosSectionSubheading(this.text);

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
  Map<String, String> labels, {
  Set<String> excludeKeys = const {},
}) {
  if (source == null) {
    return [];
  }

  final rows = <Widget>[];
  final handledKeys = <String>{};

  for (final entry in labels.entries) {
    handledKeys.add(entry.key);
    final value = source[entry.key];
    if (!_hasDisplayValue(value)) continue;
    rows.add(
      _OutrosInfoRow(
        label: entry.value,
        value: value,
      ),
    );
  }

  for (final entry in source.entries) {
    final key = entry.key;
    if (handledKeys.contains(key) || excludeKeys.contains(key)) {
      continue;
    }
    final value = entry.value;
    if (!_hasDisplayValue(value)) continue;
    rows.add(
      _OutrosInfoRow(
        label: _generateAutoLabel(key),
        value: value,
      ),
    );
  }

  return rows;
}

bool _hasDisplayValue(dynamic value) {
  if (value == null) return false;
  if (value is String) {
    return value.trim().isNotEmpty;
  }
  if (value is Iterable) {
    return value.isNotEmpty;
  }
  if (value is Map) {
    return value.isNotEmpty;
  }
  return true;
}

String _generateAutoLabel(String key) {
  if (key.trim().isEmpty) return 'Valor';
  String working = key
      .replaceAll(RegExp(r'[\-]+'), ' ')
      .replaceAll(RegExp(r'[_]+'), ' ');
  working = working.replaceAllMapped(
    RegExp(r'(?<=[a-z0-9])([A-Z])'),
    (match) => ' ${match.group(1)}',
  );

  final buffer = StringBuffer();
  for (final word in working.split(RegExp(r'\s+'))) {
    if (word.isEmpty) continue;
    if (buffer.isNotEmpty) buffer.write(' ');
    final cleaned = word.toLowerCase();
    buffer.write(cleaned[0].toUpperCase());
    if (cleaned.length > 1) {
      buffer.write(cleaned.substring(1));
    }
  }

  return buffer.isEmpty ? key : buffer.toString();
}

String _formatDisplayValue(dynamic value) {
  if (value == null) return '—';
  if (value is String) {
    final trimmed = value.trim();
    return trimmed.isEmpty ? '—' : trimmed;
  }
  return value.toString();
}

class _OutrosInfoRow extends StatelessWidget {
  const _OutrosInfoRow({
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

class _BaseOutrosEstadosStructuredPayload {
  const _BaseOutrosEstadosStructuredPayload({
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

  static _BaseOutrosEstadosStructuredPayload? tryParse(
    Map<String, dynamic> payload,
  ) {
    final fonte = _asMap(payload['fonte']);
    final veiculo = _asMap(payload['veiculo']);

    if (fonte == null || veiculo == null) {
      return null;
    }

    final gravames = _asMap(payload['gravames']);
    final comunicacao = _asMap(payload['comunicacao_vendas']);

    return _BaseOutrosEstadosStructuredPayload(
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
