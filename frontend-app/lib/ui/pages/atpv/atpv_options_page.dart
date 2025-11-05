import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'package:frontend_app/services/atpv_service.dart';
import 'package:frontend_app/services/base_estadual_service.dart';
import 'package:frontend_app/ui/pages/atpv/atpv_form_page.dart';
import 'package:frontend_app/ui/widgets/app_error_dialog.dart';

class AtpvOptionsPage extends StatefulWidget {
  const AtpvOptionsPage({super.key});

  @override
  State<AtpvOptionsPage> createState() => _AtpvOptionsPageState();
}

class _AtpvOptionsPageState extends State<AtpvOptionsPage> {
  Future<void> _handleConsultation() async {
    final result = await showDialog<_AtpvConsultationResult>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const _ConsultIntencaoVendaDialog(),
    );

    if (result == null || !mounted) return;

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => AtpvFormPage(
          initialPlate: result.plate,
          initialRenavam: result.renavam,
          initialCaptchaValue: result.captcha,
          initialCaptchaBytes: result.captchaBytes,
          initialConsultaPayload: result.payload,
          initialConsultaComunicacoes: result.comunicacoes,
        ),
      ),
    );
  }

  void _handleForm() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => const AtpvFormPage(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Emissão da ATPV-e'),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Escolha como deseja prosseguir:',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
              ),
              const SizedBox(height: 24),
              FilledButton.icon(
                onPressed: _handleConsultation,
                icon: const Icon(Icons.search_outlined),
                label: const Text('Consultar intenção de venda'),
              ),
              const SizedBox(height: 12),
              OutlinedButton.icon(
                onPressed: _handleForm,
                icon: const Icon(Icons.description_outlined),
                label: const Text('Formulário de emissão da ATPV-e'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ConsultIntencaoVendaDialog extends StatefulWidget {
  const _ConsultIntencaoVendaDialog();

  @override
  State<_ConsultIntencaoVendaDialog> createState() =>
      _ConsultIntencaoVendaDialogState();
}

class _ConsultIntencaoVendaDialogState
    extends State<_ConsultIntencaoVendaDialog> {
  final _formKey = GlobalKey<FormState>();
  final _plateController = TextEditingController();
  final _renavamController = TextEditingController();
  final _captchaController = TextEditingController();

  final BaseEstadualService _baseEstadualService = BaseEstadualService();
  final AtpvService _atpvService = AtpvService();

  Uint8List? _captchaBytes;
  bool _loadingCaptcha = false;
  bool _submitting = false;
  String? _captchaError;

  @override
  void initState() {
    super.initState();
    _refreshCaptcha();
  }

  @override
  void dispose() {
    _plateController.dispose();
    _renavamController.dispose();
    _captchaController.dispose();
    super.dispose();
  }

  Future<void> _refreshCaptcha() async {
    setState(() {
      _loadingCaptcha = true;
      _captchaError = null;
      _captchaBytes = null;
    });

    try {
      final value = await _baseEstadualService.fetchCaptcha();
      Uint8List? bytes;
      try {
        bytes = base64Decode(value);
      } catch (_) {
        bytes = null;
      }
      if (bytes == null || bytes.isEmpty) {
        setState(() {
          _captchaError = 'Captcha recebido em formato inválido.';
        });
      } else {
        setState(() {
          _captchaBytes = bytes;
        });
      }
    } on BaseEstadualException catch (e) {
      setState(() {
        _captchaError = e.message;
      });
    } catch (_) {
      setState(() {
        _captchaError = 'Erro ao carregar captcha.';
      });
    } finally {
      setState(() {
        _loadingCaptcha = false;
      });
    }
  }

  Future<void> _submit() async {
    final form = _formKey.currentState;
    if (form == null) return;

    if (!form.validate()) {
      return;
    }

    setState(() {
      _submitting = true;
    });

    final placa = _plateController.text.trim().toUpperCase();
    final renavam = _renavamController.text.trim();
    final captcha = _captchaController.text.trim().toUpperCase();

    try {
      final result = await _atpvService.consultarIntencaoVenda(
        renavam: renavam,
        placa: placa,
        captcha: captcha,
      );

      final comunicacoes = (result['comunicacao_vendas'] is List)
          ? (result['comunicacao_vendas'] as List)
              .whereType<Map>()
              .map((item) => item.map(
                    (key, value) => MapEntry(key.toString(), value),
                  ))
              .toList(growable: false)
          : const <Map<String, dynamic>>[];

      if (!mounted) return;
      Navigator.of(context).pop(
        _AtpvConsultationResult(
          plate: placa,
          renavam: renavam,
          captcha: captcha,
          captchaBytes: _captchaBytes,
          payload: result,
          comunicacoes: comunicacoes,
        ),
      );
    } on AtpvException catch (e) {
      await AppErrorDialog.show(
        context,
        title: 'Ops, algo deu errado',
        message: e.message,
      );
    } catch (_) {
      await AppErrorDialog.show(
        context,
        title: 'Ops, algo deu errado',
        message: 'Falha ao consultar intenção de venda.',
      );
    } finally {
      if (mounted) {
        setState(() {
          _submitting = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    Widget captchaContent;
    if (_loadingCaptcha) {
      captchaContent = const SizedBox(
        height: 80,
        child: Center(child: CircularProgressIndicator()),
      );
    } else if (_captchaBytes != null) {
      captchaContent = ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: Image.memory(
          _captchaBytes!,
          height: 80,
          fit: BoxFit.cover,
        ),
      );
    } else {
      captchaContent = Container(
        height: 80,
        decoration: BoxDecoration(
          color: Colors.grey.shade200,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Center(
          child: Text(
            _captchaError ?? 'Captcha não carregado.',
            style: theme.textTheme.bodySmall?.copyWith(
              color: Colors.grey.shade600,
            ),
            textAlign: TextAlign.center,
          ),
        ),
      );
    }

    return AlertDialog(
      title: const Text('Consultar intenção de venda'),
      content: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextFormField(
                controller: _plateController,
                textCapitalization: TextCapitalization.characters,
                decoration: const InputDecoration(labelText: 'Placa'),
                inputFormatters: [
                  FilteringTextInputFormatter.allow(RegExp('[A-Za-z0-9]')),
                  LengthLimitingTextInputFormatter(7),
                ],
                validator: (value) {
                  final text = value?.trim() ?? '';
                  if (text.isEmpty) {
                    return 'Informe a placa.';
                  }
                  if (text.length < 7) {
                    return 'Placa inválida.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _renavamController,
                decoration: const InputDecoration(labelText: 'Renavam'),
                keyboardType: TextInputType.number,
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                  LengthLimitingTextInputFormatter(11),
                ],
                validator: (value) {
                  final text = value?.trim() ?? '';
                  if (text.isEmpty) {
                    return 'Informe o renavam.';
                  }
                  if (text.length < 9) {
                    return 'Renavam inválido.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              captchaContent,
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _captchaController,
                      textCapitalization: TextCapitalization.characters,
                      decoration: const InputDecoration(labelText: 'Captcha'),
                      validator: (value) {
                        final text = value?.trim() ?? '';
                        if (text.isEmpty) {
                          return 'Informe o captcha.';
                        }
                        return null;
                      },
                    ),
                  ),
                  const SizedBox(width: 12),
                  IconButton(
                    onPressed: _loadingCaptcha ? null : _refreshCaptcha,
                    tooltip: 'Recarregar captcha',
                    icon: const Icon(Icons.refresh),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                'O captcha diferencia maiúsculas e minúsculas.',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: Colors.grey.shade600,
                ),
              ),
            ],
          ),
        ),
      ),
      actions: [
        TextButton(
          onPressed: _submitting ? null : () => Navigator.of(context).pop(),
          child: const Text('Cancelar'),
        ),
        FilledButton(
          onPressed: _submitting ? null : _submit,
          child: _submitting
              ? const SizedBox(
                  height: 18,
                  width: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('Consultar'),
        ),
      ],
    );
  }
}

class _AtpvConsultationResult {
  const _AtpvConsultationResult({
    required this.plate,
    required this.renavam,
    required this.captcha,
    required this.captchaBytes,
    required this.payload,
    required this.comunicacoes,
  });

  final String plate;
  final String renavam;
  final String captcha;
  final Uint8List? captchaBytes;
  final Map<String, dynamic> payload;
  final List<Map<String, dynamic>> comunicacoes;
}
