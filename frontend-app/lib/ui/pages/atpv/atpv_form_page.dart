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
import 'package:frontend_app/services/cep_service.dart';
import 'package:frontend_app/ui/widgets/app_error_dialog.dart';

class AtpvFormPage extends StatefulWidget {
  const AtpvFormPage({
    super.key,
    this.initialPlate,
    this.initialRenavam,
    this.initialCaptchaValue,
    this.initialCaptchaBytes,
    this.initialConsultaPayload,
    this.initialConsultaComunicacoes,
  });

  final String? initialPlate;
  final String? initialRenavam;
  final String? initialCaptchaValue;
  final Uint8List? initialCaptchaBytes;
  final Map<String, dynamic>? initialConsultaPayload;
  final List<Map<String, dynamic>>? initialConsultaComunicacoes;

  @override
  State<AtpvFormPage> createState() => _AtpvFormPageState();
}

class _AtpvFormPageState extends State<AtpvFormPage> {
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
  final CepService _cepService = CepService();

  bool _termsAccepted = false;
  bool _loadingCaptcha = false;
  bool _isSubmitting = false;
  bool _isFetchingCep = false;
  Uint8List? _captchaBytes;
  String? _captchaError;
  String? _submissionError;
  String? _lastCaptchaUsed;
  String? _buyerMunicipioCode;
  Map<String, dynamic>? _signatureResponse;
  bool? _assinaturaDigital;

  Map<String, dynamic>? _consultaPayload;
  List<Map<String, dynamic>> _consultaComunicacoes = const [];
  Map<String, dynamic>? _successResult;

