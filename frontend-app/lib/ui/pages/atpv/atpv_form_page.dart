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
import 'package:frontend_app/ui/pages/atpv/widgets/atpv_top_bar.dart';
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
  int _ownerDocOption = 1;
  int _buyerDocOption = 1;
  bool _updatingOwnerDoc = false;
  bool _updatingBuyerDoc = false;
  bool _updatingCep = false;
  bool _updatingValor = false;
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
    _ownerDocOption = _resolveDocOption(_ownerDocumentController.text);
    _buyerDocOption = _resolveDocOption(_buyerDocumentController.text);
    _handleOwnerDocumentInput();
    _handleBuyerDocumentInput();
    _handleCepInput();
    _handleValorInput();
    _ownerDocumentController.addListener(_handleOwnerDocumentInput);
    _buyerDocumentController.addListener(_handleBuyerDocumentInput);
    _buyerCepController.addListener(_handleCepInput);
    _saleValueController.addListener(_handleValorInput);
    if (_captchaBytes == null) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _refreshCaptcha());
    }
  }

  @override
  void dispose() {
    _ownerDocumentController.removeListener(_handleOwnerDocumentInput);
    _buyerDocumentController.removeListener(_handleBuyerDocumentInput);
    _buyerCepController.removeListener(_handleCepInput);
    _saleValueController.removeListener(_handleValorInput);
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
      final cepDigits = _onlyDigits(address.cep?.toString());
      if (cepDigits != null) {
        _setCepDigits(cepDigits);
      }

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

  int _resolveDocOption(String? value) {
    final digits = _onlyDigits(value);
    return digits != null && digits.length > 11 ? 2 : 1;
  }

  String? _onlyDigits(String? value) {
    if (value == null) return null;
    final digits = value.replaceAll(RegExp(r'[^0-9]'), '');
    return digits.isEmpty ? null : digits;
  }

  String? _validateDocumento(
    String? value,
    int option, {
    required bool requiredField,
    required String subject,
  }) {
    final digits = _onlyDigits(value);

    if (digits == null) {
      return requiredField ? 'Informe o documento do $subject.' : null;
    }

    if (option == 1 && digits.length != 11) {
      return 'CPF do $subject inválido.';
    }

    if (option == 2 && digits.length != 14) {
      return 'CNPJ do $subject inválido.';
    }

    return null;
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
      final ownerDocDigits = _onlyDigits(_ownerDocumentController.text);
      final buyerDocDigits = _onlyDigits(_buyerDocumentController.text);
      final cepDigits = _onlyDigits(_buyerCepController.text);

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
        cpfCnpjProprietario: ownerDocDigits,
        cpfCnpjComprador: buyerDocDigits ?? '',
        nomeComprador: _buyerNameController.text.trim(),
        emailComprador: _buyerEmailController.text.trim().isEmpty
            ? null
            : _buyerEmailController.text.trim(),
        uf: _buyerStateController.text.trim().toUpperCase(),
        valorVenda: _saleValueController.text.trim().isEmpty
            ? null
            : _saleValueController.text.trim(),
        cepComprador: cepDigits,
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
        municipioCodigo: _onlyDigits(_buyerMunicipioCode),
        opcaoPesquisaProprietario:
            ownerDocDigits == null ? null : _ownerDocOption.toString(),
        opcaoPesquisaComprador: _buyerDocOption.toString(),
      );

      if (!mounted) return;
      final status = result['status']?.toString();
      final apiMessage = result['message']?.toString().trim();
      if (status == null || status != 'awaiting_signature') {
        final failureMessage = (apiMessage != null && apiMessage.isNotEmpty)
            ? apiMessage
            : 'Não foi possível concluir a emissão. Verifique os dados e tente novamente.';
        setState(() {
          _isSubmitting = false;
          _submissionError = failureMessage;
        });
        await _showErrorAlert(failureMessage);
        return;
      }

      setState(() {
        _isSubmitting = false;
        _lastCaptchaUsed = _captchaController.text.trim();
      });

      final registroId = _parseRegistroId(result['registro_id']);
      if (registroId == null) {
        final failureMessage = apiMessage?.isNotEmpty == true
            ? apiMessage!
            : 'Não foi possível identificar o registro da ATPV-e.';
        setState(() {
          _submissionError = failureMessage;
        });
        await _showErrorAlert(failureMessage);
        return;
      }

      final successPrompt = apiMessage != null && apiMessage.isNotEmpty
          ? apiMessage
          : 'Dados enviados com sucesso.';

      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(
          SnackBar(
            content: Text('$successPrompt Vamos definir o tipo de assinatura.'),
          ),
        );

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

  Future<void> _handleDownloadPdf({
    String? placaOverride,
    String? renavamOverride,
    String? captchaOverride,
  }) async {
    final placa = (placaOverride ?? _plateController.text).trim().toUpperCase();
    final renavam = (renavamOverride ?? _renavamController.text).trim();
    final captchaSource =
        captchaOverride ?? (_lastCaptchaUsed ?? _captchaController.text);
    final captcha = captchaSource.trim().toUpperCase();

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
        captcha: captcha,
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

  Future<void> _openDirectPdfDownloadDialog() async {
    final result = await showDialog<_AtpvPdfDownloadRequest>(
      context: context,
      barrierDismissible: false,
      builder: (_) => _DownloadPdfDialog(
        baseService: _baseEstadualService,
      ),
    );

    if (result == null) return;
    await _handleDownloadPdf(
      placaOverride: result.placa,
      renavamOverride: result.renavam,
      captchaOverride: result.captcha,
    );
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
        if (_buyerDocumentController.text.trim().isEmpty) {
          final docDigits = _onlyDigits(comprador['documento']?.toString());
          if (docDigits != null) {
            _setDocumentDigits(
              controller: _buyerDocumentController,
              digits: docDigits,
              isOwner: false,
            );
          }
        }
        _setIfEmpty(_buyerNameController, comprador['nome']?.toString());
        _setIfEmpty(_buyerEmailController, comprador['email']?.toString());
        if (_buyerCepController.text.trim().isEmpty) {
          final cepDigits = _onlyDigits(comprador['cep']?.toString());
          if (cepDigits != null) {
            _setCepDigits(cepDigits);
          }
        }
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
        if (_saleValueController.text.trim().isEmpty) {
          final valorDigits = _onlyDigits(intencao['valor_venda']?.toString());
          if (valorDigits != null && valorDigits.isNotEmpty) {
            _setValorDigits(valorDigits);
          }
        }
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
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AtpvTopBar(
        title: success == null ? 'Preenchimento da ATPV-e' : 'ATPV-e emitida',
        subtitle: success == null
            ? 'Confira os dados antes de enviar'
            : 'ATPV-e pronta para finalizar',
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
          FilledButton.icon(
            onPressed: _openDirectPdfDownloadDialog,
            icon: const Icon(Icons.picture_as_pdf_outlined),
            label: const Text('Baixar PDF já emitido'),
          ),
          const SizedBox(height: 24),
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
    return Form(
      key: _emissaoFormKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _buildSection(
            title: 'Dados do veículo',
            subtitle: 'Informações do proprietário atual',
            icon: Icons.directions_car_filled_outlined,
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
                _buildDocOptionSelector(
                  title: 'Documento do proprietário',
                  groupValue: _ownerDocOption,
                  onChanged: _onOwnerDocOptionChanged,
                ),
                const SizedBox(height: 12),
                _buildTextField(
                  label: 'CPF/CNPJ do proprietário atual',
                  controller: _ownerDocumentController,
                  keyboardType: TextInputType.number,
                  inputFormatters: [
                    FilteringTextInputFormatter.digitsOnly,
                    LengthLimitingTextInputFormatter(
                      _ownerDocOption == 1 ? 11 : 14,
                    ),
                  ],
                  requiredField: false,
                  validator: (value) => _validateDocumento(
                    value,
                    _ownerDocOption,
                    requiredField: false,
                    subject: 'proprietário',
                  ),
                ),
                const SizedBox(height: 20),
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
                  inputFormatters: [
                    FilteringTextInputFormatter.digitsOnly,
                    LengthLimitingTextInputFormatter(12),
                  ],
                  requiredField: false,
                ),
                const SizedBox(height: 16),
                _buildTextField(
                  label: 'Hodômetro',
                  controller: _odometerController,
                  keyboardType: TextInputType.number,
                  requiredField: false,
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          _buildSection(
            title: 'Dados do comprador',
            subtitle: 'Preencha conforme registro no Detran',
            icon: Icons.person_outline,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _buildDocOptionSelector(
                  title: 'Documento do comprador',
                  groupValue: _buyerDocOption,
                  onChanged: _onBuyerDocOptionChanged,
                ),
                const SizedBox(height: 12),
                _buildTextField(
                  label: 'CPF/CNPJ do comprador',
                  controller: _buyerDocumentController,
                  keyboardType: TextInputType.number,
                  inputFormatters: [
                    FilteringTextInputFormatter.digitsOnly,
                    LengthLimitingTextInputFormatter(
                      _buyerDocOption == 1 ? 11 : 14,
                    ),
                  ],
                  validator: (value) => _validateDocumento(
                    value,
                    _buyerDocOption,
                    requiredField: true,
                    subject: 'comprador',
                  ),
                ),
                const SizedBox(height: 20),
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
                          FilteringTextInputFormatter.digitsOnly,
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
              ],
            ),
          ),
          const SizedBox(height: 24),
          _buildSection(
            title: 'Validação',
            subtitle: 'Confirme o captcha e aceite os termos',
            icon: Icons.verified_outlined,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _buildCaptchaSection(),
                const SizedBox(height: 16),
                Container(
                  decoration: BoxDecoration(
                    color: const Color(0xFFF4F7FF),
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: CheckboxListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                    controlAffinity: ListTileControlAffinity.leading,
                    title: const Text('Confirmo que as informações estão corretas.'),
                    value: _termsAccepted,
                    onChanged: (value) => setState(() {
                      _termsAccepted = value ?? false;
                    }),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          FilledButton(
            onPressed: _isSubmitting ? null : _submitEmission,
            child: _isSubmitting
                ? const SizedBox(
                    height: 18,
                    width: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('Enviar ATPV-e'),
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
    );
  }

  Widget _buildSuccessBody(BuildContext context) {
    final payload = _successResult?['payload'];
    final veiculo = payload is Map<String, dynamic> ? payload['parsed'] ?? payload['veiculo'] : null;
    final successMessage = () {
      final message = _successResult?['message']?.toString().trim();
      if (message != null && message.isNotEmpty) {
        return message;
      }
      return 'ATPV-e emitida com sucesso!';
    }();

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
            successMessage,
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
                _ownerDocOption = _resolveDocOption(_ownerDocumentController.text);
                _buyerDocOption = _resolveDocOption(_buyerDocumentController.text);
                _handleOwnerDocumentInput();
                _handleBuyerDocumentInput();
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
    IconData? icon,
    String? subtitle,
  }) {
    final theme = Theme.of(context);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 32,
            offset: const Offset(0, 18),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(24, 24, 24, 30),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (icon != null)
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.primary.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Icon(
                    icon,
                    color: theme.colorScheme.primary,
                  ),
                ),
              if (icon != null) const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                        color: theme.colorScheme.primary,
                      ),
                    ),
                    if (subtitle != null) ...[
                      const SizedBox(height: 4),
                      Text(
                        subtitle,
                        style: theme.textTheme.bodySmall,
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          child,
        ],
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

  void _onOwnerDocOptionChanged(int value) {
    if (_ownerDocOption == value) {
      return;
    }
    setState(() {
      _ownerDocOption = value;
      _ownerDocumentController.clear();
      _handleOwnerDocumentInput();
    });
  }

  void _onBuyerDocOptionChanged(int value) {
    if (_buyerDocOption == value) {
      return;
    }
    setState(() {
      _buyerDocOption = value;
      _buyerDocumentController.clear();
      _handleBuyerDocumentInput();
    });
  }

  void _handleOwnerDocumentInput() {
    if (_updatingOwnerDoc) return;
    _updatingOwnerDoc = true;
    final digits = _onlyDigits(_ownerDocumentController.text) ?? '';
    final limited = _limitDigits(digits, _ownerDocOption == 1 ? 11 : 14);
    final formatted = _ownerDocOption == 1
        ? _formatCpf(limited)
        : _formatCnpj(limited);
    if (_ownerDocumentController.text != formatted) {
      _ownerDocumentController.value = TextEditingValue(
        text: formatted,
        selection: TextSelection.collapsed(offset: formatted.length),
      );
    }
    _updatingOwnerDoc = false;
  }

  void _handleBuyerDocumentInput() {
    if (_updatingBuyerDoc) return;
    _updatingBuyerDoc = true;
    final digits = _onlyDigits(_buyerDocumentController.text) ?? '';
    final limited = _limitDigits(digits, _buyerDocOption == 1 ? 11 : 14);
    final formatted = _buyerDocOption == 1
        ? _formatCpf(limited)
        : _formatCnpj(limited);
    if (_buyerDocumentController.text != formatted) {
      _buyerDocumentController.value = TextEditingValue(
        text: formatted,
        selection: TextSelection.collapsed(offset: formatted.length),
      );
    }
    _updatingBuyerDoc = false;
  }

  void _handleCepInput() {
    if (_updatingCep) return;
    _updatingCep = true;
    final digits = _onlyDigits(_buyerCepController.text) ?? '';
    final limited = _limitDigits(digits, 8);
    final formatted = _formatCep(limited);
    if (_buyerCepController.text != formatted) {
      _buyerCepController.value = TextEditingValue(
        text: formatted,
        selection: TextSelection.collapsed(offset: formatted.length),
      );
    }
    if (!_isFetchingCep && limited.length != 8) {
      _buyerMunicipioCode = null;
    }
    _updatingCep = false;
  }

  void _handleValorInput() {
    if (_updatingValor) return;
    _updatingValor = true;
    final digits = _onlyDigits(_saleValueController.text) ?? '';
    if (digits.isEmpty) {
      _saleValueController.clear();
      _updatingValor = false;
      return;
    }
    final limited = _limitDigits(digits, 12);
    final formatted = _formatCurrencyFromDigits(limited);
    if (_saleValueController.text != formatted) {
      _saleValueController.value = TextEditingValue(
        text: formatted,
        selection: TextSelection.collapsed(offset: formatted.length),
      );
    }
    _updatingValor = false;
  }

  void _setDocumentDigits({
    required TextEditingController controller,
    required String digits,
    required bool isOwner,
  }) {
    final option = digits.length > 11 ? 2 : 1;
    if (isOwner) {
      _ownerDocOption = option;
    } else {
      _buyerDocOption = option;
    }
    final limited = _limitDigits(digits, option == 1 ? 11 : 14);
    final formatted = option == 1 ? _formatCpf(limited) : _formatCnpj(limited);
    if (isOwner) {
      _updatingOwnerDoc = true;
    } else {
      _updatingBuyerDoc = true;
    }
    controller.value = TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
    if (isOwner) {
      _updatingOwnerDoc = false;
    } else {
      _updatingBuyerDoc = false;
    }
  }

  void _setCepDigits(String digits) {
    final limited = _limitDigits(digits, 8);
    final formatted = _formatCep(limited);
    _updatingCep = true;
    _buyerCepController.value = TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
    _updatingCep = false;
  }

  void _setValorDigits(String digits) {
    final limited = _limitDigits(digits, 12);
    final formatted = _formatCurrencyFromDigits(limited);
    _updatingValor = true;
    _saleValueController.value = TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
    _updatingValor = false;
  }

  String _limitDigits(String digits, int maxLen) {
    if (digits.length <= maxLen) {
      return digits;
    }
    return digits.substring(0, maxLen);
  }

  String _formatCpf(String digits) {
    final buffer = StringBuffer();
    for (var i = 0; i < digits.length && i < 11; i++) {
      if (i == 3 || i == 6) buffer.write('.');
      if (i == 9) buffer.write('-');
      buffer.write(digits[i]);
    }
    return buffer.toString();
  }

  String _formatCnpj(String digits) {
    final buffer = StringBuffer();
    for (var i = 0; i < digits.length && i < 14; i++) {
      if (i == 2 || i == 5) buffer.write('.');
      if (i == 8) buffer.write('/');
      if (i == 12) buffer.write('-');
      buffer.write(digits[i]);
    }
    return buffer.toString();
  }

  String _formatCep(String digits) {
    if (digits.isEmpty) {
      return '';
    }
    if (digits.length <= 5) {
      return digits;
    }
    return '${digits.substring(0, 5)}-${digits.substring(5)}';
  }

  String _formatCurrencyFromDigits(String digits) {
    if (digits.isEmpty) {
      return '';
    }
    final limited = digits.isEmpty ? '0' : digits;
    final intValue = int.parse(limited);
    final cents = (intValue % 100).toString().padLeft(2, '0');
    final integerPart = (intValue ~/ 100).toString();
    final integerFormatted = _addThousandsSeparator(integerPart);
    return '$integerFormatted,$cents';
  }

  String _addThousandsSeparator(String value) {
    final buffer = StringBuffer();
    for (var i = 0; i < value.length; i++) {
      buffer.write(value[i]);
      final remaining = value.length - i - 1;
      if (remaining > 0 && remaining % 3 == 0) {
        buffer.write('.');
      }
    }
    return buffer.toString();
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

  Widget _buildDocOptionSelector({
    required String title,
    required int groupValue,
    required ValueChanged<int> onChanged,
  }) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: theme.textTheme.bodySmall?.copyWith(
            fontWeight: FontWeight.w600,
            color: Colors.grey.shade700,
          ),
        ),
        const SizedBox(height: 8),
        Container(
          decoration: BoxDecoration(
            color: const Color(0xFFF8FAFF),
            borderRadius: BorderRadius.circular(18),
          ),
          child: Row(
            children: [
              Expanded(
                child: RadioListTile<int>(
                  value: 1,
                  groupValue: groupValue,
                  dense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                  title: const Text('CPF'),
                  onChanged: (value) {
                    if (value != null) onChanged(value);
                  },
                ),
              ),
              Expanded(
                child: RadioListTile<int>(
                  value: 2,
                  groupValue: groupValue,
                  dense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                  title: const Text('CNPJ'),
                  onChanged: (value) {
                    if (value != null) onChanged(value);
                  },
                ),
              ),
            ],
          ),
        ),
      ],
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

    captchaContent = Center(
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 260),
        child: captchaContent,
      ),
    );

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

    return _buildSection(
      title: 'Resultado da consulta',
      subtitle: 'Dados retornados pelo Detran',
      icon: Icons.receipt_long_outlined,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (displayEntries.isNotEmpty)
            Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
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
          if (displayEntries.isNotEmpty) const SizedBox(height: 12),
          if (_consultaComunicacoes.isNotEmpty) ...[
            Text(
              'Comunicações de venda',
              style: theme.textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.w700,
                color: theme.colorScheme.primary,
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

class _AtpvPdfDownloadRequest {
  const _AtpvPdfDownloadRequest({
    required this.placa,
    required this.renavam,
    required this.captcha,
  });

  final String placa;
  final String renavam;
  final String captcha;
}

class _DownloadPdfDialog extends StatefulWidget {
  const _DownloadPdfDialog({
    required this.baseService,
  });

  final BaseEstadualService baseService;

  @override
  State<_DownloadPdfDialog> createState() => _DownloadPdfDialogState();
}

class _DownloadPdfDialogState extends State<_DownloadPdfDialog> {
  final _formKey = GlobalKey<FormState>();
  final _plateController = TextEditingController();
  final _renavamController = TextEditingController();
  final _captchaController = TextEditingController();

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
      _captchaBytes = null;
      _captchaError = null;
    });

    try {
      final captchaValue = await widget.baseService.fetchCaptcha();
      Uint8List? bytes;
      try {
        bytes = base64Decode(captchaValue);
      } catch (_) {
        bytes = null;
      }
      if (bytes == null || bytes.isEmpty) {
        setState(() {
          _captchaError = 'Captcha inválido. Recarregue.';
        });
      } else {
        setState(() {
          _captchaBytes = bytes;
        });
      }
    } catch (_) {
      setState(() {
        _captchaError = 'Erro ao carregar captcha.';
      });
    } finally {
      if (mounted) {
        setState(() {
          _loadingCaptcha = false;
        });
      }
    }
  }

  void _submit() {
    final form = _formKey.currentState;
    if (form == null) return;
    if (!form.validate()) return;

    setState(() {
      _submitting = true;
    });

    Navigator.of(context).pop(
      _AtpvPdfDownloadRequest(
        placa: _plateController.text.trim().toUpperCase(),
        renavam: _renavamController.text.trim(),
        captcha: _captchaController.text.trim().toUpperCase(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    Widget captchaWidget;
    if (_loadingCaptcha) {
      captchaWidget = const SizedBox(
        height: 80,
        child: Center(child: CircularProgressIndicator()),
      );
    } else if (_captchaBytes != null) {
      captchaWidget = ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: Image.memory(
          _captchaBytes!,
          height: 80,
          fit: BoxFit.cover,
        ),
      );
    } else {
      captchaWidget = Container(
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

    return AlertDialog(
      title: const Text('Baixar PDF já emitido'),
      content: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextFormField(
                controller: _plateController,
                decoration: const InputDecoration(labelText: 'Placa'),
                textCapitalization: TextCapitalization.characters,
                inputFormatters: [
                  const UpperCaseTextFormatter(),
                  FilteringTextInputFormatter.allow(RegExp('[A-Za-z0-9]')),
                  LengthLimitingTextInputFormatter(7),
                ],
                validator: (value) {
                  final text = value?.trim() ?? '';
                  if (text.length != 7) {
                    return 'Informe uma placa válida.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
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
                  if (text.length < 9) {
                    return 'Informe um Renavam válido.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              captchaWidget,
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _captchaController,
                      decoration: const InputDecoration(labelText: 'Captcha'),
                      textCapitalization: TextCapitalization.characters,
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Informe o captcha.';
                        }
                        return null;
                      },
                    ),
                  ),
                  IconButton(
                    onPressed: _loadingCaptcha ? null : _refreshCaptcha,
                    icon: const Icon(Icons.refresh),
                    tooltip: 'Recarregar captcha',
                  ),
                ],
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
              : const Text('Baixar PDF'),
        ),
      ],
    );
  }
}
