import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import 'package:universal_io/io.dart' as io;

import 'package:frontend_app/services/atpv_service.dart';
import 'package:frontend_app/services/base_estadual_service.dart';

class AtpvFormPage extends StatefulWidget {
  const AtpvFormPage({
    super.key,
    this.initialPlate,
    this.initialRenavam,
  });

  final String? initialPlate;
  final String? initialRenavam;

  @override
  State<AtpvFormPage> createState() => _AtpvFormPageState();
}

class _AtpvFormPageState extends State<AtpvFormPage> {
  final _consultaFormKey = GlobalKey<FormState>();
  final _emissaoFormKey = GlobalKey<FormState>();

  late final TextEditingController _plateController;
  late final TextEditingController _renavamController;
  final TextEditingController _captchaController = TextEditingController();

  final TextEditingController _chassiController = TextEditingController();
  final TextEditingController _ownerDocumentController = TextEditingController();
  final TextEditingController _ownerEmailController = TextEditingController();
  final TextEditingController _saleValueController = TextEditingController();
  final TextEditingController _odometerController = TextEditingController();
  final TextEditingController _buyerDocumentController = TextEditingController();
  final TextEditingController _buyerNameController = TextEditingController();
  final TextEditingController _buyerEmailController = TextEditingController();
  final TextEditingController _buyerCepController = TextEditingController();
  final TextEditingController _buyerNumberController = TextEditingController();
  final TextEditingController _buyerComplementController = TextEditingController();
  final TextEditingController _buyerCityController = TextEditingController();
  final TextEditingController _buyerNeighborhoodController = TextEditingController();
  final TextEditingController _buyerStreetController = TextEditingController();
  final TextEditingController _buyerStateController = TextEditingController();

  final BaseEstadualService _baseEstadualService = BaseEstadualService();
  final AtpvService _atpvService = AtpvService();

  bool _termsAccepted = false;
  bool _loadingCaptcha = false;
  bool _isConsulting = false;
  bool _isSubmitting = false;
  Uint8List? _captchaBytes;
  String? _captchaError;
  String? _consultaFeedback;
  String? _submissionError;
  String? _lastCaptchaUsed;

  Map<String, dynamic>? _consultaPayload;
  List<Map<String, dynamic>> _consultaTables = const [];
  Map<String, dynamic>? _successResult;

  @override
  void initState() {
    super.initState();
    _plateController = TextEditingController(text: widget.initialPlate ?? '');
    _renavamController = TextEditingController(text: widget.initialRenavam ?? '');
    WidgetsBinding.instance.addPostFrameCallback((_) => _refreshCaptcha());
  }

  @override
  void dispose() {
    _plateController.dispose();
    _renavamController.dispose();
    _captchaController.dispose();
    _chassiController.dispose();
    _ownerDocumentController.dispose();
    _ownerEmailController.dispose();
    _saleValueController.dispose();
    _odometerController.dispose();
    _buyerDocumentController.dispose();
    _buyerNameController.dispose();
    _buyerEmailController.dispose();
    _buyerCepController.dispose();
    _buyerNumberController.dispose();
    _buyerComplementController.dispose();
    _buyerCityController.dispose();
    _buyerNeighborhoodController.dispose();
    _buyerStreetController.dispose();
    _buyerStateController.dispose();
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

  Future<void> _consultarIntencaoVenda() async {
    final form = _consultaFormKey.currentState;
    if (form == null) return;

    if (!form.validate()) {
      return;
    }

    final placa = _plateController.text.trim().toUpperCase();
    final renavam = _renavamController.text.trim();
    final captcha = _captchaController.text.trim().toUpperCase();

    FocusScope.of(context).unfocus();

    setState(() {
      _isConsulting = true;
      _consultaFeedback = null;
      _consultaPayload = null;
      _consultaTables = const [];
    });

    try {
      final result = await _atpvService.consultarIntencaoVenda(
        renavam: renavam,
        placa: placa,
        captcha: captcha,
      );

      final tables = result['tabelas'];

      setState(() {
        _consultaPayload = result;
        if (tables is List) {
          _consultaTables = tables
              .whereType<Map>()
              .map(
                (item) => item.map(
                  (key, dynamic value) => MapEntry(key.toString(), value),
                ),
              )
              .toList(growable: false);
        } else {
          _consultaTables = const [];
        }
        _consultaFeedback = 'Consulta realizada com sucesso.';
        _lastCaptchaUsed = captcha;
        _termsAccepted = false;
      });

      _prefillFromConsulta();
    } on AtpvException catch (e) {
      setState(() {
        _consultaFeedback = e.message;
      });
    } catch (_) {
      setState(() {
        _consultaFeedback = 'Falha ao consultar intenção de venda.';
      });
    } finally {
      setState(() {
        _isConsulting = false;
      });
    }
  }

  Future<void> _submitEmission() async {
    final form = _emissaoFormKey.currentState;
    if (form == null) return;

    if (!form.validate()) {
      return;
    }

    if (!_termsAccepted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Confirme que as informações estão corretas.'),
        ),
      );
      return;
    }

