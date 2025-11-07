import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:share_plus/share_plus.dart';

import 'package:frontend_app/ui/widgets/response_top_bar.dart';
import 'package:frontend_app/utils/pdf_share_helper.dart';

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
        rawPayload: payload,
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
      appBar: ResponseTopBar(
        title: 'Base outros estados',
        subtitle: 'Chassi: $chassi',
        onShare: hasOnlyMessage ? null : () => _sharePdf(context),
        actions: !hasOnlyMessage
            ? [
                IconButton(
                  tooltip: 'Copiar resultado',
                  onPressed: () => _copyJsonToClipboard(context, formatted: formattedPayload),
                  icon: const Icon(Icons.copy_outlined),
                ),
              ]
            : const [],
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
  Future<void> _sharePdf(BuildContext context) async {
    try {
      await PdfShareHelper.share(
        title: 'Base outros estados',
        filenamePrefix: 'pesquisa_base_outros_estados',
        data: payload,
        subtitle: 'Chassi: $chassi',
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
    required this.rawPayload,
  });

  final String chassi;
  final _BaseOutrosEstadosStructuredPayload data;
  final String formattedPayload;
  final Map<String, dynamic> rawPayload;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: ResponseTopBar(
        title: 'Base outros estados',
        subtitle: 'Chassi: $chassi',
        onShare: () => _sharePdf(context),
        actions: [
          IconButton(
            tooltip: 'Copiar resultado',
            onPressed: () => _copyJsonToClipboard(context, formatted: formattedPayload),
            icon: const Icon(Icons.copy_outlined),
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
          child: Column(
            children: [
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
        builder: (_) => _OutrosEstadosGravamePage(chassi: chassi, data: data),
      ),
    );
  }

  void _openDebitosPage(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _OutrosEstadosDebitosPage(chassi: chassi, data: data),
      ),
    );
  }

  void _openRestricoesPage(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _OutrosEstadosRestricoesPage(chassi: chassi, data: data),
      ),
    );
  }

  void _openComunicacaoPage(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _OutrosEstadosComunicacaoPage(chassi: chassi, data: data),
      ),
    );
  }

  Future<void> _sharePdf(BuildContext context) async {
    bool dialogOpened = false;
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );
    dialogOpened = true;

    try {
      final generator = _BaseOutrosEstadosPdfGenerator();
      final bytes = await generator.generateFullReport(
        data: data,
        chassi: chassi,
      );

      if (dialogOpened && context.mounted) {
        Navigator.of(context, rootNavigator: true).pop();
        dialogOpened = false;
      }

      final sanitized = chassi.replaceAll(RegExp(r'[^A-Za-z0-9]'), '');
      final filename =
          'pesquisa_base_outros_estados_${sanitized.isEmpty ? 'consulta' : sanitized}.pdf';

      await Share.shareXFiles(
        [
          XFile.fromData(
            bytes,
            mimeType: 'application/pdf',
            name: filename,
          ),
        ],
        text: 'Pesquisa Base Outros Estados',
        subject: 'Pesquisa Base Outros Estados',
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
        dialogOpened = false;
      }
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
      shareBuilder: (generator) {
        final pdfSections = [
          _pdfSectionFromMap(
            'Veículo',
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
          _pdfSectionFromMap(
            'Proprietário',
            data.proprietario,
            const {
              'nome': 'Nome',
              'cnpj_cpf': 'CNPJ/CPF',
              'tipo_documento': 'Tipo de documento',
              'municipio': 'Município',
              'uf': 'UF',
            },
          ),
          _pdfSectionFromMap(
            'CRV / CRLV',
            data.crvCrlvAtualizacao,
            const {
              'exercicio_licenciamento': 'Exercício licenciamento',
              'data_licenciamento': 'Data licenciamento',
            },
          ),
        ].whereType<_OutrosPdfSection>().toList();

        return generator.generateCustomReport(
          data: data,
          chassi: chassi,
          reportTitle: 'INFORMAÇÕES DO VEÍCULO - BASE OUTROS ESTADOS',
          sections: pdfSections,
          subtitle: 'Chassi: $chassi',
        );
      },
      shareFilePrefix: 'relatorio_veiculo_outros_estados',
      shareSubject: 'Informações do veículo - Base outros estados',
      shareSubtitle: 'Chassi: $chassi',
      onCopy: () => _copyJsonToClipboard(
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
  const _OutrosEstadosGravamePage({required this.chassi, required this.data});

  final String chassi;
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
      shareSubtitle: 'Chassi: $chassi',
      shareBuilder: (generator) {
        final pdfSections = [
          _pdfSectionFromMap(
            'Gravame atual',
            data.gravames,
            const {
              'restricao_financeira': 'Restrição financeira',
              'nome_agente': 'Nome do agente',
              'arrendatario': 'Arrendatário',
              'cnpj_cpf_financiado': 'CNPJ/CPF financiado',
            },
            excludeKeys: const {'datas'},
          ),
          _pdfSectionFromMap(
            'Gravame - Datas',
            data.gravamesDatas,
            const {
              'inclusao_financiamento': 'Inclusão financiamento',
            },
          ),
          _pdfSectionFromMap(
            'Intenção de gravame',
            data.intencaoGravame,
            const {
              'restricao_financeira': 'Restrição financeira',
              'agente_financeiro': 'Agente financeiro',
              'nome_financiado': 'Nome financiado',
              'cnpj_cpf': 'CNPJ/CPF',
              'data_inclusao': 'Data inclusão',
            },
          ),
        ].whereType<_OutrosPdfSection>().toList();

        return generator.generateCustomReport(
          data: data,
          chassi: chassi,
          reportTitle: 'GRAVAME - BASE OUTROS ESTADOS',
          sections: pdfSections,
          subtitle: 'Chassi: $chassi',
        );
      },
      shareFilePrefix: 'relatorio_gravame_outros_estados',
      shareSubject: 'Gravame - Base outros estados',
      onCopy: () => _copyJsonToClipboard(
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
  const _OutrosEstadosDebitosPage({required this.chassi, required this.data});

  final String chassi;
  final _BaseOutrosEstadosStructuredPayload data;

  @override
  Widget build(BuildContext context) {
    return _OutrosEstadosDetailScaffold(
      title: 'Multas e débitos',
      children: [
        _DebitosSummaryCard(data: data),
      ],
      emptyMessage: 'Nenhum débito informado.',
      shareSubtitle: 'Chassi: $chassi',
      shareBuilder: (generator) {
        final section = _buildDebitosPdfSection(data.debitosMultas);
        return generator.generateCustomReport(
          data: data,
          chassi: chassi,
          reportTitle: 'MULTAS E DÉBITOS - BASE OUTROS ESTADOS',
          sections: section == null ? [] : [section],
          subtitle: 'Chassi: $chassi',
        );
      },
      shareFilePrefix: 'relatorio_multas_outros_estados',
      shareSubject: 'Multas e débitos - Base outros estados',
      onCopy: () => _copyJsonToClipboard(
        context,
        payload: {
          'debitos_multas': data.debitosMultas,
        },
      ),
    );
  }
}

class _OutrosEstadosRestricoesPage extends StatelessWidget {
  const _OutrosEstadosRestricoesPage({required this.chassi, required this.data});

  final String chassi;
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
      shareSubtitle: 'Chassi: $chassi',
      shareBuilder: (generator) {
        final section = _pdfSectionFromMap(
          'Restrições',
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

        return generator.generateCustomReport(
          data: data,
          chassi: chassi,
          reportTitle: 'RESTRIÇÕES - BASE OUTROS ESTADOS',
          sections: section == null ? [] : [section],
          subtitle: 'Chassi: $chassi',
        );
      },
      shareFilePrefix: 'relatorio_restricoes_outros_estados',
      shareSubject: 'Restrições - Base outros estados',
      onCopy: () => _copyJsonToClipboard(
        context,
        payload: {
          'restricoes': data.restricoes,
        },
      ),
    );
  }
}

class _OutrosEstadosComunicacaoPage extends StatelessWidget {
  const _OutrosEstadosComunicacaoPage({required this.chassi, required this.data});

  final String chassi;
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
      shareSubtitle: 'Chassi: $chassi',
      shareBuilder: (generator) {
        final section = _pdfSectionFromMap(
          'Comunicação de venda',
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

        final datasSection = _pdfSectionFromMap(
          'Comunicação de venda - Datas',
          data.comunicacaoVendasDatas,
          const {
            'venda': 'Venda',
            'nota_fiscal': 'Nota fiscal',
            'protocolo_detran': 'Protocolo DETRAN',
          },
        );

        final sectionsList = [section, datasSection]
            .whereType<_OutrosPdfSection>()
            .toList();

        return generator.generateCustomReport(
          data: data,
          chassi: chassi,
          reportTitle: 'COMUNICAÇÃO DE VENDA - BASE OUTROS ESTADOS',
          sections: sectionsList,
          subtitle: 'Chassi: $chassi',
        );
      },
      shareFilePrefix: 'relatorio_comunicacao_outros_estados',
      shareSubject: 'Comunicações de venda - Base outros estados',
      onCopy: () => _copyJsonToClipboard(
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
    this.shareBuilder,
    this.shareSubtitle,
    this.shareFilePrefix = 'pesquisa_base_outros_estados',
    this.shareSubject = 'Pesquisa Base Outros Estados',
    this.onCopy,
  });

  final String title;
  final List<Widget> children;
  final String emptyMessage;
  final Future<Uint8List> Function(_BaseOutrosEstadosPdfGenerator generator)?
      shareBuilder;
  final String? shareSubtitle;
  final String shareFilePrefix;
  final String shareSubject;
  final VoidCallback? onCopy;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: ResponseTopBar(
        title: title,
        subtitle: shareSubtitle,
        onShare: shareBuilder != null ? () => _sharePdf(context) : null,
        actions: [
          if (onCopy != null)
            IconButton(
              tooltip: 'Copiar resultado',
              onPressed: onCopy,
              icon: const Icon(Icons.copy_outlined),
            ),
        ],
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
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

  Future<void> _sharePdf(BuildContext context) async {
    if (shareBuilder == null) return;
    bool dialogOpened = false;
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );
    dialogOpened = true;

    try {
      final generator = _BaseOutrosEstadosPdfGenerator();
      final bytes = await shareBuilder!(generator);

      if (dialogOpened && context.mounted) {
        Navigator.of(context, rootNavigator: true).pop();
        dialogOpened = false;
      }

      final filename =
          '${shareFilePrefix}_${DateTime.now().millisecondsSinceEpoch}.pdf';

      await Share.shareXFiles(
        [
          XFile.fromData(
            bytes,
            mimeType: 'application/pdf',
            name: filename,
          ),
        ],
        text: shareSubject,
        subject: shareSubject,
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
        dialogOpened = false;
      }
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

double _parseCurrencyValue(dynamic raw) {
  if (raw == null) return 0;
  if (raw is num) return raw.toDouble();
  final text = raw.toString().trim();
  if (text.isEmpty) return 0;
  final normalized = text.replaceAll('.', '').replaceAll(',', '.');
  return double.tryParse(normalized) ?? 0;
}

String _formatCurrency(dynamic raw) {
  if (raw == null) return '0,00';
  if (raw is num) return raw.toStringAsFixed(2).replaceAll('.', ',');
  final text = raw.toString().trim();
  return text.isEmpty ? '0,00' : text;
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
  Map<String, dynamic>? payload,
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

class _OutrosPdfField {
  const _OutrosPdfField({required this.label, required this.value});

  final String label;
  final String value;
}

class _OutrosPdfSection {
  const _OutrosPdfSection({required this.title, required this.fields});

  final String title;
  final List<_OutrosPdfField> fields;
}

class _BaseOutrosEstadosPdfGenerator {
  Future<Uint8List> generateFullReport({
    required _BaseOutrosEstadosStructuredPayload data,
    required String chassi,
  }) async {
    final sections = _buildFullSections(data);
    return _buildDocument(
      data: data,
      chassi: chassi,
      reportTitle: 'PESQUISA BASE OUTROS ESTADOS',
      subtitle: 'Chassi: ${_formatDisplayValue(chassi)}',
      sections: sections,
    );
  }

  Future<Uint8List> generateCustomReport({
    required _BaseOutrosEstadosStructuredPayload data,
    required String chassi,
    required String reportTitle,
    required List<_OutrosPdfSection> sections,
    String? subtitle,
  }) async {
    final effectiveSections = sections.isEmpty
        ? [
            _OutrosPdfSection(
              title: 'Dados',
              fields: [
                _OutrosPdfField(
                  label: 'Mensagem',
                  value: 'Nenhuma informação disponível para exibir.',
                ),
              ],
            ),
          ]
        : sections;
    return _buildDocument(
      data: data,
      chassi: chassi,
      reportTitle: reportTitle,
      subtitle: subtitle ?? 'Chassi: ${_formatDisplayValue(chassi)}',
      sections: effectiveSections,
    );
  }

  Future<Uint8List> _buildDocument({
    required _BaseOutrosEstadosStructuredPayload data,
    required String chassi,
    required String reportTitle,
    required List<_OutrosPdfSection> sections,
    String? subtitle,
  }) async {
    final doc = pw.Document();
    final logo = await _loadLogo();
    final placa = _formatDisplayValue(data.veiculo['placa']);
    final renavam = _formatDisplayValue(data.veiculo['renavam']);

    doc.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.fromLTRB(32, 28, 32, 36),
        build: (context) => [
          _buildHeader(
            logo: logo,
            reportTitle: reportTitle,
            placa: placa,
            renavam: renavam,
            chassi: _formatDisplayValue(chassi),
            subtitle: subtitle,
          ),
          pw.SizedBox(height: 18),
          ...sections.map(_buildSection),
        ],
      ),
    );

    return doc.save();
  }

  Future<pw.MemoryImage?> _loadLogo() async {
    try {
      final data = await rootBundle.load('assets/images/logoLL.png');
      return pw.MemoryImage(data.buffer.asUint8List());
    } catch (_) {
      return null;
    }
  }

  pw.Widget _buildHeader({
    required pw.MemoryImage? logo,
    required String reportTitle,
    required String placa,
    required String renavam,
    required String chassi,
    String? subtitle,
  }) {
    final now = DateTime.now();
    final dateFormatted =
        '${_twoDigits(now.day)}/${_twoDigits(now.month)}/${now.year} - '
        '${_twoDigits(now.hour)}:${_twoDigits(now.minute)}:${_twoDigits(now.second)}';

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
                    'AV. DES. PLÍNIO DE CARVALHO PINTO, 05 - ENSEADA - (13) 99730-1533 / 11 3367-8400\nGUARUJÁ - SP',
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
                reportTitle,
                textAlign: pw.TextAlign.center,
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
              if (subtitle != null && subtitle.trim().isNotEmpty)
                pw.Text(
                  subtitle,
                  style: const pw.TextStyle(fontSize: 10),
                ),
            ],
          ),
        ),
        pw.SizedBox(height: 10),
        pw.Text(
          'Placa: $placa   |   Renavam: $renavam   |   Chassi: $chassi',
          style: const pw.TextStyle(fontSize: 11),
        ),
      ],
    );
  }

  pw.Widget _buildSection(_OutrosPdfSection section) {
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

  pw.Widget _buildFieldsGrid(List<_OutrosPdfField> fields) {
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

  pw.Widget _buildFieldCell(_OutrosPdfField field) {
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
            style: pw.TextStyle(
              fontSize: 11,
              color: PdfColors.blueGrey900,
            ),
          ),
        ],
      ),
    );
  }

  List<_OutrosPdfSection> _buildFullSections(
    _BaseOutrosEstadosStructuredPayload data,
  ) {
    final sections = <_OutrosPdfSection?>[
      _pdfSectionFromMap(
        'Fonte',
        data.fonte,
        const {
          'titulo': 'Título',
          'gerado_em': 'Gerado em',
        },
      ),
      _pdfSectionFromMap(
        'Identificação do veículo',
        {
          ...data.veiculo,
          if (data.proprietario != null)
            'proprietario_nome': data.proprietario?['nome'],
        },
        const {
          'placa': 'Placa',
          'renavam': 'Renavam',
          'chassi': 'Chassi',
          'municipio': 'Município',
          'uf': 'UF',
          'proprietario_nome': 'Proprietário',
        },
      ),
      _pdfSectionFromMap(
        'Características do veículo',
        data.veiculo,
        const {
          'marca': 'Marca',
          'modelo': 'Modelo',
          'categoria': 'Categoria',
          'segmento': 'Segmento',
          'tipo': 'Tipo',
          'combustivel': 'Combustível',
          'procedencia': 'Procedência',
          'cor': 'Cor',
          'ano_modelo': 'Ano modelo',
          'ano_fabricacao': 'Ano fabricação',
        },
      ),
      _pdfSectionFromMap(
        'CRV / CRLV',
        data.crvCrlvAtualizacao,
        const {
          'exercicio_licenciamento': 'Exercício licenciamento',
          'data_licenciamento': 'Data licenciamento',
        },
      ),
      _pdfSectionFromMap(
        'Gravame atual',
        data.gravames,
        const {
          'restricao_financeira': 'Restrição financeira',
          'nome_agente': 'Nome do agente',
          'arrendatario': 'Arrendatário',
          'cnpj_cpf_financiado': 'CNPJ/CPF financiado',
          'numero_contrato': 'Número do contrato',
        },
        excludeKeys: const {'datas'},
      ),
      _pdfSectionFromMap(
        'Gravame - Datas',
        data.gravamesDatas,
        const {
          'inclusao_financiamento': 'Inclusão financiamento',
        },
      ),
      _pdfSectionFromMap(
        'Intenção de gravame',
        data.intencaoGravame,
        const {
          'restricao_financeira': 'Restrição financeira',
          'agente_financeiro': 'Agente financeiro',
          'nome_financiado': 'Nome financiado',
          'cnpj_cpf': 'CNPJ/CPF',
          'data_inclusao': 'Data inclusão',
        },
      ),
      _buildDebitosPdfSection(data.debitosMultas),
      _pdfSectionFromMap(
        'Restrições',
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
      ),
      _pdfSectionFromMap(
        'Comunicação de venda',
        data.comunicacaoVendas,
        const {
          'status': 'Status',
          'inclusao': 'Inclusão',
          'tipo_doc_comprador': 'Tipo documento comprador',
          'cnpj_cpf_comprador': 'CNPJ/CPF comprador',
          'origem': 'Origem',
        },
        excludeKeys: const {'datas'},
      ),
      _pdfSectionFromMap(
        'Comunicação de venda - Datas',
        data.comunicacaoVendasDatas,
        const {
          'venda': 'Venda',
          'nota_fiscal': 'Nota fiscal',
          'protocolo_detran': 'Protocolo DETRAN',
        },
      ),
    ];

    return sections.whereType<_OutrosPdfSection>().toList();
  }

}