  @override
  void initState() {
    super.initState();
    _plateController = TextEditingController(text: widget.initialPlate ?? '');
    _renavamController = TextEditingController(text: widget.initialRenavam ?? '');
    if (widget.initialCaptchaValue != null &&
        widget.initialCaptchaValue!.trim().isNotEmpty) {
      _captchaController.text = widget.initialCaptchaValue!.trim();
      _lastCaptchaUsed = widget.initialCaptchaValue!.trim();
    }
    _captchaBytes = widget.initialCaptchaBytes;
    _consultaPayload = widget.initialConsultaPayload;
    _consultaComunicacoes =
        widget.initialConsultaComunicacoes ?? const <Map<String, dynamic>>[];
    if (_consultaPayload != null) {
      _prefillFromConsulta();
    }
    if (_captchaBytes == null) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _refreshCaptcha());
    }
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

  Future<void> _lookupCep() async {
    final rawCep = _buyerCepController.text.trim();
    final sanitized = rawCep.replaceAll(RegExp(r'[^0-9]'), '');

    if (sanitized.length != 8) {
      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          const SnackBar(content: Text('Digite um CEP válido com 8 dígitos.')),
        );
      return;
    }

    setState(() {
      _isFetchingCep = true;
    });

    try {
      final address = await _cepService.lookup(sanitized);

      setState(() {
        if ((address.street ?? '').isNotEmpty) {
          _buyerStreetController.text = address.street!;
        }
        if ((address.neighborhood ?? '').isNotEmpty) {
          _buyerNeighborhoodController.text = address.neighborhood!;
        }
        if ((address.city ?? '').isNotEmpty) {
          _buyerCityController.text = address.city!;
        }
        if ((address.state ?? '').isNotEmpty) {
          _buyerStateController.text = address.state!;
        }
        if ((address.complement ?? '').isNotEmpty) {
          _buyerComplementController.text = address.complement!;
        }
        _buyerMunicipioCode = address.code;
      });

      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          const SnackBar(
            content: Text('Endereço preenchido automaticamente. Confira os dados.'),
          ),
        );
    } on CepException catch (e) {
      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          SnackBar(content: Text(e.message)),
        );
    } catch (_) {
      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          const SnackBar(
            content: Text('Não foi possível consultar o CEP. Tente novamente.'),
          ),
        );
    } finally {
      if (mounted) {
        setState(() {
          _isFetchingCep = false;
        });
      }
    }
  }

  Widget? _buildCepSuffixIcon() {
    if (_isFetchingCep) {
      return Padding(
        padding: const EdgeInsets.only(right: 8.0),
        child: SizedBox(
          height: 18,
          width: 18,
          child: const CircularProgressIndicator(strokeWidth: 2),
        ),
      );
    }

    return IconButton(
      onPressed: _isFetchingCep ? null : _lookupCep,
      tooltip: 'Buscar endereço pelo CEP',
      icon: const Icon(Icons.search),
    );
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
      _signatureResponse = null;
      _assinaturaDigital = null;
      _successResult = null;
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
        municipioCodigoComprador: _buyerMunicipioCode,
      );

      if (!mounted) return;

      setState(() {
        _isSubmitting = false;
        _lastCaptchaUsed = _captchaController.text.trim();
      });

      final registroId = _parseRegistroId(result['registro_id']);

      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          const SnackBar(
            content: Text('Dados enviados com sucesso. Vamos definir o tipo de assinatura.'),
          ),
        );

      if (registroId == null) {
        setState(() {
          _successResult = result;
        });
        return;
      }

      final assinaturaDigital = await _showSignatureDialog();
      if (assinaturaDigital == null) {
        ScaffoldMessenger.of(context)
          ..clearSnackBars()
          ..showSnackBar(
            const SnackBar(content: Text('Assinatura não registrada.')),
          );
        return;
      }

      await _registrarAssinatura(
        registroId: registroId,
        assinaturaDigital: assinaturaDigital,
        emissionResult: result,
      );
    } on AtpvException catch (e) {
      setState(() {
        _submissionError = e.message;
      });
      _showErrorAlert(e.message);
    } catch (_) {
      setState(() {
        _submissionError = 'Não foi possível emitir a ATPV-e.';
      });
      _showErrorAlert('Não foi possível emitir a ATPV-e.');
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
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

    var dialogOpened = false;

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
        child: CircularProgressIndicator(),
      ),
    );
    dialogOpened = true;

    try {
      final bytes = await _atpvService.baixarPdf(
        placa: placa,
        renavam: renavam,
        captcha: captcha.toUpperCase(),
      );

      if (!mounted) return;
      if (dialogOpened) {
        Navigator.of(context, rootNavigator: true).pop();
      }

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
      if (dialogOpened) {
        Navigator.of(context, rootNavigator: true).pop();
      }
      await _showErrorAlert(e.message);
    } catch (_) {
      if (!mounted) return;
      if (dialogOpened) {
        Navigator.of(context, rootNavigator: true).pop();
      }
      await _showErrorAlert('Não foi possível baixar o PDF da ATPV-e.');
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
            _buyerNameController.text.trim().isEmpty) {
          _buyerNameController.text = nome.trim();
        }
      }
    }

    if (_consultaComunicacoes.isNotEmpty) {
      final primeiraComunicacao = _consultaComunicacoes.first;
      final comprador = primeiraComunicacao['comprador'];
      final intencao = primeiraComunicacao['intencao'];

      if (comprador is Map<String, dynamic>) {
        _setIfEmpty(_buyerDocumentController, comprador['documento']?.toString());
        _setIfEmpty(_buyerNameController, comprador['nome']?.toString());
        _setIfEmpty(_buyerEmailController, comprador['email']?.toString());
        _setIfEmpty(_buyerCepController, comprador['cep']?.toString());
        _setIfEmpty(_buyerCityController, comprador['municipio']?.toString());
        _setIfEmpty(_buyerNeighborhoodController, comprador['bairro']?.toString());
        _setIfEmpty(_buyerStreetController, comprador['logradouro']?.toString());
        _setIfEmpty(_buyerNumberController, comprador['numero']?.toString());
        _setIfEmpty(_buyerComplementController, comprador['complemento']?.toString());
      }

      if (intencao is Map<String, dynamic>) {
        final uf = intencao['uf']?.toString();
        if (uf != null && uf.trim().isNotEmpty &&
            _buyerStateController.text.trim().isEmpty) {
          _buyerStateController.text = uf.trim().toUpperCase();
        }
        _setIfEmpty(_saleValueController, intencao['valor_venda']?.toString());
      }
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
          if (_consultaPayload != null) ...[
            _buildConsultaResultSection(),
            const SizedBox(height: 24),
          ],
          _buildEmissionFormSection(context),
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
                    suffixIcon: _buildCepSuffixIcon(),
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
            _buildCaptchaSection(),
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
          if (_assinaturaDigital != null || (_signatureResponse?['message'] != null)) ...[
            const SizedBox(height: 16),
            if (_assinaturaDigital != null)
              Text(
                _assinaturaDigital == true
                    ? 'A assinatura digital foi solicitada.'
                    : 'A assinatura digital ficará pendente por enquanto.',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            if ((_signatureResponse?['message']?.toString().trim().isNotEmpty ?? false))
              Padding(
                padding: const EdgeInsets.only(top: 12),
                child: Text(
                  _signatureResponse!['message'].toString(),
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Colors.grey.shade600,
                      ),
                ),
              ),
          ],
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
                _signatureResponse = null;
                _assinaturaDigital = null;
              });
              _refreshCaptcha();
            },
            child: const Text('Emitir nova ATPV-e'),
          ),
        ],
      ),
    );
  }

  Future<void> _showErrorAlert(String message) async {
    if (!mounted) return;
    await AppErrorDialog.show(
      context,
      message: message,
      title: 'Ops, algo deu errado',
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

  Widget _buildCommunicationCard(Map<String, dynamic> data) {
    final theme = Theme.of(context);
    final comprador = data['comprador'] as Map<String, dynamic>? ?? const {};
    final intencao = data['intencao'] as Map<String, dynamic>? ?? const {};

    final entriesComprador = <MapEntry<String, String>>[];
    void addComprador(String label, dynamic value) {
      final text = value?.toString().trim();
      if (text != null && text.isNotEmpty) {
        entriesComprador.add(MapEntry(label, text));
      }
    }

    addComprador('CPF/CNPJ', comprador['documento']);
    addComprador('Nome', comprador['nome']);
    addComprador('E-mail', comprador['email']);
    addComprador('UF', comprador['uf']);
    addComprador('Município', comprador['municipio']);
    addComprador('Bairro', comprador['bairro']);
    addComprador('Logradouro', comprador['logradouro']);
    addComprador('Número', comprador['numero']);
    addComprador('Complemento', comprador['complemento']);

    final entriesIntencao = <MapEntry<String, String>>[];
    void addIntencao(String label, dynamic value) {
      final text = value?.toString().trim();
      if (text != null && text.isNotEmpty) {
        entriesIntencao.add(MapEntry(label, text));
      }
    }

    addIntencao('UF', intencao['uf']);
    addIntencao('Estado da intenção', intencao['estado']);
    addIntencao('Data/Hora', intencao['data_hora']);
    addIntencao('Data/Hora atualização', intencao['data_hora_atualizacao']);
    addIntencao('Valor da venda', intencao['valor_venda']);

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey.shade300),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Dados do comprador',
            style: theme.textTheme.bodyMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          if (entriesComprador.isNotEmpty)
            ...entriesComprador.map(
              (entry) => Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: _buildSummaryRow(
                  context,
                  label: entry.key,
                  value: entry.value,
                ),
              ),
            )
          else
            Text(
              'Nenhum dado disponível.',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: Colors.grey.shade600,
              ),
            ),
          const SizedBox(height: 16),
          Text(
            'Dados da intenção de venda',
            style: theme.textTheme.bodyMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          if (entriesIntencao.isNotEmpty)
            ...entriesIntencao.map(
              (entry) => Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: _buildSummaryRow(
                  context,
                  label: entry.key,
                  value: entry.value,
                ),
              ),
            )
          else
            Text(
              'Nenhum dado disponível.',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: Colors.grey.shade600,
              ),
            ),
        ],
      ),
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

  int? _parseRegistroId(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String) return int.tryParse(value);
    return null;
  }

  Future<bool?> _showSignatureDialog() async {
    bool selected = false;

    return showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return StatefulBuilder(
          builder: (context, setStateDialog) {
            return AlertDialog(
              title: const Text('Deseja assinar digitalmente a ATPVe?'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  RadioListTile<bool>(
                    title: const Text('Sim, desejo assinar digitalmente'),
                    value: true,
                    groupValue: selected,
                    onChanged: (value) {
                      if (value == null) return;
                      setStateDialog(() {
                        selected = value;
                      });
                    },
                  ),
                  RadioListTile<bool>(
                    title: const Text('Não, vou assinar posteriormente'),
                    value: false,
                    groupValue: selected,
                    onChanged: (value) {
                      if (value == null) return;
                      setStateDialog(() {
                        selected = value;
                      });
                    },
                  ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(dialogContext).pop(null),
                  child: const Text('Cancelar'),
                ),
                FilledButton(
                  onPressed: () => Navigator.of(dialogContext).pop(selected),
                  child: const Text('Salvar'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  Future<void> _registrarAssinatura({
    required int registroId,
    required bool assinaturaDigital,
    required Map<String, dynamic> emissionResult,
  }) async {
    setState(() {
      _isSubmitting = true;
      _submissionError = null;
    });

    try {
      final response = await _atpvService.registrarTipoAssinatura(
        registroId: registroId,
        assinaturaDigital: assinaturaDigital,
      );

      if (!mounted) return;

      final updatedResult = Map<String, dynamic>.from(emissionResult)
        ..['status'] = 'completed'
        ..['assinatura_digital'] = assinaturaDigital
        ..['assinatura'] = response;

      setState(() {
        _isSubmitting = false;
        _successResult = updatedResult;
        _signatureResponse = response;
        _assinaturaDigital = assinaturaDigital;
      });

      final message = response['message']?.toString().trim();
      if (message != null && message.isNotEmpty) {
        ScaffoldMessenger.of(context)
          ..clearSnackBars()
          ..showSnackBar(SnackBar(content: Text(message)));
      } else {
        ScaffoldMessenger.of(context)
          ..clearSnackBars()
          ..showSnackBar(
            SnackBar(
              content: Text(
                assinaturaDigital
                    ? 'Assinatura digital registrada com sucesso.'
                    : 'Preferência salva. Você pode assinar posteriormente.',
              ),
            ),
          );
      }
    } on AtpvException catch (e) {
      setState(() {
        _submissionError = e.message;
      });
      await _showErrorAlert(e.message);
    } catch (_) {
      const fallbackMessage = 'Não foi possível registrar a assinatura. Tente novamente.';
      setState(() {
        _submissionError = fallbackMessage;
      });
      await _showErrorAlert(fallbackMessage);
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
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
    Widget? suffixIcon,
  }) {
    return TextFormField(
      controller: controller,
      readOnly: readOnly,
      textCapitalization: textCapitalization,
      keyboardType: keyboardType,
      inputFormatters: inputFormatters,
      decoration: InputDecoration(
        labelText: label,
        suffixIcon: suffixIcon,
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
    final consultaInfo = _consultaPayload?['consulta'] as Map<String, dynamic>?
        ?? const <String, dynamic>{};

    final displayEntries = <MapEntry<String, String>>[];
    final consultaPlaca = consultaInfo['placa']?.toString();
    final consultaRenavam = consultaInfo['renavam']?.toString();
    if (consultaPlaca != null && consultaPlaca.trim().isNotEmpty) {
      displayEntries.add(MapEntry('Placa informada', consultaPlaca.trim()));
    }
    if (consultaRenavam != null && consultaRenavam.trim().isNotEmpty) {
      displayEntries.add(MapEntry('Renavam informado', consultaRenavam.trim()));
    }

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
            const SizedBox(height: 16),
            if (_consultaComunicacoes.isNotEmpty) ...[
              Text(
                'Comunicações de venda',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 12),
              ..._consultaComunicacoes.map(_buildCommunicationCard).toList(),
            ] else ...[
              Text(
                'Nenhuma comunicação de venda encontrada para os dados informados.',
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: Colors.grey.shade600,
                ),
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
