import 'dart:convert';

import 'package:flutter/material.dart';

import '../shared/gravame_details_card.dart';

class GravamePage extends StatelessWidget {
  const GravamePage({
    super.key,
    required this.placa,
    required this.uf,
    required this.payload,
    this.renavam,
  });

  final String placa;
  final String uf;
  final String? renavam;
  final Map<String, dynamic> payload;

  @override
  Widget build(BuildContext context) {
    final fonte = _asMap(payload['fonte']);
    final veiculo = _asMap(payload['veiculo']);
    final gravames = _asMap(payload['gravames']);
    final gravamesDatas = _asMap(payload['gravames_datas']);
    final intencao = _asMap(payload['intencao_gravame']);
    final origin = payload['origin']?.toString();

    final inclusionDate = _nonEmptyString(intencao?['data_inclusao']) ??
        _nonEmptyString(gravamesDatas?['inclusao_financiamento']) ??
        '—';

    final restricaoFinanceira =
        _nonEmptyString(intencao?['restricao_financeira']) ??
            _nonEmptyString(gravames?['restricao_financeira']) ??
            '—';

    final agenteFinanceiro = _nonEmptyString(intencao?['agente_financeiro']) ??
        _nonEmptyString(gravames?['nome_agente']) ??
        '—';

    final nomeFinanciado = _nonEmptyString(intencao?['nome_financiado']) ??
        _nonEmptyString(gravames?['arrendatario']) ??
        '—';

    final documentoFinanciado = _nonEmptyString(intencao?['cnpj_cpf']) ??
        _nonEmptyString(gravames?['cnpj_cpf_financiado']) ??
        '—';

    final arrendatario = _nonEmptyString(gravames?['arrendatario']);

    final statusLabel =
        restricaoFinanceira != '—' || _nonEmptyString(intencao?['restricao_financeira']) != null
            ? 'Ativo'
            : 'Não encontrado';

    final formattedJson = const JsonEncoder.withIndent('  ').convert(payload);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Gravame'),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _GravameSummaryCard(
                placa: placa,
                renavam: renavam,
                uf: uf,
                origin: origin,
                veiculo: veiculo,
                fonte: fonte,
              ),
              const SizedBox(height: 16),
              GravameDetailsCard(
                statusLabel: statusLabel,
                inclusionDate: inclusionDate,
                restricaoFinanceira: restricaoFinanceira,
                agenteFinanceiro: agenteFinanceiro,
                nomeFinanciado: nomeFinanciado,
                documentoFinanciado: documentoFinanciado,
                arrendatario: arrendatario,
              ),
              const SizedBox(height: 24),
              Text(
                'Resposta completa',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
              ),
              const SizedBox(height: 12),
              Container(
                decoration: BoxDecoration(
                  color: Colors.black.withOpacity(0.04),
                  borderRadius: BorderRadius.circular(16),
                ),
                padding: const EdgeInsets.all(16),
                child: SelectableText(
                  formattedJson,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        fontFamily: 'monospace',
                        height: 1.4,
                      ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Map<String, dynamic>? _asMap(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) {
      return value.map(
        (key, dynamic val) => MapEntry(key.toString(), val),
      );
    }
    return null;
  }

  String? _nonEmptyString(dynamic value) {
    if (value == null) return null;
    final text = value.toString().trim();
    if (text.isEmpty) return null;
    if (RegExp(r'^-+$').hasMatch(text)) return null;
    return text;
  }
}

class _GravameSummaryCard extends StatelessWidget {
  const _GravameSummaryCard({
    required this.placa,
    required this.uf,
    this.origin,
    this.veiculo,
    this.fonte,
    this.renavam,
  });

  final String placa;
  final String uf;
  final String? renavam;
  final String? origin;
  final Map<String, dynamic>? veiculo;
  final Map<String, dynamic>? fonte;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final generatedAt = fonte != null ? fonte!['gerado_em']?.toString() : null;
    final procedencia =
        veiculo != null ? veiculo!['procedencia']?.toString() : null;

    Widget buildTile(String label, String value) {
      return Expanded(
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: colorScheme.outlineVariant.withOpacity(0.3),
            ),
          ),
          child: Column(
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
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: colorScheme.primary.withOpacity(0.2),
            blurRadius: 20,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Text(
                'Resumo da consulta',
                style: theme.textTheme.titleMedium?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const Spacer(),
              if (origin != null && origin!.isNotEmpty)
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Text(
                    origin == 'another_base_estadual'
                        ? 'Outros estados'
                        : 'Base estadual',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
            ],
          ),
          if (generatedAt != null && generatedAt.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              'Gerado em $generatedAt',
              style: theme.textTheme.bodySmall?.copyWith(
                color: Colors.white70,
              ),
            ),
          ],
          const SizedBox(height: 16),
          Row(
            children: [
              buildTile('Placa', placa),
              const SizedBox(width: 12),
              buildTile(
                'Renavam',
                (renavam == null || renavam!.trim().isEmpty) ? '—' : renavam!,
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              buildTile('UF', uf),
              if (procedencia != null && procedencia.isNotEmpty) ...[
                const SizedBox(width: 12),
                buildTile('Procedência', procedencia),
              ],
            ],
          ),
        ],
      ),
    );
  }
}