    FocusScope.of(context).unfocus();

    setState(() {
      _isSubmitting = true;
      _submissionError = null;
    });

    try {
      final result = await _atpvService.emitirAtpv(
        renavam: _renavamController.text.trim(),
        placa: _plateController.text.trim().toUpperCase(),
        captcha: _captchaController.text.trim().toUpperCase(),
        chassi: _chassiController.text.trim().isEmpty
            ? null
            : _chassiController.text.trim(),
        hodometro: _odometerController.text.trim().isEmpty
            ? null
            : _odometerController.text.trim(),
        emailProprietario: _ownerEmailController.text.trim().isEmpty
            ? null
            : _ownerEmailController.text.trim(),
        cpfCnpjProprietario: _ownerDocumentController.text.trim().isEmpty
            ? null
            : _ownerDocumentController.text.trim(),
        cpfCnpjComprador: _buyerDocumentController.text.trim(),
        nomeComprador: _buyerNameController.text.trim(),
        emailComprador: _buyerEmailController.text.trim().isEmpty
            ? null
            : _buyerEmailController.text.trim(),
        uf: _buyerStateController.text.trim().toUpperCase(),
        valorVenda: _saleValueController.text.trim().isEmpty
            ? null
            : _saleValueController.text.trim(),
        cepComprador: _buyerCepController.text.trim().isEmpty
            ? null
            : _buyerCepController.text.trim(),
        municipioComprador: _buyerCityController.text.trim().isEmpty
            ? null
            : _buyerCityController.text.trim(),
        bairroComprador: _buyerNeighborhoodController.text.trim().isEmpty
            ? null
            : _buyerNeighborhoodController.text.trim(),
        logradouroComprador: _buyerStreetController.text.trim().isEmpty
            ? null
            : _buyerStreetController.text.trim(),
        numeroComprador: _buyerNumberController.text.trim().isEmpty
            ? null
            : _buyerNumberController.text.trim(),
        complementoComprador: _buyerComplementController.text.trim().isEmpty
            ? null
            : _buyerComplementController.text.trim(),
      );

      setState(() {
        _successResult = result;
        _lastCaptchaUsed = _captchaController.text.trim();
      });

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('ATPV-e emitida com sucesso.')),
      );
    } on AtpvException catch (e) {
      setState(() {
        _submissionError = e.message;
      });
    } catch (_) {
      setState(() {
        _submissionError = 'Não foi possível emitir a ATPV-e.';
      });
    } finally {
      setState(() {
        _isSubmitting = false;
      });
    }
  }

  Future<void> _handleDownloadPdf() async {
    final placa = _plateController.text.trim().toUpperCase();
    final renavam = _renavamController.text.trim();
    final captcha = (_lastCaptchaUsed ?? _captchaController.text).trim();

    if (placa.isEmpty || renavam.isEmpty || captcha.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Informe placa, renavam e captcha para baixar o PDF.'),
        ),
      );
      return;
    }

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
        child: CircularProgressIndicator(),
      ),
    );

    try {
      final bytes = await _atpvService.baixarPdf(
        placa: placa,
        renavam: renavam,
        captcha: captcha.toUpperCase(),
      );

      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();

      final result = await _saveAndOpenPdf(bytes, placa);
      if (!mounted) return;

      if (result.opened) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('PDF da ATPV-e aberto com sucesso.'),
          ),
        );
      } else if (result.path != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'PDF salvo em ${result.path}. Abra manualmente.',
            ),
          ),
        );
      } else if (kIsWeb) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Download disponível no navegador. Verifique a pasta de downloads.',
            ),
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'PDF gerado. Abra manualmente pelo gerenciador de arquivos.',
            ),
          ),
        );
      }
    } on AtpvException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message)),
      );
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Não foi possível baixar o PDF da ATPV-e.'),
        ),
      );
    }
  }

  Future<({bool opened, String? path})> _saveAndOpenPdf(
    Uint8List pdfBytes,
    String plate,
  ) async {
    if (kIsWeb) {
      return (opened: false, path: null);
    }

    final directory = await getTemporaryDirectory();
    final sanitizedPlate =
        plate.replaceAll(RegExp(r'[^A-Za-z0-9]'), '').toUpperCase();
    final timestamp = DateTime.now().millisecondsSinceEpoch;
    final filename =
        'atpv_${sanitizedPlate.isEmpty ? 'veiculo' : sanitizedPlate}_$timestamp.pdf';
    final file = io.File('${directory.path}/$filename');

    await file.writeAsBytes(pdfBytes, flush: true);
    final openResult = await OpenFilex.open(file.path);

    final opened = openResult.type == ResultType.done;
    return (opened: opened, path: file.path);
  }

  void _prefillFromConsulta() {
    final payload = _consultaPayload;
    final tables = _consultaTables;

    if (payload != null) {
      final veiculo = payload['veiculo'];
      if (veiculo is Map<String, dynamic>) {
        _setIfEmpty(_plateController, veiculo['placa']?.toString());
        _setIfEmpty(_renavamController, veiculo['renavam']?.toString());
        _setIfEmpty(_chassiController, veiculo['chassi']?.toString());
      }

      final proprietario = payload['proprietario'];
      if (proprietario is Map<String, dynamic>) {
        final nome = proprietario['nome']?.toString();
        if (nome != null && nome.trim().isNotEmpty &&
            _ownerDocumentController.text.trim().isEmpty) {
          // Keep owner name in placeholder (not shown as field).
        }
      }
    }

    _setIfEmpty(
      _ownerDocumentController,
      _findValueInTables(tables, ['propriet', 'cpf']),
    );
    _setIfEmpty(
      _ownerEmailController,
      _findValueInTables(tables, ['propriet', 'email']),
    );
    _setIfEmpty(
      _saleValueController,
      _findValueInTables(tables, ['valor', 'venda']),
    );
    _setIfEmpty(
      _buyerDocumentController,
      _findValueInTables(tables, ['cpf', 'cnpj', 'comprador']),
    );
    _setIfEmpty(
      _buyerNameController,
      _findValueInTables(tables, ['nome', 'comprador']),
    );
    _setIfEmpty(
      _buyerEmailController,
      _findValueInTables(tables, ['email', 'comprador']),
    );
    _setIfEmpty(
      _buyerCepController,
      _findValueInTables(tables, ['cep']),
    );
    _setIfEmpty(
      _buyerCityController,
      _findValueInTables(tables, ['munic', 'cidade', 'comprador']),
    );
    _setIfEmpty(
      _buyerNeighborhoodController,
      _findValueInTables(tables, ['bairro']),
    );
    _setIfEmpty(
      _buyerStreetController,
      _findValueInTables(tables, ['logradouro', 'endereco']),
    );
    _setIfEmpty(
      _buyerNumberController,
      _findValueInTables(tables, ['numero']),
    );
    _setIfEmpty(
      _buyerComplementController,
      _findValueInTables(tables, ['complemento']),
    );
    final uf = _findValueInTables(tables, ['uf', 'estado']);
    if (uf != null && uf.trim().isNotEmpty && _buyerStateController.text.trim().isEmpty) {
      _buyerStateController.text = uf.trim().toUpperCase();
    }
  }

  void _setIfEmpty(TextEditingController controller, String? value) {
    if (value == null) return;
    final trimmed = value.trim();
    if (trimmed.isEmpty) return;
    if (controller.text.trim().isEmpty) {
      controller.text = trimmed;
    }
  }

  String? _findValueInTables(
    List<Map<String, dynamic>> tables,
    List<String> keywords,
  ) {
    for (final table in tables) {
      final rows = table['rows'];
      if (rows is! List) continue;
      for (final row in rows) {
        if (row is! Map) continue;
        final rawLabel = row['label']?.toString().toLowerCase() ?? '';
        final rawValue = row['value']?.toString();
        if (rawLabel.isEmpty || rawValue == null) continue;
        for (final keyword in keywords) {
          if (rawLabel.contains(keyword.toLowerCase())) {
            final trimmed = rawValue.trim();
            if (trimmed.isNotEmpty) return trimmed;
          }
        }
      }
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    final success = _successResult;

    return Scaffold(
      appBar: AppBar(
        title: Text(success == null ? 'Emissão da ATPV-e' : 'ATPV-e emitida'),
        actions: [
          IconButton(
            onPressed: _refreshCaptcha,
            tooltip: 'Atualizar captcha',
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: SafeArea(
        child: success != null ? _buildSuccessBody(context) : _buildFormBody(context),
      ),
    );
  }

  Widget _buildFormBody(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _buildSection(
            title: 'Consulta de intenção de venda',
            child: Form(
              key: _consultaFormKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _buildTextField(
                    label: 'Placa',
                    controller: _plateController,
                    textCapitalization: TextCapitalization.characters,
                    inputFormatters: [
                      const UpperCaseTextFormatter(),
                      FilteringTextInputFormatter.allow(RegExp('[A-Za-z0-9]')),
                      LengthLimitingTextInputFormatter(7),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _buildTextField(
                    label: 'Renavam',
                    controller: _renavamController,
                    keyboardType: TextInputType.number,
                    inputFormatters: [
                      FilteringTextInputFormatter.digitsOnly,
                      LengthLimitingTextInputFormatter(11),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _buildCaptchaSection(),
                  const SizedBox(height: 16),
                  FilledButton.icon(
                    onPressed: _isConsulting ? null : _consultarIntencaoVenda,
                    icon: _isConsulting
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.search),
                    label: Text(_isConsulting ? 'Consultando...' : 'Consultar intenção de venda'),
                  ),
                  if (_consultaFeedback != null) ...[
                    const SizedBox(height: 12),
                    Text(
                      _consultaFeedback!,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: (_consultaPayload != null && _consultaFeedback!.contains('sucesso'))
                                ? Colors.green.shade700
                                : Colors.red.shade700,
                          ),
                    ),
                  ],
                ],
              ),
            ),
          ),
          if (_consultaPayload != null || _consultaTables.isNotEmpty) ...[
            const SizedBox(height: 24),
            _buildConsultaResultSection(),
          ],
          if (_consultaPayload != null) ...[
            const SizedBox(height: 24),
            _buildEmissionFormSection(context),
          ],
        ],
      ),
    );
  }

  Widget _buildEmissionFormSection(BuildContext context) {
    return _buildSection(
      title: 'Dados para emissão da ATPV-e',
      child: Form(
        key: _emissaoFormKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildTextField(
              label: 'Chassi',
              controller: _chassiController,
              requiredField: false,
            ),
            const SizedBox(height: 16),
            _buildTextField(
              label: 'CPF/CNPJ do proprietário atual',
              controller: _ownerDocumentController,
              requiredField: false,
            ),
            const SizedBox(height: 16),
            _buildTextField(
              label: 'E-mail do proprietário atual',
              controller: _ownerEmailController,
              keyboardType: TextInputType.emailAddress,
              requiredField: false,
            ),
            const SizedBox(height: 16),
            _buildTextField(
              label: 'Valor da venda',
              controller: _saleValueController,
              keyboardType: TextInputType.number,
              requiredField: false,
            ),
            const SizedBox(height: 16),
            _buildTextField(
              label: 'Hodômetro',
              controller: _odometerController,
              keyboardType: TextInputType.number,
              requiredField: false,
            ),
            const SizedBox(height: 16),
            _buildTextField(
              label: 'CPF/CNPJ do comprador',
              controller: _buyerDocumentController,
              validator: (value) {
                final text = value?.trim() ?? '';
                if (text.isEmpty) {
                  return 'Informe o CPF ou CNPJ do comprador.';
                }
                if (text.length < 11) {
                  return 'Documento do comprador inválido.';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            _buildTextField(
              label: 'Nome completo do comprador',
              controller: _buyerNameController,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'Informe o nome do comprador.';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            _buildTextField(
              label: 'E-mail do comprador',
              controller: _buyerEmailController,
              keyboardType: TextInputType.emailAddress,
              requiredField: false,
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _buildTextField(
                    label: 'CEP',
                    controller: _buyerCepController,
                    keyboardType: TextInputType.number,
                    requiredField: false,
                    inputFormatters: [
                      FilteringTextInputFormatter.allow(RegExp('[0-9]')),
                      LengthLimitingTextInputFormatter(8),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildTextField(
                    label: 'Número',
                    controller: _buyerNumberController,
                    keyboardType: TextInputType.number,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            _buildTextField(
              label: 'Complemento',
              controller: _buyerComplementController,
              requiredField: false,
            ),
            const SizedBox(height: 16),
            _buildTextField(
              label: 'Município',
              controller: _buyerCityController,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'Informe o município do comprador.';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            _buildTextField(
              label: 'Bairro',
              controller: _buyerNeighborhoodController,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'Informe o bairro.';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            _buildTextField(
              label: 'Logradouro',
              controller: _buyerStreetController,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'Informe o logradouro.';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            _buildTextField(
              label: 'UF',
              controller: _buyerStateController,
              textCapitalization: TextCapitalization.characters,
              inputFormatters: [
                const UpperCaseTextFormatter(),
                LengthLimitingTextInputFormatter(2),
              ],
              validator: (value) {
                final text = value?.trim().toUpperCase() ?? '';
                if (text.length != 2) {
                  return 'Informe a UF com duas letras.';
                }
                return null;
              },
            ),
            const SizedBox(height: 24),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Confirmo que as informações estão corretas.'),
              value: _termsAccepted,
              onChanged: (value) => setState(() {
                _termsAccepted = value ?? false;
              }),
            ),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: _isSubmitting ? null : _submitEmission,
              child: _isSubmitting
                  ? const SizedBox(
                      height: 18,
                      width: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Emitir ATPV-e'),
            ),
            if (_submissionError != null) ...[
              const SizedBox(height: 12),
              Text(
                _submissionError!,
                style: Theme.of(context)
                    .textTheme
                    .bodyMedium
                    ?.copyWith(color: Colors.red.shade700),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildSuccessBody(BuildContext context) {
    final payload = _successResult?['payload'];
    final veiculo = payload is Map<String, dynamic> ? payload['parsed'] ?? payload['veiculo'] : null;

    String? placa;
    String? renavam;
    if (veiculo is Map<String, dynamic>) {
      placa = veiculo['placa']?.toString();
      renavam = veiculo['renavam']?.toString();
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const SizedBox(height: 24),
          Icon(
            Icons.check_circle_outline,
            color: Theme.of(context).colorScheme.primary,
            size: 84,
          ),
          const SizedBox(height: 16),
          Text(
            'ATPV-e emitida com sucesso!',
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
          ),
          const SizedBox(height: 12),
          Text(
            'Faça o download do PDF para concluir o processo junto ao Detran.',
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Colors.grey.shade700,
                ),
          ),
          const SizedBox(height: 24),
          if (placa != null || renavam != null) ...[
            Card(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (placa != null)
                      _buildSummaryRow(
                        context,
                        label: 'Placa',
                        value: placa.toUpperCase(),
                      ),
                    if (renavam != null) ...[
                      const SizedBox(height: 12),
                      _buildSummaryRow(
                        context,
                        label: 'Renavam',
                        value: renavam,
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),
          ],
          FilledButton.icon(
            onPressed: _handleDownloadPdf,
            icon: const Icon(Icons.picture_as_pdf_outlined),
            label: const Text('Baixar PDF da ATPV-e'),
          ),
          const SizedBox(height: 12),
          TextButton(
            onPressed: () {
              setState(() {
                _successResult = null;
                _termsAccepted = false;
                _submissionError = null;
              });
              _refreshCaptcha();
            },
            child: const Text('Emitir nova ATPV-e'),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryRow(
    BuildContext context, {
    required String label,
    required String value,
  }) {
    final theme = Theme.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            color: Colors.grey.shade700,
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

  Widget _buildSection({
    required String title,
    required Widget child,
  }) {
    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
      ),
      elevation: 1,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              title,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
            ),
            const SizedBox(height: 16),
            child,
          ],
        ),
      ),
    );
  }

  Widget _buildTextField({
    required String label,
    required TextEditingController controller,
    TextInputType? keyboardType,
    List<TextInputFormatter>? inputFormatters,
    TextCapitalization textCapitalization = TextCapitalization.none,
    bool readOnly = false,
    bool requiredField = true,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      readOnly: readOnly,
      textCapitalization: textCapitalization,
      keyboardType: keyboardType,
      inputFormatters: inputFormatters,
      decoration: InputDecoration(
        labelText: label,
      ),
      validator: validator ??
          (value) {
            final text = value?.trim() ?? '';
            if (requiredField && text.isEmpty) {
              return 'Informe $label'.replaceAll('Informe Informe', 'Informe');
            }
            return null;
          },
    );
  }

  Widget _buildCaptchaSection() {
    final theme = Theme.of(context);

    Widget captchaContent;
    if (_loadingCaptcha) {
      captchaContent = const SizedBox(
        height: 80,
        child: Center(
          child: CircularProgressIndicator(),
        ),
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
          ),
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        captchaContent,
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildTextField(
                label: 'Captcha',
                controller: _captchaController,
                textCapitalization: TextCapitalization.characters,
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
          'O captcha diferencia maiúsculas e minúsculas. Recarregue caso não esteja legível.',
          style: theme.textTheme.bodySmall?.copyWith(
            color: Colors.grey.shade600,
          ),
        ),
        if (_captchaError != null) ...[
          const SizedBox(height: 8),
          Text(
            _captchaError!,
            style: theme.textTheme.bodySmall?.copyWith(
              color: Colors.red.shade700,
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildConsultaResultSection() {
    final theme = Theme.of(context);

    final veiculo = _consultaPayload?['veiculo'] as Map<String, dynamic>?;
    final proprietario = _consultaPayload?['proprietario'] as Map<String, dynamic>?;

    final displayEntries = <MapEntry<String, String>>[];
    if (veiculo != null) {
      final placa = veiculo['placa']?.toString();
      final renavam = veiculo['renavam']?.toString();
      final chassi = veiculo['chassi']?.toString();
      if (placa != null && placa.trim().isNotEmpty) {
        displayEntries.add(MapEntry('Placa', placa.trim()));
      }
      if (renavam != null && renavam.trim().isNotEmpty) {
        displayEntries.add(MapEntry('Renavam', renavam.trim()));
      }
      if (chassi != null && chassi.trim().isNotEmpty) {
        displayEntries.add(MapEntry('Chassi', chassi.trim()));
      }
    }
    if (proprietario != null) {
      final nome = proprietario['nome']?.toString();
      if (nome != null && nome.trim().isNotEmpty) {
        displayEntries.add(MapEntry('Proprietário', nome.trim()));
      }
    }

    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      elevation: 0,
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              'Resultado da consulta',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 16),
            if (displayEntries.isNotEmpty)
              Column(
                children: displayEntries
                    .map(
                      (entry) => Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: _buildSummaryRow(
                          context,
                          label: entry.key,
                          value: entry.value,
                        ),
                      ),
                    )
                    .toList(),
              ),
            if (_consultaTables.isNotEmpty) ...[
              const SizedBox(height: 12),
              ExpansionTile(
                tilePadding: EdgeInsets.zero,
                title: const Text('Detalhes completos'),
                children: _consultaTables.map((table) {
                  final rows = table['rows'];
                  if (rows is! List) {
                    return const SizedBox.shrink();
                  }
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: rows.whereType<Map>().map((row) {
                      final label = row['label']?.toString() ?? '';
                      final value = row['value']?.toString() ?? '';
                      if (label.isEmpty && value.isEmpty) {
                        return const SizedBox.shrink();
                      }
                      return Padding(
                        padding: const EdgeInsets.symmetric(vertical: 6),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(
                              flex: 2,
                              child: Text(
                                label,
                                style: theme.textTheme.bodySmall?.copyWith(
                                  color: Colors.grey.shade600,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              flex: 3,
                              child: Text(
                                value.trim(),
                                style: theme.textTheme.bodyMedium,
                              ),
                            ),
                          ],
                        ),
                      );
                    }).toList(),
                  );
                }).toList(),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class UpperCaseTextFormatter extends TextInputFormatter {
  const UpperCaseTextFormatter();

  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    return newValue.copyWith(
      text: newValue.text.toUpperCase(),
      selection: newValue.selection,
    );
  }
}