List<_OutrosPdfField> _buildPdfFieldsFromMap(
  Map<String, dynamic>? source,
  Map<String, String> labels, {
  Set<String> excludeKeys = const {},
}) {
  if (source == null) return [];

  final fields = <_OutrosPdfField>[];
  final handled = <String>{};

  for (final entry in labels.entries) {
    handled.add(entry.key);
    final value = source[entry.key];
    if (!_hasDisplayValue(value)) continue;
    fields.add(
      _OutrosPdfField(
        label: entry.value,
        value: _formatDisplayValue(value),
      ),
    );
  }

  for (final entry in source.entries) {
    final key = entry.key;
    if (handled.contains(key) || excludeKeys.contains(key)) {
      continue;
    }
    final value = entry.value;
    if (!_hasDisplayValue(value)) continue;
    fields.add(
      _OutrosPdfField(
        label: _generateAutoLabel(key),
        value: _formatDisplayValue(value),
      ),
    );
  }

  return fields;
}

_OutrosPdfSection? _pdfSectionFromMap(
  String title,
  Map<String, dynamic>? source,
  Map<String, String> labels, {
  Set<String> excludeKeys = const {},
}) {
  final fields = _buildPdfFieldsFromMap(
    source,
    labels,
    excludeKeys: excludeKeys,
  );
  if (fields.isEmpty) return null;
  return _OutrosPdfSection(title: title, fields: fields);
}

_OutrosPdfSection? _buildDebitosPdfSection(Map<String, dynamic>? debitos) {
  if (debitos == null || debitos.isEmpty) {
    return null;
  }
  const labels = {
    'dersa': 'DERSA',
    'der': 'DER',
    'detran': 'DETRAN',
    'cetesb': 'CETESB',
    'renainf': 'RENAINF',
    'municipais': 'Municipais',
    'prf': 'Polícia Rodoviária Federal',
    'ipva': 'IPVA',
  };

  final fields = <_OutrosPdfField>[];
  for (final entry in labels.entries) {
    final raw = debitos[entry.key];
    final amount = _formatCurrency(raw);
    fields.add(
      _OutrosPdfField(
        label: entry.value,
        value: 'R\$ $amount',
      ),
    );
  }

  if (fields.isEmpty) return null;
  return _OutrosPdfSection(title: 'Multas e débitos', fields: fields);
}

String _twoDigits(int value) => value.toString().padLeft(2, '0');
