import 'dart:convert';

import 'package:flutter/material.dart';

class EcrvProcessPage extends StatelessWidget {
  const EcrvProcessPage({
    super.key,
    required this.placa,
    required this.numeroFicha,
    required this.anoFicha,
    required this.fichaPayload,
    required this.andamentoPayload,
  });

  final String placa;
  final String numeroFicha;
  final String anoFicha;
  final Map<String, dynamic> fichaPayload;
  final Map<String, dynamic> andamentoPayload;

  @override
  Widget build(BuildContext context) {
    final fichaNormalized =
        _asMap(_asMap(fichaPayload['normalized'])?['dados_da_ficha_cadastral']);
    final andamentoNormalized =
        _asMap(andamentoPayload['normalized']) ?? const {};
    final andamentoInfo =
        _asMap(andamentoNormalized['andamento_do_processo']) ?? const {};
    final datasInfo = _asMap(andamentoNormalized['datas']) ?? const {};
    final anexosInfo = _asMap(andamentoNormalized['documentos_anexos']);

    final status = _nonEmptyString(
          andamentoInfo['status_registro'],
        ) ??
        _nonEmptyString(andamentoInfo['status']) ??
        '—';
    final retorno = _nonEmptyString(andamentoInfo['retorno_consistencia']);

    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        title: const Text('Andamento do processo e-CRV'),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _ResumoCard(
                placa: placa,
                numeroFicha: numeroFicha,
                anoFicha: anoFicha,
                fichaData: fichaNormalized,
                status: status,
                retornoConsistencia: retorno,
              ),
              const SizedBox(height: 20),
              if (andamentoInfo.isNotEmpty)
                _InfoSection(
                  title: 'Andamento do processo',
                  entries: andamentoInfo,
                ),
              if (datasInfo.isNotEmpty) ...[
                const SizedBox(height: 16),
                _InfoSection(title: 'Datas', entries: datasInfo),
              ],
              if (anexosInfo != null && anexosInfo.isNotEmpty) ...[
                const SizedBox(height: 16),
                _InfoSection(title: 'Documentos anexos', entries: anexosInfo),
              ],
              const SizedBox(height: 24),
              _JsonViewer(
                title: 'Resposta da ficha cadastral',
                data: fichaPayload,
              ),
              const SizedBox(height: 16),
              _JsonViewer(
                title: 'Resposta do andamento do processo',
                data: andamentoPayload,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ResumoCard extends StatelessWidget {
  const _ResumoCard({
    required this.placa,
    required this.numeroFicha,
    required this.anoFicha,
    required this.fichaData,
    required this.status,
    required this.retornoConsistencia,
  });

  final String placa;
  final String numeroFicha;
  final String anoFicha;
  final Map<String, dynamic>? fichaData;
  final String status;
  final String? retornoConsistencia;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    String? _value(String key) {
      final value = fichaData?[key];
      if (value == null) return null;
      final text = value.toString().trim();
      if (text.isEmpty) return null;
      if (RegExp(r'^-+$').hasMatch(text)) return null;
      return text;
    }

    Widget buildTile(String label, String? value) {
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
                value ?? '—',
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
            color: colorScheme.primary.withOpacity(0.22),
            blurRadius: 20,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Resumo da consulta',
            style: theme.textTheme.titleMedium?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              SizedBox(
                width: double.infinity,
                child: buildTile(
                  'Placa',
                  placa,
                ),
              ),
              SizedBox(
                width: double.infinity,
                child: buildTile(
                  'Número / Ano da ficha',
                  '$numeroFicha / $anoFicha',
                ),
              ),
              if (_value('renavam') != null)
                SizedBox(
                  width: double.infinity,
                  child: buildTile('Renavam', _value('renavam')),
                ),
              if (_value('chassi') != null)
                SizedBox(
                  width: double.infinity,
                  child: buildTile('Chassi', _value('chassi')),
                ),
              if (_value('municipio') != null)
                SizedBox(
                  width: double.infinity,
                  child: buildTile('Município', _value('municipio')),
                ),
            ],
          ),
          const SizedBox(height: 20),
          Container(
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.1),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.white.withOpacity(0.12)),
            ),
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Status do registro',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: Colors.white70,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  status,
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (retornoConsistencia != null &&
                    retornoConsistencia!.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(
                    retornoConsistencia!,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: Colors.white70,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoSection extends StatelessWidget {
  const _InfoSection({
    required this.title,
    required this.entries,
  });

  final String title;
  final Map<String, dynamic> entries;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final orderedEntries = entries.entries
        .map(
          (entry) => MapEntry(
            _formatLabel(entry.key),
            _formatValue(entry.value),
          ),
        )
        .where((entry) => entry.value.isNotEmpty)
        .toList();

    if (orderedEntries.isEmpty) {
      return const SizedBox.shrink();
    }

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: const [
          BoxShadow(
            color: Color(0x0F101828),
            blurRadius: 12,
            offset: Offset(0, 8),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(18, 18, 18, 22),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 16),
          for (final entry in orderedEntries) ...[
            Text(
              entry.key,
              style: theme.textTheme.bodySmall?.copyWith(
                color: const Color(0xFF667085),
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              entry.value,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: const Color(0xFF1D2939),
                fontWeight: FontWeight.w600,
              ),
            ),
            if (entry != orderedEntries.last) ...[
              const SizedBox(height: 12),
              const Divider(color: Color(0xFFE4E7EC)),
              const SizedBox(height: 12),
            ],
          ],
        ],
      ),
    );
  }

  String _formatLabel(String value) {
    final normalized = value.replaceAll('_', ' ').trim();
    if (normalized.isEmpty) return value;
    return normalized[0].toUpperCase() + normalized.substring(1);
  }

  String _formatValue(dynamic value) {
    if (value == null) return '';
    if (value is String) {
      final text = value.trim();
      if (text.isEmpty) return '';
      if (RegExp(r'^-+$').hasMatch(text)) return '';
      return text;
    }
    return value.toString();
  }
}

class _JsonViewer extends StatelessWidget {
  const _JsonViewer({
    required this.title,
    required this.data,
  });

  final String title;
  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final formatted = const JsonEncoder.withIndent('  ').convert(data);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          title,
          style: theme.textTheme.titleMedium?.copyWith(
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
            formatted,
            style: theme.textTheme.bodySmall?.copyWith(
              fontFamily: 'monospace',
              height: 1.4,
            ),
          ),
        ),
      ],
    );
  }
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
