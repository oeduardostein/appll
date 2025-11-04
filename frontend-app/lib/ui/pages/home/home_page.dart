import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'package:frontend_app/models/pesquisa_models.dart';
import 'package:frontend_app/services/auth_service.dart';
import 'package:frontend_app/services/base_estadual_service.dart';
import 'package:frontend_app/services/bin_service.dart';
import 'package:frontend_app/services/renainf_service.dart';
import 'package:frontend_app/services/gravame_service.dart';
import 'package:frontend_app/services/pesquisa_service.dart';

import '../atpv/atpv_form_page.dart';
import '../base_state/base_estadual_page.dart';
import '../base_state/base_outros_estados_page.dart';
import '../bin/bin_result_page.dart';
import '../bloqueios/bloqueios_page.dart';
import '../ecrv/ecrv_process_page.dart';
import '../fines/renainf_page.dart';
import '../gravame/gravame_page.dart';
import '../shared/loading_dialog.dart';
import '../auth/login_page.dart';
import 'home_models.dart';
import 'widgets/home_action_card.dart';
import 'widgets/home_header.dart';
import 'widgets/recent_vehicle_card.dart';

class HomePage extends StatefulWidget {
  const HomePage({
    super.key,
    this.authService,
    this.pesquisaService,
  });

  static const routeName = '/home';

  final AuthService? authService;
  final PesquisaService? pesquisaService;

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  late final AuthService _authService;
  final _baseEstadualService = BaseEstadualService();
  final _binService = BinService();
  final _gravameService = GravameService();
  late final PesquisaService _pesquisaService =
      widget.pesquisaService ?? PesquisaService(authService: _authService);
  final _renainfService = RenainfService();
  AuthUser? _currentUser;
  bool _isFetchingUser = false;
  List<RecentVehicle> _recentVehicles = const [];
  bool _isLoadingRecentVehicles = false;
  String? _recentVehiclesError;
  int? _monthlyCreditsUsed;
  bool _isLoadingMonthlyCredits = false;
  String? _monthlyCreditsError;

  static final List<HomeAction> _actions = [
    HomeAction(
      icon: Icons.search,
      title: 'Pesquisas',
      description:
          'Base estadual, BIN, outros Estados, RENAINF, Gravame e bloqueios ativos.',
      subActions: [
        HomeSubAction(icon: Icons.location_on_outlined, label: 'Base estadual'),
        HomeSubAction(
          icon: Icons.public_outlined,
          label: 'Base Outros Estados',
        ),
        HomeSubAction(icon: Icons.credit_card, label: 'BIN'),
        HomeSubAction(icon: Icons.assignment_outlined, label: 'Gravame'),
        HomeSubAction(icon: Icons.directions_car_outlined, label: 'Renainf'),
        HomeSubAction(icon: Icons.lock_outline, label: 'Bloqueios Ativos'),
        HomeSubAction(
          icon: Icons.timeline_outlined,
          label: 'Andamento do processo e-CRV',
        ),
      ],
    ),
    HomeAction(
      icon: Icons.description_outlined,
      title: 'CRLV-e',
      description: 'Emissão do CRLV digital',
    ),
    HomeAction(
      icon: Icons.assignment_turned_in_outlined,
      title: 'Emissão da ATPV-e',
      description: 'Preencher a autorização para transferência',
    ),
  ];

  static final RegExp _oldPlatePattern = RegExp(r'^[A-Z]{3}[0-9]{4}$');
  static final RegExp _mercosurPlatePattern = RegExp(
    r'^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$',
  );
  static final RegExp _chassiPattern = RegExp(r'^[A-HJ-NPR-Z0-9]{17}$');

  static const List<String> _brazilUfCodes = [
    'AC',
    'AL',
    'AP',
    'AM',
    'BA',
    'CE',
    'DF',
    'ES',
    'GO',
    'MA',
    'MT',
    'MS',
    'MG',
    'PA',
    'PB',
    'PR',
    'PE',
    'PI',
    'RJ',
    'RN',
    'RS',
    'RO',
    'RR',
    'SC',
    'SP',
    'SE',
    'TO',
  ];

  int? _expandedIndex;

  void _toggleExpanded(int index) {
    setState(() {
      _expandedIndex = _expandedIndex == index ? null : index;
    });
  }

  Future<void> _handleSubActionTap(
    HomeAction action,
    HomeSubAction subAction,
  ) async {
    if (action.title == 'Pesquisas') {
      if (subAction.label == 'Base estadual') {
        await _handleBaseEstadualFlow();
      } else if (subAction.label == 'Base Outros Estados') {
        await _handleBaseOutrosEstadosFlow();
      } else if (subAction.label == 'BIN') {
        await _handleBinFlow();
      } else if (subAction.label == 'Renainf') {
        await _handleRenainfFlow();
      } else if (subAction.label == 'Bloqueios Ativos') {
        await _handleBloqueiosAtivosFlow();
      } else if (subAction.label == 'Andamento do processo e-CRV') {
        await _handleEcrvProcessFlow();
      } else if (subAction.label == 'Gravame') {
        await _handleGravameFlow();
      }
    }
  }

  Future<void> _handleBaseEstadualFlow() async {
    final query = await _showBaseEstadualDialog();
    if (query == null || !mounted) return;

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      final result = await _baseEstadualService.consultar(
        placa: query.placa,
        renavam: query.renavam,
        captcha: query.captcha,
      );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _registerPesquisa(
        nome: 'Base estadual',
        placa: query.placa,
        renavam: query.renavam,
      );
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => BaseEstadualPage(
            placa: query.placa,
            renavam: query.renavam,
            payload: result,
          ),
        ),
      );
    } on BaseEstadualException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage('Não foi possível consultar a base estadual.');
    }
  }

  Future<_BaseEstadualQuery?> _showBaseEstadualDialog() {
    return _showVehicleLookupDialog(
      title: 'Consulta base estadual',
      fetchCaptcha: () => _baseEstadualService.fetchCaptcha(),
      captchaErrorResolver: _mapBaseEstadualCaptchaError,
    );
  }

  Future<_BaseEstadualQuery?> _showBinDialog() {
    return _showVehicleLookupDialog(
      title: 'Pesquisa BIN',
      fetchCaptcha: () => _binService.fetchCaptcha(),
      captchaErrorResolver: _mapBinCaptchaError,
    );
  }

  Future<_GravameRequest?> _showGravameDialog() async {
    final formKey = GlobalKey<FormState>();
    final plateController = TextEditingController();
    final renavamController = TextEditingController();
    final captchaController = TextEditingController();

    String? selectedUf;
    String? captchaBase64;
    String? captchaError;
    bool isLoadingCaptcha = false;
    bool initialized = false;

    Future<void> refreshCaptcha(StateSetter setState) async {
      setState(() {
        isLoadingCaptcha = true;
        captchaError = null;
        captchaBase64 = null;
      });
      try {
        final image = await _baseEstadualService.fetchCaptcha();
        if (!mounted) return;
        setState(() {
          captchaBase64 = image;
        });
      } on BaseEstadualException catch (e) {
        if (!mounted) return;
        setState(() {
          captchaError = e.message;
        });
      } catch (_) {
        if (!mounted) return;
        setState(() {
          captchaError = 'Erro ao carregar captcha.';
        });
      } finally {
        if (!mounted) return;
        setState(() {
          isLoadingCaptcha = false;
        });
      }
    }

    final result = await showDialog<_GravameRequest>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return Dialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 24),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          child: StatefulBuilder(
            builder: (context, setState) {
              if (!initialized) {
                initialized = true;
                Future.microtask(() => refreshCaptcha(setState));
              }

              Uint8List? captchaBytes;
              if (captchaBase64 != null && captchaBase64!.isNotEmpty) {
                try {
                  captchaBytes = base64Decode(captchaBase64!);
                } catch (_) {
                  captchaError ??= 'Captcha recebido em formato inválido.';
                }
              }

              return ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 560),
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                  child: Form(
                    key: formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
                          children: [
                            Text(
                              'Consultar gravame',
                              style: Theme.of(context).textTheme.titleMedium
                                  ?.copyWith(fontWeight: FontWeight.w700),
                            ),
                            const Spacer(),
                            IconButton(
                              onPressed: () {
                                Navigator.of(dialogContext).pop();
                              },
                              icon: const Icon(Icons.close),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Text(
                          'Informe a placa, renavam, UF e captcha para consultar o gravame.',
                          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                color: const Color(0xFF475467),
                              ),
                        ),
                        const SizedBox(height: 20),
                        TextFormField(
                          controller: plateController,
                          decoration: const InputDecoration(
                            labelText: 'Placa',
                          ),
                          inputFormatters: [
                            const _UpperCaseTextFormatter(),
                            FilteringTextInputFormatter.allow(
                              RegExp('[A-Za-z0-9]'),
                            ),
                            LengthLimitingTextInputFormatter(7),
                          ],
                          textCapitalization: TextCapitalization.characters,
                          validator: (value) {
                            final text = value?.trim().toUpperCase() ?? '';
                            if (text.isEmpty) {
                              return 'Informe a placa';
                            }
                            final normalized = text.replaceAll('-', '');
                            if (!_isValidPlate(normalized)) {
                              return 'Placa inválida';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: renavamController,
                          decoration: const InputDecoration(
                            labelText: 'Renavam',
                          ),
                          keyboardType: TextInputType.number,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                            LengthLimitingTextInputFormatter(11),
                          ],
                          validator: (value) {
                            final text = value?.trim() ?? '';
                            if (text.isEmpty) {
                              return 'Informe o renavam';
                            }
                            if (!_isValidRenavam(text)) {
                              return 'Renavam inválido';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        DropdownButtonFormField<String>(
                          value: selectedUf,
                          items: _brazilUfCodes
                              .map(
                                (uf) => DropdownMenuItem<String>(
                                  value: uf,
                                  child: Text(uf),
                                ),
                              )
                              .toList(),
                          onChanged: (value) => setState(() {
                            selectedUf = value;
                          }),
                          decoration: const InputDecoration(
                            labelText: 'UF',
                          ),
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Selecione a UF';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 20),
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: Theme.of(context)
                                  .colorScheme
                                  .outline
                                  .withOpacity(0.2),
                            ),
                          ),
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Row(
                                children: [
                                  Text(
                                    'Captcha',
                                    style: Theme.of(context)
                                        .textTheme
                                        .titleSmall
                                        ?.copyWith(fontWeight: FontWeight.w600),
                                  ),
                                  const Spacer(),
                                  TextButton.icon(
                                    onPressed: isLoadingCaptcha
                                        ? null
                                        : () => refreshCaptcha(setState),
                                    icon: const Icon(Icons.refresh),
                                    label: const Text('Atualizar'),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              if (isLoadingCaptcha)
                                const Center(
                                  child: Padding(
                                    padding: EdgeInsets.symmetric(vertical: 16),
                                    child: CircularProgressIndicator(),
                                  ),
                                )
                              else if (captchaError != null)
                                Padding(
                                  padding:
                                      const EdgeInsets.symmetric(vertical: 8),
                                  child: Text(
                                    captchaError!,
                                    style: TextStyle(
                                      color:
                                          Theme.of(context).colorScheme.error,
                                    ),
                                  ),
                                )
                              else if (captchaBytes != null)
                                Center(
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(8),
                                    child: Image.memory(
                                      captchaBytes,
                                      width: 180,
                                      height: 80,
                                      fit: BoxFit.contain,
                                      errorBuilder: (_, __, ___) {
                                        return const Text(
                                          'Não foi possível exibir o captcha.',
                                        );
                                      },
                                    ),
                                  ),
                                ),
                              const SizedBox(height: 16),
                              TextFormField(
                                controller: captchaController,
                                decoration: const InputDecoration(
                                  labelText: 'Informe o captcha',
                                ),
                                inputFormatters: [
                                  const _UpperCaseTextFormatter(),
                                  FilteringTextInputFormatter.allow(
                                    RegExp('[A-Za-z0-9]'),
                                  ),
                                  LengthLimitingTextInputFormatter(10),
                                ],
                                textCapitalization:
                                    TextCapitalization.characters,
                                validator: (value) {
                                  final text = value?.trim() ?? '';
                                  if (text.isEmpty) {
                                    return 'Informe o captcha';
                                  }
                                  return null;
                                },
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 24),
                        FilledButton(
                          onPressed: isLoadingCaptcha || captchaBase64 == null
                              ? null
                              : () {
                                  if (!formKey.currentState!.validate()) {
                                    return;
                                  }
                                  if (selectedUf == null ||
                                      selectedUf!.trim().isEmpty) {
                                    return;
                                  }
                                  Navigator.of(dialogContext).pop(
                                    _GravameRequest(
                                      plate: plateController.text
                                          .trim()
                                          .toUpperCase(),
                                      renavam: renavamController.text.trim(),
                                      uf: selectedUf!.toUpperCase(),
                                      captcha: captchaController.text
                                          .trim()
                                          .toUpperCase(),
                                    ),
                                  );
                                },
                          child: const Text('Consultar'),
                        ),
                        const SizedBox(height: 12),
                        TextButton(
                          onPressed: () {
                            Navigator.of(dialogContext).pop();
                          },
                          child: const Text('Cancelar'),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        );
      },
    );

    plateController.dispose();
    renavamController.dispose();
    captchaController.dispose();

    return result;
  }

  Future<_BaseEstadualQuery?> _showVehicleLookupDialog({
    required String title,
    required Future<String> Function() fetchCaptcha,
    String Function(Object error)? captchaErrorResolver,
  }) {
    return showDialog<_BaseEstadualQuery>(
      context: context,
      barrierDismissible: false,
      builder: (_) => _VehicleLookupDialog(
        title: title,
        fetchCaptcha: fetchCaptcha,
        captchaErrorResolver: captchaErrorResolver,
        plateValidator: _isValidPlate,
        renavamValidator: _isValidRenavam,
      ),
    );
  }

  Future<void> _handleBaseOutrosEstadosFlow() async {
    final query = await _showBaseOutrosEstadosDialog();
    if (query == null || !mounted) return;

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      final result = await _baseEstadualService.consultarOutrosEstados(
        chassi: query.chassi,
        uf: query.uf,
        captcha: query.captcha,
      );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _registerPesquisa(
        nome: 'Base outros estados',
        chassi: query.chassi,
        opcaoPesquisa: query.uf,
      );
      final htmlResponse = result['html'];
      if (htmlResponse is String && htmlResponse.isNotEmpty) {
        await _showBaseOutrosEstadosHtmlDialog(
          chassi: query.chassi,
          html: htmlResponse,
        );
      } else {
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => BaseOutrosEstadosPage(
              chassi: query.chassi,
              payload: result,
            ),
          ),
        );
      }
    } on BaseEstadualException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(
        'Não foi possível consultar a base de outros estados.',
      );
    }
  }

  Future<void> _handleBinFlow() async {
    final query = await _showBinDialog();
    if (query == null || !mounted) return;

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      final result = await _binService.consultar(
        placa: query.placa,
        renavam: query.renavam,
        captcha: query.captcha,
      );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _registerPesquisa(
        nome: 'BIN',
        placa: query.placa,
        renavam: query.renavam,
      );
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => BinResultPage(
            placa: query.placa,
            renavam: query.renavam,
            payload: result,
          ),
        ),
      );
    } on BinException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage('Não foi possível concluir a pesquisa BIN.');
    }
  }

  Future<void> _handleRenainfFlow() async {
    final request = await _showRenainfDialog();
    if (request == null || !mounted) return;

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      final result = await _renainfService.consultar(
        plate: request.plate,
        statusCode: request.statusCode,
        statusLabel: request.statusLabel,
        uf: request.uf,
        startDate: request.startDate,
        endDate: request.endDate,
        captcha: request.captcha,
      );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _registerPesquisa(
        nome: 'RENAINF',
        placa: request.plate,
        opcaoPesquisa: '${request.statusCode}-${request.statusLabel}',
      );
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => RenainfPage(result: result),
        ),
      );
    } on RenainfException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage('Não foi possível consultar o RENAINF.');
    }
  }

  Future<void> _handleEcrvProcessFlow() async {
    final request = await _showEcrvProcessDialog();
    if (request == null || !mounted) return;

    await _showLoadingDialog();
    if (!mounted) return;
    _registerPesquisa(
      nome: 'Processo e-CRVsp',
      placa: request.plate,
      chassi: request.chassi,
    );

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => EcrvProcessPage(
          plate: request.plate,
          chassi: request.chassi,
        ),
      ),
    );
  }

  Future<void> _handleBloqueiosAtivosFlow() async {
    final request = await _showBloqueiosAtivosDialog();
    if (request == null || !mounted) return;

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      final result = await _baseEstadualService.consultarBloqueiosAtivos(
        origin: request.origin,
        captcha: request.captcha,
        chassi: request.chassi,
        opcaoPesquisa: request.opcaoPesquisa,
      );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _registerPesquisa(
        nome: 'Bloqueios ativos',
        chassi: request.chassi,
        opcaoPesquisa: request.opcaoPesquisa,
      );
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => BloqueiosPage(
            origin: request.origin,
            chassi: request.chassi,
            payload: result,
          ),
        ),
      );
    } on BaseEstadualException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage('Não foi possível consultar os bloqueios ativos.');
    }
  }

  Future<void> _handlePrimaryActionTap(HomeAction action) async {
    if (action.title == 'CRLV-e') {
      await _handleCrlvEmissionFlow();
    } else if (action.title == 'Emissão da ATPV-e') {
      await _handleAtpvEmissionFlow();
    }
  }

  Future<void> _handleCrlvEmissionFlow() async {
    final request = await _showCrlvEmissionDialog();
    if (request == null || !mounted) return;
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      final isCpf = request.document.length <= 11;
      final cpf = isCpf ? request.document : '';
      final cnpj = isCpf ? '' : request.document;
      final opcao = isCpf ? '1' : '2';

      final result = await _baseEstadualService.emitirCrlv(
        placa: request.plate,
        renavam: request.renavam,
        cpf: cpf,
        cnpj: cnpj,
        captchaResponse: request.captcha,
        opcaoPesquisa: opcao,
      );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _registerPesquisa(
        nome: 'Emissão do CRLV-e',
        placa: request.plate,
        renavam: request.renavam,
        opcaoPesquisa: opcao,
      );
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => BaseEstadualPage(
            placa: request.plate,
            renavam: request.renavam,
            payload: result,
            pageTitle: 'Emissão do CRLV-e',
          ),
        ),
      );
    } on BaseEstadualException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage('Não foi possível emitir o CRLV-e.');
    }
  }

  Future<void> _handleAtpvEmissionFlow() async {
    final request = await _showAtpvEmissionDialog();
    if (request == null || !mounted) return;

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      final result = await _baseEstadualService.emitirAtpv(
        placa: request.plate,
        renavam: request.renavam,
        captchaResponse: request.captcha,
      );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _registerPesquisa(
        nome: 'Emissão da ATPV-e',
        placa: request.plate,
        renavam: request.renavam,
      );
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => BaseEstadualPage(
            placa: request.plate,
            renavam: request.renavam,
            payload: result,
            pageTitle: 'Emissão da ATPV-e',
          ),
        ),
      );
    } on BaseEstadualException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage('Não foi possível emitir a ATPV-e.');
    }
  }

  Future<_CrlvEmissionRequest?> _showCrlvEmissionDialog() async {
    final formKey = GlobalKey<FormState>();
    final plateController = TextEditingController();
    final renavamController = TextEditingController();
    final documentController = TextEditingController();
    final captchaController = TextEditingController();

    String? captchaBase64;
    String? captchaError;
    bool isLoadingCaptcha = false;
    bool initialized = false;

    Future<void> refreshCaptcha(StateSetter setState) async {
      setState(() {
        isLoadingCaptcha = true;
        captchaError = null;
      });
      try {
        final image = await _baseEstadualService.fetchCaptcha();
        setState(() {
          captchaBase64 = image;
        });
      } on BaseEstadualException catch (e) {
        setState(() {
          captchaError = e.message;
        });
      } catch (_) {
        setState(() {
          captchaError = 'Erro ao carregar captcha.';
        });
      } finally {
        setState(() {
          isLoadingCaptcha = false;
        });
      }
    }

    final result = await showDialog<_CrlvEmissionRequest>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return Dialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 24),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          child: StatefulBuilder(
            builder: (context, setState) {
              if (!initialized) {
                initialized = true;
                Future.microtask(() => refreshCaptcha(setState));
              }

              Uint8List? captchaBytes;
              if (captchaBase64 != null && captchaBase64!.isNotEmpty) {
                try {
                  captchaBytes = base64Decode(captchaBase64!);
                } catch (_) {
                  captchaError ??= 'Captcha recebido em formato inválido.';
                }
              }

              return ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 560),
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                  child: Form(
                    key: formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
                          children: [
                            Text(
                              'Emissão do CRLV-e',
                              style: Theme.of(context).textTheme.titleMedium
                                  ?.copyWith(fontWeight: FontWeight.w700),
                            ),
                            const Spacer(),
                            IconButton(
                              onPressed: () {
                                Navigator.of(dialogContext).pop();
                              },
                              icon: const Icon(Icons.close),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: plateController,
                          decoration: const InputDecoration(
                            labelText: 'Placa',
                          ),
                          inputFormatters: [
                            const _UpperCaseTextFormatter(),
                            FilteringTextInputFormatter.allow(
                              RegExp('[A-Za-z0-9]'),
                            ),
                            LengthLimitingTextInputFormatter(7),
                          ],
                          textCapitalization: TextCapitalization.characters,
                          validator: (value) {
                            final text = value?.trim().toUpperCase() ?? '';
                            if (text.isEmpty) {
                              return 'Informe a placa';
                            }
                            if (!_isValidPlate(text)) {
                              return 'Placa inválida';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: renavamController,
                          decoration: const InputDecoration(
                            labelText: 'Renavam',
                          ),
                          keyboardType: TextInputType.number,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                            LengthLimitingTextInputFormatter(11),
                          ],
                          validator: (value) {
                            final text = value?.trim() ?? '';
                            if (text.isEmpty) {
                              return 'Informe o renavam';
                            }
                            if (!_isValidRenavam(text)) {
                              return 'Renavam inválido';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: documentController,
                          decoration: const InputDecoration(
                            labelText: 'CPF / CNPJ',
                          ),
                          keyboardType: TextInputType.number,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                            LengthLimitingTextInputFormatter(14),
                          ],
                          validator: (value) {
                            final text = value?.trim() ?? '';
                            if (text.isEmpty) {
                              return 'Informe o CPF ou CNPJ';
                            }
                            if (!_isValidCpfCnpj(text)) {
                              return 'Documento inválido';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 24),
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: Theme.of(context)
                                  .colorScheme
                                  .outline
                                  .withOpacity(0.2),
                            ),
                          ),
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Row(
                                children: [
                                  Text(
                                    'Captcha',
                                    style: Theme.of(context)
                                        .textTheme
                                        .titleSmall
                                        ?.copyWith(fontWeight: FontWeight.w600),
                                  ),
                                  const Spacer(),
                                  TextButton.icon(
                                    onPressed: isLoadingCaptcha
                                        ? null
                                        : () => refreshCaptcha(setState),
                                    icon: const Icon(Icons.refresh),
                                    label: const Text('Atualizar'),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              if (isLoadingCaptcha)
                                const Center(
                                  child: Padding(
                                    padding: EdgeInsets.symmetric(vertical: 16),
                                    child: CircularProgressIndicator(),
                                  ),
                                )
                              else if (captchaError != null)
                                Padding(
                                  padding:
                                      const EdgeInsets.symmetric(vertical: 8),
                                  child: Text(
                                    captchaError!,
                                    style: TextStyle(
                                      color:
                                          Theme.of(context).colorScheme.error,
                                    ),
                                  ),
                                )
                              else if (captchaBytes != null)
                                Center(
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(8),
                                    child: Image.memory(
                                      captchaBytes,
                                      width: 180,
                                      height: 80,
                                      fit: BoxFit.contain,
                                    ),
                                  ),
                                )
                              else
                                const SizedBox.shrink(),
                              const SizedBox(height: 16),
                              TextFormField(
                                controller: captchaController,
                                decoration: const InputDecoration(
                                  labelText: 'Digite o captcha',
                                ),
                                inputFormatters: [
                                  const _UpperCaseTextFormatter(),
                                  FilteringTextInputFormatter.allow(
                                    RegExp('[A-Za-z0-9]'),
                                  ),
                                  LengthLimitingTextInputFormatter(10),
                                ],
                                textCapitalization:
                                    TextCapitalization.characters,
                                validator: (value) {
                                  final text = value?.trim() ?? '';
                                  if (text.isEmpty) {
                                    return 'Informe o captcha';
                                  }
                                  return null;
                                },
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 24),
                        FilledButton(
                          onPressed: isLoadingCaptcha || captchaBase64 == null
                              ? null
                              : () {
                                  if (!formKey.currentState!.validate()) {
                                    return;
                                  }
                                  Navigator.of(dialogContext).pop(
                                    _CrlvEmissionRequest(
                                      plate: plateController.text
                                          .trim()
                                          .toUpperCase(),
                                      renavam: renavamController.text.trim(),
                                      document:
                                          documentController.text.trim(),
                                      captcha: captchaController.text
                                          .trim()
                                          .toUpperCase(),
                                    ),
                                  );
                                },
                          child: const Text('Emitir'),
                        ),
                        const SizedBox(height: 12),
                        TextButton(
                          onPressed: () {
                            Navigator.of(dialogContext).pop();
                          },
                          child: const Text('Cancelar'),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        );
      },
    );

    plateController.dispose();
    renavamController.dispose();
    documentController.dispose();
    captchaController.dispose();

    return result;
  }

  Future<_AtpvEmissionRequest?> _showAtpvEmissionDialog() async {
    final formKey = GlobalKey<FormState>();
    final plateController = TextEditingController();
    final renavamController = TextEditingController();
    final captchaController = TextEditingController();

    String? captchaBase64;
    String? captchaError;
    bool isLoadingCaptcha = false;
    bool initialized = false;

    Future<void> refreshCaptcha(StateSetter setState) async {
      setState(() {
        isLoadingCaptcha = true;
        captchaError = null;
      });
      try {
        final image = await _baseEstadualService.fetchCaptcha();
        setState(() {
          captchaBase64 = image;
        });
      } on BaseEstadualException catch (e) {
        setState(() {
          captchaError = e.message;
        });
      } catch (_) {
        setState(() {
          captchaError = 'Erro ao carregar captcha.';
        });
      } finally {
        setState(() {
          isLoadingCaptcha = false;
        });
      }
    }

    final result = await showDialog<_AtpvEmissionRequest>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return Dialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 24),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          child: StatefulBuilder(
            builder: (context, setState) {
              if (!initialized) {
                initialized = true;
                Future.microtask(() => refreshCaptcha(setState));
              }

              Uint8List? captchaBytes;
              if (captchaBase64 != null && captchaBase64!.isNotEmpty) {
                try {
                  captchaBytes = base64Decode(captchaBase64!);
                } catch (_) {
                  captchaError ??= 'Captcha recebido em formato inválido.';
                }
              }

              return ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 520),
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                  child: Form(
                    key: formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
                          children: [
                            Text(
                              'Emissão da ATPV-e',
                              style: Theme.of(context).textTheme.titleMedium
                                  ?.copyWith(fontWeight: FontWeight.w700),
                            ),
                            const Spacer(),
                            IconButton(
                              onPressed: () {
                                Navigator.of(dialogContext).pop();
                              },
                              icon: const Icon(Icons.close),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: plateController,
                          decoration: const InputDecoration(
                            labelText: 'Placa',
                          ),
                          inputFormatters: [
                            const _UpperCaseTextFormatter(),
                            FilteringTextInputFormatter.allow(
                              RegExp('[A-Za-z0-9]'),
                            ),
                            LengthLimitingTextInputFormatter(7),
                          ],
                          textCapitalization: TextCapitalization.characters,
                          validator: (value) {
                            final text = value?.trim().toUpperCase() ?? '';
                            if (text.isEmpty) {
                              return 'Informe a placa';
                            }
                            if (!_isValidPlate(text)) {
                              return 'Placa inválida';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: renavamController,
                          decoration: const InputDecoration(
                            labelText: 'Renavam',
                          ),
                          keyboardType: TextInputType.number,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                            LengthLimitingTextInputFormatter(11),
                          ],
                          validator: (value) {
                            final text = value?.trim() ?? '';
                            if (text.isEmpty) {
                              return 'Informe o renavam';
                            }
                            if (!_isValidRenavam(text)) {
                              return 'Renavam inválido';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 24),
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: Theme.of(context)
                                  .colorScheme
                                  .outline
                                  .withOpacity(0.2),
                            ),
                          ),
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Row(
                                children: [
                                  Text(
                                    'Captcha',
                                    style: Theme.of(context)
                                        .textTheme
                                        .titleSmall
                                        ?.copyWith(fontWeight: FontWeight.w600),
                                  ),
                                  const Spacer(),
                                  TextButton.icon(
                                    onPressed: isLoadingCaptcha
                                        ? null
                                        : () => refreshCaptcha(setState),
                                    icon: const Icon(Icons.refresh),
                                    label: const Text('Atualizar'),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              if (isLoadingCaptcha)
                                const Center(
                                  child: Padding(
                                    padding: EdgeInsets.symmetric(vertical: 16),
                                    child: CircularProgressIndicator(),
                                  ),
                                )
                              else if (captchaError != null)
                                Padding(
                                  padding:
                                      const EdgeInsets.symmetric(vertical: 8),
                                  child: Text(
                                    captchaError!,
                                    style: TextStyle(
                                      color:
                                          Theme.of(context).colorScheme.error,
                                    ),
                                  ),
                                )
                              else if (captchaBytes != null)
                                Center(
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(8),
                                    child: Image.memory(
                                      captchaBytes,
                                      width: 180,
                                      height: 80,
                                      fit: BoxFit.contain,
                                    ),
                                  ),
                                )
                              else
                                const SizedBox.shrink(),
                              const SizedBox(height: 16),
                              TextFormField(
                                controller: captchaController,
                                decoration: const InputDecoration(
                                  labelText: 'Digite o captcha',
                                ),
                                inputFormatters: [
                                  const _UpperCaseTextFormatter(),
                                  FilteringTextInputFormatter.allow(
                                    RegExp('[A-Za-z0-9]'),
                                  ),
                                  LengthLimitingTextInputFormatter(10),
                                ],
                                textCapitalization:
                                    TextCapitalization.characters,
                                validator: (value) {
                                  final text = value?.trim() ?? '';
                                  if (text.isEmpty) {
                                    return 'Informe o captcha';
                                  }
                                  return null;
                                },
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 24),
                        FilledButton(
                          onPressed: isLoadingCaptcha || captchaBase64 == null
                              ? null
                              : () {
                                  if (!formKey.currentState!.validate()) {
                                    return;
                                  }
                                  Navigator.of(dialogContext).pop(
                                    _AtpvEmissionRequest(
                                      plate: plateController.text
                                          .trim()
                                          .toUpperCase(),
                                      renavam: renavamController.text.trim(),
                                      captcha: captchaController.text
                                          .trim()
                                          .toUpperCase(),
                                    ),
                                  );
                                },
                          child: const Text('Emitir'),
                        ),
                        const SizedBox(height: 12),
                        TextButton(
                          onPressed: () {
                            Navigator.of(dialogContext).pop();
                          },
                          child: const Text('Cancelar'),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        );
      },
    );

    plateController.dispose();
    renavamController.dispose();
    captchaController.dispose();

    return result;
  }

  Future<_BaseOutrosEstadosQuery?> _showBaseOutrosEstadosDialog() async {
    final formKey = GlobalKey<FormState>();
    final chassiController = TextEditingController();
    final captchaController = TextEditingController();
    String? selectedUf;

    String? captchaBase64;
    String? captchaError;
    bool isLoadingCaptcha = false;
    bool initialized = false;

    Future<void> refreshCaptcha(StateSetter setState) async {
      setState(() {
        isLoadingCaptcha = true;
        captchaError = null;
      });
      try {
        final image = await _baseEstadualService.fetchCaptcha();
        setState(() {
          captchaBase64 = image;
        });
      } on BaseEstadualException catch (e) {
        setState(() {
          captchaError = e.message;
        });
      } catch (_) {
        setState(() {
          captchaError = 'Erro ao carregar captcha.';
        });
      } finally {
        setState(() {
          isLoadingCaptcha = false;
        });
      }
    }

    final result = await showDialog<_BaseOutrosEstadosQuery>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return Dialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 24),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          child: StatefulBuilder(
            builder: (context, setState) {
              if (!initialized) {
                initialized = true;
                Future.microtask(() => refreshCaptcha(setState));
              }

              Uint8List? captchaBytes;
              if (captchaBase64 != null && captchaBase64!.isNotEmpty) {
                try {
                  captchaBytes = base64Decode(captchaBase64!);
                } catch (_) {
                  captchaError ??= 'Captcha recebido em formato inválido.';
                }
              }

              final theme = Theme.of(context);

              return Form(
                key: formKey,
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Text(
                            'Base de outros estados',
                            style: theme.textTheme.titleMedium
                                ?.copyWith(fontWeight: FontWeight.w700),
                          ),
                          const Spacer(),
                          IconButton(
                            onPressed: () {
                              Navigator.of(dialogContext).pop();
                            },
                            icon: const Icon(Icons.close),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Text(
                        'Informe o chassi e o captcha exibido para consultar a base de outros estados.',
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: const Color(0xFF475467),
                        ),
                      ),
                      const SizedBox(height: 20),
                      TextFormField(
                        controller: chassiController,
                        decoration: const InputDecoration(
                          labelText: 'Chassi',
                        ),
                        inputFormatters: [
                          const _UpperCaseTextFormatter(),
                          FilteringTextInputFormatter.allow(
                            RegExp('[A-Za-z0-9]'),
                          ),
                          LengthLimitingTextInputFormatter(17),
                        ],
                        textCapitalization: TextCapitalization.characters,
                        validator: (value) {
                          final text = value?.trim().toUpperCase() ?? '';
                          if (text.isEmpty) {
                            return 'Informe o chassi';
                          }
                          if (!_isValidChassi(text)) {
                            return 'Chassi inválido';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 16),
                      DropdownButtonFormField<String>(
                        value: selectedUf,
                        decoration: const InputDecoration(
                          labelText: 'UF',
                          hintText: 'Selecione a UF',
                        ),
                        items: _brazilUfCodes
                            .map(
                              (uf) => DropdownMenuItem<String>(
                                value: uf,
                                child: Text(uf),
                              ),
                            )
                            .toList(),
                        onChanged: (value) {
                          setState(() {
                            selectedUf = value;
                          });
                        },
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Selecione a UF';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 20),
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: Theme.of(context)
                                .colorScheme
                                .outline
                                .withOpacity(0.2),
                          ),
                        ),
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Row(
                              children: [
                                Text(
                                  'Captcha',
                                  style: theme.textTheme.titleSmall
                                      ?.copyWith(fontWeight: FontWeight.w600),
                                ),
                                const Spacer(),
                                TextButton.icon(
                                  onPressed: isLoadingCaptcha
                                      ? null
                                      : () => refreshCaptcha(setState),
                                  icon: const Icon(Icons.refresh),
                                  label: const Text('Atualizar'),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            if (isLoadingCaptcha)
                              const Center(
                                child: Padding(
                                  padding: EdgeInsets.symmetric(vertical: 16),
                                  child: CircularProgressIndicator(),
                                ),
                              )
                            else if (captchaError != null)
                              Padding(
                                padding:
                                    const EdgeInsets.symmetric(vertical: 8),
                                child: Text(
                                  captchaError!,
                                  style: TextStyle(
                                    color: theme.colorScheme.error,
                                  ),
                                ),
                              )
                            else if (captchaBytes != null)
                              Center(
                                child: ClipRRect(
                                  borderRadius: BorderRadius.circular(8),
                                  child: Image.memory(
                                    captchaBytes,
                                    width: 180,
                                    height: 80,
                                    fit: BoxFit.contain,
                                  ),
                                ),
                              )
                            else
                              const SizedBox.shrink(),
                            const SizedBox(height: 16),
                            TextFormField(
                              controller: captchaController,
                              decoration: const InputDecoration(
                                labelText: 'Digite o captcha',
                              ),
                              inputFormatters: [
                                const _UpperCaseTextFormatter(),
                                FilteringTextInputFormatter.allow(
                                  RegExp('[A-Za-z0-9]'),
                                ),
                                LengthLimitingTextInputFormatter(10),
                              ],
                              textCapitalization:
                                  TextCapitalization.characters,
                              validator: (value) {
                                final text = value?.trim() ?? '';
                                if (text.isEmpty) {
                                  return 'Informe o captcha';
                                }
                                return null;
                              },
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),
                      FilledButton(
                        onPressed: isLoadingCaptcha || captchaBase64 == null
                            ? null
                            : () {
                                if (!formKey.currentState!.validate()) {
                                  return;
                                }
                                if (selectedUf == null) {
                                  return;
                                }
                                FocusManager.instance.primaryFocus?.unfocus();
                                Navigator.of(dialogContext).pop(
                                  _BaseOutrosEstadosQuery(
                                    chassi: chassiController.text
                                        .trim()
                                        .toUpperCase(),
                                    uf: selectedUf!,
                                    captcha: captchaController.text
                                        .trim()
                                        .toUpperCase(),
                                  ),
                                );
                              },
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(52),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: const Text('Pesquisar'),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        );
      },
    );

    WidgetsBinding.instance.addPostFrameCallback((_) {
      chassiController.dispose();
      captchaController.dispose();
    });
    return result;
  }

  Future<void> _handleGravameFlow() async {
    final request = await _showGravameDialog();
    if (request == null || !mounted) return;

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      final result = await _gravameService.consultar(
        placa: request.plate,
        renavam: request.renavam,
        uf: request.uf,
        captcha: request.captcha,
      );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _registerPesquisa(
        nome: 'Gravame',
        placa: request.plate,
        renavam: request.renavam,
        opcaoPesquisa: request.uf,
      );
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => GravamePage(
            placa: request.plate,
            renavam: request.renavam,
            uf: request.uf,
            payload: result,
          ),
        ),
      );
    } on GravameException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage('Não foi possível consultar o gravame.');
    }
  }

  void _registerPesquisa({
    required String nome,
    String? placa,
    String? renavam,
    String? chassi,
    String? opcaoPesquisa,
  }) {
    if (_authService.session == null) {
      return;
    }

    String? normalize(String? value) {
      final trimmed = value?.trim();
      if (trimmed == null || trimmed.isEmpty) {
        return null;
      }
      return trimmed;
    }

    unawaited(() async {
      try {
        await _pesquisaService.salvarPesquisa(
          nome: nome,
          placa: normalize(placa),
          renavam: normalize(renavam),
          chassi: normalize(chassi),
          opcaoPesquisa: normalize(opcaoPesquisa),
        );
        if (!mounted) return;
        _loadRecentVehicles();
        _loadMonthlyCredits();
      } on PesquisaException catch (e) {
        debugPrint('Falha ao registrar pesquisa "$nome": ${e.message}');
      } catch (e) {
        debugPrint('Falha ao registrar pesquisa "$nome": $e');
      }
    }());
  }

  Future<void> _showBaseOutrosEstadosHtmlDialog({
    required String chassi,
    required String html,
  }) async {
    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        final theme = Theme.of(dialogContext);
        final maxHeight = MediaQuery.of(dialogContext).size.height * 0.7;

        return Dialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 24),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          child: SizedBox(
            width: 520,
            height: maxHeight,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 20, 12, 0),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Resultado - Base outros estados',
                              style: theme.textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Chassi consultado: $chassi',
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: const Color(0xFF475467),
                              ),
                            ),
                          ],
                        ),
                      ),
                      IconButton(
                        onPressed: () => Navigator.of(dialogContext).pop(),
                        icon: const Icon(Icons.close),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                const Divider(height: 1),
                Expanded(
                  child: Scrollbar(
                    thumbVisibility: true,
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
                      child: SelectableText(
                        html,
                        style: const TextStyle(
                          fontFamily: 'monospace',
                          fontSize: 13,
                          height: 1.4,
                        ),
                      ),
                    ),
                  ),
                ),
                const Divider(height: 1),
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
                  child: Row(
                    children: [
                      TextButton.icon(
                        onPressed: () async {
                          await Clipboard.setData(ClipboardData(text: html));
                          if (mounted) {
                            ScaffoldMessenger.of(context)
                              ..clearSnackBars()
                              ..showSnackBar(
                                const SnackBar(
                                  content: Text(
                                    'HTML copiado para a área de transferência.',
                                  ),
                                ),
                              );
                          }
                        },
                        icon: const Icon(Icons.copy_outlined),
                        label: const Text('Copiar HTML'),
                      ),
                      const Spacer(),
                      FilledButton(
                        onPressed: () => Navigator.of(dialogContext).pop(),
                        child: const Text('Fechar'),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Future<_EcrvProcessRequest?> _showEcrvProcessDialog() async {
    final plateController = TextEditingController();
    final chassiController = TextEditingController();

    final result = await showDialog<_EcrvProcessRequest>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return Dialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 24),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          child: StatefulBuilder(
            builder: (context, setState) {
              final plateText = plateController.text.trim().toUpperCase();
              final chassiText = chassiController.text.trim().toUpperCase();

              final plateValid =
                  plateText.isNotEmpty && _isValidPlate(plateText);
              final chassiValid =
                  chassiText.isNotEmpty && _isValidChassi(chassiText);
              final isValid = plateValid || chassiValid;

              return ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 480),
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Text(
                            'Andamento do processo e-CRV',
                            style: Theme.of(context).textTheme.titleMedium
                                ?.copyWith(fontWeight: FontWeight.w700),
                          ),
                          const Spacer(),
                          IconButton(
                            onPressed: () {
                              FocusManager.instance.primaryFocus?.unfocus();
                              Navigator.of(dialogContext).pop();
                            },
                            icon: const Icon(Icons.close),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      TextField(
                        controller: plateController,
                        decoration: InputDecoration(
                          labelText: 'Placa',
                          errorText: plateText.isNotEmpty && !plateValid
                              ? 'Placa inválida'
                              : null,
                        ),
                        inputFormatters: [
                          const _UpperCaseTextFormatter(),
                          FilteringTextInputFormatter.allow(
                            RegExp('[A-Za-z0-9]'),
                          ),
                          LengthLimitingTextInputFormatter(7),
                        ],
                        textCapitalization: TextCapitalization.characters,
                        onChanged: (_) => setState(() {}),
                      ),
                      const SizedBox(height: 12),
                      Center(
                        child: Text(
                          'ou',
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: const Color(0xFF667085),
                                fontWeight: FontWeight.w600,
                              ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: chassiController,
                        decoration: InputDecoration(
                          labelText: 'Chassi',
                          errorText: chassiText.isNotEmpty && !chassiValid
                              ? 'Chassi inválido'
                              : null,
                        ),
                        inputFormatters: [
                          const _UpperCaseTextFormatter(),
                          FilteringTextInputFormatter.allow(
                            RegExp('[A-Za-z0-9]'),
                          ),
                          LengthLimitingTextInputFormatter(17),
                        ],
                        textCapitalization: TextCapitalization.characters,
                        onChanged: (_) => setState(() {}),
                      ),
                      const SizedBox(height: 24),
                      FilledButton(
                        onPressed: isValid
                            ? () {
                                FocusManager.instance.primaryFocus?.unfocus();
                                Navigator.of(dialogContext).pop(
                                  _EcrvProcessRequest(
                                    plate: plateValid ? plateText : null,
                                    chassi: chassiValid ? chassiText : null,
                                  ),
                                );
                              }
                            : null,
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(52),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: const Text('Avançar'),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        );
      },
    );

    WidgetsBinding.instance.addPostFrameCallback((_) {
      plateController.dispose();
      chassiController.dispose();
    });
    return result;
  }

  Future<_RenainfRequest?> _showRenainfDialog() async {
    final formKey = GlobalKey<FormState>();
    final plateController = TextEditingController();
    final startDateController = TextEditingController();
    final endDateController = TextEditingController();
    final captchaController = TextEditingController();

    const statusOptions = [
      {'label': 'Todas', 'value': 2},
      {'label': 'Multas em cobrança', 'value': 1},
    ];

    int? selectedStatusValue = 2;
    String selectedStatusLabel = 'Todas';
    String? selectedUf;
    DateTime? startDate;
    DateTime? endDate;

    String? captchaBase64;
    String? captchaError;
    bool isLoadingCaptcha = false;
    bool initialized = false;

    Future<void> refreshCaptcha(StateSetter setState) async {
      setState(() {
        isLoadingCaptcha = true;
        captchaError = null;
      });
      try {
        final image = await _baseEstadualService.fetchCaptcha();
        setState(() {
          captchaBase64 = image;
        });
      } on BaseEstadualException catch (e) {
        setState(() {
          captchaError = e.message;
        });
      } catch (_) {
        setState(() {
          captchaError = 'Erro ao carregar captcha.';
        });
      } finally {
        setState(() {
          isLoadingCaptcha = false;
        });
      }
    }

    Future<void> pickDate({
      required bool isStart,
      required StateSetter setState,
    }) async {
      final initialDate =
          isStart ? startDate ?? DateTime.now() : endDate ?? DateTime.now();
      final firstDate = DateTime(DateTime.now().year - 5);
      final lastDate = DateTime.now();

      final picked = await showDatePicker(
        context: context,
        initialDate: initialDate,
        firstDate: firstDate,
        lastDate: lastDate,
      );

      if (picked == null) return;

      setState(() {
        if (isStart) {
          startDate = picked;
          startDateController.text = _formatDate(picked);
        } else {
          endDate = picked;
          endDateController.text = _formatDate(picked);
        }
      });

      // Trigger validation updates.
      formKey.currentState?.validate();
    }

    final result = await showDialog<_RenainfRequest>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return Dialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 24),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          child: StatefulBuilder(
            builder: (context, setState) {
              if (!initialized) {
                initialized = true;
                Future.microtask(() => refreshCaptcha(setState));
              }

              Uint8List? captchaBytes;
              if (captchaBase64 != null && captchaBase64!.isNotEmpty) {
                try {
                  captchaBytes = base64Decode(captchaBase64!);
                } catch (_) {
                  captchaError ??= 'Captcha recebido em formato inválido.';
                }
              }

              final plateText = plateController.text.trim().toUpperCase();
              final captchaText = captchaController.text.trim().toUpperCase();
              final plateValid =
                  plateText.isNotEmpty && _isValidPlate(plateText);
              final datesValid = startDate != null &&
                  endDate != null &&
                  !startDate!.isAfter(endDate!);
              final captchaLoaded =
                  captchaBase64 != null && captchaBase64!.isNotEmpty;

              final readyToSubmit = plateValid &&
                  datesValid &&
                  selectedStatusValue != null &&
                  selectedUf != null &&
                  captchaLoaded &&
                  captchaText.isNotEmpty &&
                  !isLoadingCaptcha;

              return Form(
                key: formKey,
                autovalidateMode: AutovalidateMode.onUserInteraction,
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Text(
                            'RENAINF',
                            style: Theme.of(context).textTheme.titleMedium
                                ?.copyWith(fontWeight: FontWeight.w700),
                          ),
                          const Spacer(),
                          IconButton(
                            onPressed: () {
                              FocusManager.instance.primaryFocus?.unfocus();
                              Navigator.of(dialogContext).pop();
                            },
                            icon: const Icon(Icons.close),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: plateController,
                        decoration: const InputDecoration(
                          labelText: 'Placa',
                        ),
                        inputFormatters: [
                          const _UpperCaseTextFormatter(),
                          FilteringTextInputFormatter.allow(
                            RegExp('[A-Za-z0-9]'),
                          ),
                          LengthLimitingTextInputFormatter(7),
                        ],
                        textCapitalization: TextCapitalization.characters,
                        validator: (value) {
                          final text = value?.trim().toUpperCase() ?? '';
                          if (text.isEmpty) {
                            return 'Informe a placa';
                          }
                          if (!_isValidPlate(text)) {
                            return 'Placa inválida';
                          }
                          return null;
                        },
                        onChanged: (_) => setState(() {}),
                      ),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<int>(
                        value: selectedStatusValue,
                        decoration: const InputDecoration(
                          labelText: 'Status da multa',
                        ),
                        items: statusOptions
                            .map(
                              (status) => DropdownMenuItem<int>(
                                value: status['value']! as int,
                                child: Text(status['label']! as String),
                              ),
                            )
                            .toList(),
                        onChanged: (value) {
                          if (value == null) return;
                          setState(() {
                            selectedStatusValue = value;
                            selectedStatusLabel = statusOptions.firstWhere(
                              (status) => status['value'] == value,
                            )['label']! as String;
                          });
                        },
                        validator: (value) {
                          if (value == null) {
                            return 'Selecione o status';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<String>(
                        value: selectedUf,
                        decoration: const InputDecoration(
                          labelText: 'UF',
                          hintText: 'Selecione a UF',
                        ),
                        items: _brazilUfCodes
                            .map(
                              (uf) => DropdownMenuItem<String>(
                                value: uf,
                                child: Text(uf),
                              ),
                            )
                            .toList(),
                        onChanged: (value) {
                          setState(() {
                            selectedUf = value;
                          });
                        },
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Selecione a UF';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: startDateController,
                              readOnly: true,
                              decoration: const InputDecoration(
                                labelText: 'Data inicial',
                              ),
                              validator: (_) {
                                if (startDate == null) {
                                  return 'Informe a data inicial';
                                }
                                if (endDate != null &&
                                    startDate!.isAfter(endDate!)) {
                                  return 'Deve ser anterior à final';
                                }
                                return null;
                              },
                              onTap: () async {
                                FocusManager.instance.primaryFocus?.unfocus();
                                await pickDate(
                                  isStart: true,
                                  setState: setState,
                                );
                              },
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextFormField(
                              controller: endDateController,
                              readOnly: true,
                              decoration: const InputDecoration(
                                labelText: 'Data final',
                              ),
                              validator: (_) {
                                if (endDate == null) {
                                  return 'Informe a data final';
                                }
                                if (startDate != null &&
                                    endDate!.isBefore(startDate!)) {
                                  return 'Deve ser posterior à inicial';
                                }
                                return null;
                              },
                              onTap: () async {
                                FocusManager.instance.primaryFocus?.unfocus();
                                await pickDate(
                                  isStart: false,
                                  setState: setState,
                                );
                              },
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 20),
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: Theme.of(context)
                                .colorScheme
                                .outline
                                .withOpacity(0.2),
                          ),
                        ),
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Row(
                              children: [
                                Text(
                                  'Captcha',
                                  style: Theme.of(context)
                                      .textTheme
                                      .titleSmall
                                      ?.copyWith(fontWeight: FontWeight.w600),
                                ),
                                const Spacer(),
                                TextButton.icon(
                                  onPressed: isLoadingCaptcha
                                      ? null
                                      : () => refreshCaptcha(setState),
                                  icon: const Icon(Icons.refresh),
                                  label: const Text('Atualizar'),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            if (isLoadingCaptcha)
                              const Center(
                                child: Padding(
                                  padding: EdgeInsets.symmetric(vertical: 16),
                                  child: CircularProgressIndicator(),
                                ),
                              )
                            else if (captchaError != null)
                              Padding(
                                padding:
                                    const EdgeInsets.symmetric(vertical: 8),
                                child: Text(
                                  captchaError!,
                                  style: TextStyle(
                                    color: Theme.of(context).colorScheme.error,
                                  ),
                                ),
                              )
                            else if (captchaBytes != null)
                              Center(
                                child: ClipRRect(
                                  borderRadius: BorderRadius.circular(8),
                                  child: Image.memory(
                                    captchaBytes,
                                    width: 180,
                                    height: 80,
                                    fit: BoxFit.contain,
                                  ),
                                ),
                              )
                            else
                              const SizedBox.shrink(),
                            const SizedBox(height: 16),
                            TextFormField(
                              controller: captchaController,
                              decoration: const InputDecoration(
                                labelText: 'Digite o captcha',
                              ),
                              inputFormatters: [
                                const _UpperCaseTextFormatter(),
                                FilteringTextInputFormatter.allow(
                                  RegExp('[A-Za-z0-9]'),
                                ),
                                LengthLimitingTextInputFormatter(10),
                              ],
                              textCapitalization:
                                  TextCapitalization.characters,
                              validator: (value) {
                                final text = value?.trim() ?? '';
                                if (text.isEmpty) {
                                  return 'Informe o captcha';
                                }
                                return null;
                              },
                              onChanged: (_) => setState(() {}),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),
                      FilledButton(
                        onPressed: readyToSubmit
                            ? () {
                                if (!(formKey.currentState?.validate() ??
                                    false)) {
                                  return;
                                }
                                FocusManager.instance.primaryFocus?.unfocus();
                                Navigator.of(dialogContext).pop(
                                  _RenainfRequest(
                                    plate: plateText,
                                    statusCode: selectedStatusValue!,
                                    statusLabel: selectedStatusLabel,
                                    uf: selectedUf!,
                                    captcha: captchaText,
                                    startDate: startDate!,
                                    endDate: endDate!,
                                  ),
                                );
                              }
                            : null,
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(52),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: const Text('Consultar'),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        );
      },
    );

    WidgetsBinding.instance.addPostFrameCallback((_) {
      plateController.dispose();
      startDateController.dispose();
      endDateController.dispose();
      captchaController.dispose();
    });
    return result;
  }

  Future<_BloqueiosAtivosRequest?> _showBloqueiosAtivosDialog() async {
    final chassiController = TextEditingController();
    final captchaController = TextEditingController();

    String selectedSource = 'DETRAN';

    String? captchaBase64;
    String? captchaError;
    bool isLoadingCaptcha = false;
    bool initialized = false;

    Future<void> refreshCaptcha(StateSetter setState) async {
      setState(() {
        isLoadingCaptcha = true;
        captchaError = null;
      });
      try {
        final image = await _baseEstadualService.fetchCaptcha();
        setState(() {
          captchaBase64 = image;
        });
      } on BaseEstadualException catch (e) {
        setState(() {
          captchaError = e.message;
        });
      } catch (_) {
        setState(() {
          captchaError = 'Erro ao carregar captcha.';
        });
      } finally {
        setState(() {
          isLoadingCaptcha = false;
        });
      }
    }

    final result = await showDialog<_BloqueiosAtivosRequest>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return Dialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 24),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          child: StatefulBuilder(
            builder: (context, setState) {
              if (!initialized) {
                initialized = true;
                Future.microtask(() => refreshCaptcha(setState));
              }

              final chassiText = chassiController.text.trim().toUpperCase();

              final chassiValid =
                  chassiText.isNotEmpty && _isValidChassi(chassiText);
              final captchaValue = captchaController.text.trim().toUpperCase();
              final captchaValid = captchaValue.isNotEmpty;
              final isValid = chassiValid &&
                  captchaValid &&
                  captchaBase64 != null &&
                  !isLoadingCaptcha;

              Color backgroundFor(String source) =>
                  selectedSource == source
                      ? Theme.of(context).colorScheme.primary
                      : const Color(0xFFE7EDFF);
              Color foregroundFor(String source) =>
                  selectedSource == source ? Colors.white : const Color(0xFF344054);

              Uint8List? captchaBytes;
              if (captchaBase64 != null && captchaBase64!.isNotEmpty) {
                try {
                  captchaBytes = base64Decode(captchaBase64!);
                } catch (_) {
                  captchaError ??= 'Captcha recebido em formato inválido.';
                }
              }

              return ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 680),
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Text(
                            'Bloqueios ativos',
                            style: Theme.of(context).textTheme.titleMedium
                                ?.copyWith(fontWeight: FontWeight.w700),
                          ),
                          const Spacer(),
                          IconButton(
                            onPressed: () {
                              FocusManager.instance.primaryFocus?.unfocus();
                              Navigator.of(dialogContext).pop();
                            },
                            icon: const Icon(Icons.close),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Container(
                        decoration: BoxDecoration(
                          color: const Color(0xFFF2F4F7),
                          borderRadius: BorderRadius.circular(999),
                        ),
                        padding: const EdgeInsets.all(4),
                        child: Row(
                          children: [
                            Expanded(
                              child: FilledButton(
                                onPressed: () {
                                  if (selectedSource != 'DETRAN') {
                                    setState(() => selectedSource = 'DETRAN');
                                  }
                                },
                                style: FilledButton.styleFrom(
                                  padding:
                                      const EdgeInsets.symmetric(vertical: 12),
                                  backgroundColor: backgroundFor('DETRAN'),
                                  foregroundColor: foregroundFor('DETRAN'),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(999),
                                  ),
                                ),
                                child: const Text('DETRAN'),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: FilledButton(
                                onPressed: () {
                                  if (selectedSource != 'RENAJUD') {
                                    setState(() => selectedSource = 'RENAJUD');
                                  }
                                },
                                style: FilledButton.styleFrom(
                                  padding:
                                      const EdgeInsets.symmetric(vertical: 12),
                                  backgroundColor: backgroundFor('RENAJUD'),
                                  foregroundColor: foregroundFor('RENAJUD'),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(999),
                                  ),
                                ),
                                child: const Text('RENAJUD'),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      TextField(
                        controller: chassiController,
                        decoration: InputDecoration(
                          labelText: 'Chassi',
                          errorText: chassiText.isNotEmpty && !chassiValid
                              ? 'Chassi inválido'
                              : null,
                        ),
                        inputFormatters: [
                          const _UpperCaseTextFormatter(),
                          FilteringTextInputFormatter.allow(
                            RegExp('[A-Za-z0-9]'),
                          ),
                          LengthLimitingTextInputFormatter(17),
                        ],
                        textCapitalization: TextCapitalization.characters,
                        onChanged: (_) => setState(() {}),
                      ),
                      const SizedBox(height: 20),
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: Theme.of(context)
                                .colorScheme
                                .outline
                                .withOpacity(0.2),
                          ),
                        ),
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Row(
                              children: [
                                Text(
                                  'Captcha',
                                  style: Theme.of(context)
                                      .textTheme
                                      .titleSmall
                                      ?.copyWith(fontWeight: FontWeight.w600),
                                ),
                                const Spacer(),
                                TextButton.icon(
                                  onPressed: isLoadingCaptcha
                                      ? null
                                      : () => refreshCaptcha(setState),
                                  icon: const Icon(Icons.refresh),
                                  label: const Text('Atualizar'),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            if (isLoadingCaptcha)
                              const Center(
                                child: Padding(
                                  padding: EdgeInsets.symmetric(vertical: 16),
                                  child: CircularProgressIndicator(),
                                ),
                              )
                            else if (captchaError != null)
                              Padding(
                                padding:
                                    const EdgeInsets.symmetric(vertical: 8),
                                child: Text(
                                  captchaError!,
                                  style: TextStyle(
                                    color: Theme.of(context).colorScheme.error,
                                  ),
                                ),
                              )
                            else if (captchaBytes != null)
                              Center(
                                child: ClipRRect(
                                  borderRadius: BorderRadius.circular(8),
                                  child: Image.memory(
                                    captchaBytes,
                                    width: 180,
                                    height: 80,
                                    fit: BoxFit.contain,
                                  ),
                                ),
                              )
                            else
                              const SizedBox.shrink(),
                            const SizedBox(height: 16),
                            TextField(
                              controller: captchaController,
                              decoration: const InputDecoration(
                                labelText: 'Digite o captcha',
                              ),
                              inputFormatters: [
                                const _UpperCaseTextFormatter(),
                                FilteringTextInputFormatter.allow(
                                  RegExp('[A-Za-z0-9]'),
                                ),
                                LengthLimitingTextInputFormatter(10),
                              ],
                              textCapitalization:
                                  TextCapitalization.characters,
                              onChanged: (_) => setState(() {}),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),
                      FilledButton(
                        onPressed: isValid
                            ? () {
                                FocusManager.instance.primaryFocus?.unfocus();
                              Navigator.of(dialogContext).pop(
                                  _BloqueiosAtivosRequest(
                                    origin: selectedSource,
                                    captcha: captchaValue,
                                    chassi: chassiText,
                                    opcaoPesquisa:
                                        selectedSource == 'DETRAN' ? '1' : '2',
                                  ),
                                );
                              }
                            : null,
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(52),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: const Text('Pesquisar'),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        );
      },
    );

    WidgetsBinding.instance.addPostFrameCallback((_) {
      chassiController.dispose();
      captchaController.dispose();
    });
    return result;
  }

  Future<void> _showLoadingDialog() async {
    if (!mounted) return;

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    await Future.delayed(const Duration(seconds: 2));
    if (!mounted) return;
    Navigator.of(context, rootNavigator: true).pop();
  }

  void _showErrorMessage(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..clearSnackBars()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  String _mapBaseEstadualCaptchaError(Object error) {
    if (error is BaseEstadualException) {
      return error.message;
    }
    return 'Erro ao carregar captcha.';
  }

  String _mapBinCaptchaError(Object error) {
    if (error is BinException) {
      return error.message;
    }
    if (error is BaseEstadualException) {
      return error.message;
    }

    return 'Erro ao carregar captcha.';
  }

  bool _isValidPlate(String value) {
    final normalized = value.replaceAll('-', '').toUpperCase();
    if (normalized.length != 7) {
      return false;
    }
    return _oldPlatePattern.hasMatch(normalized) ||
        _mercosurPlatePattern.hasMatch(normalized);
  }

  bool _isValidChassi(String value) {
    final normalized =
        value.replaceAll(RegExp('[^A-Za-z0-9]'), '').toUpperCase();
    if (normalized.length != 17) {
      return false;
    }
    return _chassiPattern.hasMatch(normalized);
  }

  bool _isValidRenavam(String value) {
    final digitsOnly = value.replaceAll(RegExp(r'\D'), '');
    return RegExp(r'^\d{11}$').hasMatch(digitsOnly);
  }

  bool _isValidCpfCnpj(String value) {
    final digitsOnly = value.replaceAll(RegExp(r'\D'), '');
    return digitsOnly.length == 11 || digitsOnly.length == 14;
  }

  String _formatDate(DateTime? date) {
    if (date == null) return '';
    final day = date.day.toString().padLeft(2, '0');
    final month = date.month.toString().padLeft(2, '0');
    final year = date.year.toString().padLeft(4, '0');
    return '$day/$month/$year';
  }

  @override
  void initState() {
    super.initState();
    _authService = widget.authService ?? AuthService();
    _currentUser = _authService.session?.user;
    _loadCurrentUser();
    _loadRecentVehicles();
    _loadMonthlyCredits();
  }

  Future<void> _loadRecentVehicles() async {
    if (!mounted) return;
    if (_authService.session == null) {
      setState(() {
        _recentVehicles = const [];
        _recentVehiclesError = null;
        _isLoadingRecentVehicles = false;
      });
      return;
    }

    setState(() {
      _isLoadingRecentVehicles = true;
      _recentVehiclesError = null;
    });

    try {
      final pesquisas = await _pesquisaService.listarRecentes();
      if (!mounted) return;
      setState(() {
        _recentVehicles = pesquisas
            .map(_mapPesquisaResumoToRecentVehicle)
            .toList(growable: false);
        _isLoadingRecentVehicles = false;
      });
    } on PesquisaException catch (e) {
      if (!mounted) return;
      if (e.message.contains('Sessão inválida') ||
          e.message.contains('Não autenticado')) {
        _handleUnauthorized();
        return;
      }
      setState(() {
        _recentVehicles = const [];
        _isLoadingRecentVehicles = false;
        _recentVehiclesError = e.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _recentVehicles = const [];
        _isLoadingRecentVehicles = false;
        _recentVehiclesError =
            'Não foi possível carregar os veículos pesquisados.';
      });
    }
  }

  Future<void> _loadMonthlyCredits() async {
    if (!mounted) return;
    if (_authService.session == null) {
      setState(() {
        _monthlyCreditsUsed = null;
        _monthlyCreditsError = null;
        _isLoadingMonthlyCredits = false;
      });
      return;
    }

    setState(() {
      _isLoadingMonthlyCredits = true;
      _monthlyCreditsError = null;
    });

    try {
      final pesquisas = await _pesquisaService.listarUltimoMes();
      if (!mounted) return;
      setState(() {
        _monthlyCreditsUsed = pesquisas.length;
        _isLoadingMonthlyCredits = false;
      });
    } on PesquisaException catch (e) {
      if (!mounted) return;
      if (e.message.contains('Sessão inválida') ||
          e.message.contains('Não autenticado')) {
        _handleUnauthorized();
        return;
      }
      setState(() {
        _monthlyCreditsUsed = null;
        _isLoadingMonthlyCredits = false;
        _monthlyCreditsError = e.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _monthlyCreditsUsed = null;
        _isLoadingMonthlyCredits = false;
        _monthlyCreditsError =
            'Não foi possível carregar os créditos utilizados este mês.';
      });
    }
  }

  RecentVehicle _mapPesquisaResumoToRecentVehicle(
    PesquisaResumo resumo,
  ) {
    final plate = resumo.placa?.trim().toUpperCase();
    final leading = plate?.isNotEmpty == true
        ? plate!
        : (resumo.chassi?.trim().toUpperCase() ?? resumo.nome);

    final details = <String>[
      resumo.nome,
      if (resumo.renavam != null && resumo.renavam!.trim().isNotEmpty)
        'Renavam: ${resumo.renavam}',
      if (resumo.chassi != null && resumo.chassi!.trim().isNotEmpty)
        'Chassi: ${resumo.chassi}',
      if (resumo.opcaoPesquisa != null &&
          resumo.opcaoPesquisa!.trim().isNotEmpty)
        'Opção: ${resumo.opcaoPesquisa}',
    ];

    return RecentVehicle(
      plate: leading,
      summary: details.join('\n'),
      dateTime: resumo.createdAt,
    );
  }

  Future<void> _loadCurrentUser() async {
    setState(() {
      _isFetchingUser = true;
    });

    try {
      final user = await _authService.fetchCurrentUser();
      if (!mounted) return;
      setState(() {
        _currentUser = user;
        _isFetchingUser = false;
      });
    } on AuthException catch (e) {
      if (!mounted) return;
      setState(() {
        _isFetchingUser = false;
      });
      if (e.message.contains('Não autenticado') ||
          e.message.contains('Sessão expirada')) {
        _handleUnauthorized();
      } else {
        _showErrorMessage(e.message);
      }
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _isFetchingUser = false;
      });
      _showErrorMessage('Não foi possível carregar os dados do usuário.');
    }
  }

  Future<void> _handleLogout() async {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      await _authService.logout();
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      Navigator.of(context).pushNamedAndRemoveUntil(
        LoginPage.routeName,
        (route) => false,
      );
    } on AuthException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _showErrorMessage(e.message);
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _showErrorMessage('Não foi possível sair. Tente novamente.');
    }
  }

  void _handleUnauthorized() {
   _authService.clearSession();
   WidgetsBinding.instance.addPostFrameCallback((_) {
     if (!mounted) return;
     Navigator.of(context).pushNamedAndRemoveUntil(
       LoginPage.routeName,
       (route) => false,
     );
   });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final monthlyCreditsLabel = _isLoadingMonthlyCredits
        ? 'Créditos usados este mês: carregando...'
        : _monthlyCreditsError != null
            ? 'Créditos usados este mês: indisponível'
            : _monthlyCreditsUsed != null
                ? 'Créditos usados este mês: $_monthlyCreditsUsed'
                : null;

    return Scaffold(
      body: SafeArea(
        child: CustomScrollView(
          slivers: [
            SliverToBoxAdapter(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  HomeHeader(
                    userName: _isFetchingUser
                        ? 'Carregando...'
                        : _currentUser?.username ?? 'Usuário',
                    monthlyCreditsLabel: monthlyCreditsLabel,
                    onLogout: () => _handleLogout(),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 20,
                      vertical: 24,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        for (var i = 0; i < _actions.length; i++)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 16),
                            child: HomeActionCard(
                              action: _actions[i],
                              isExpanded: _expandedIndex == i,
                              onTap: _actions[i].subActions.isNotEmpty
                                  ? () => _toggleExpanded(i)
                                  : () => _handlePrimaryActionTap(_actions[i]),
                              onSubActionTap: (subAction) =>
                                  _handleSubActionTap(_actions[i], subAction),
                            ),
                          ),
                        const SizedBox(height: 8),
                        Text(
                          'Últimos veículos pesquisados',
                          style: theme.textTheme.headlineSmall?.copyWith(
                            fontSize: 18,
                          ),
                        ),
                        const SizedBox(height: 16),
                        if (_isLoadingRecentVehicles)
                          const Center(
                            child: Padding(
                              padding: EdgeInsets.symmetric(vertical: 24),
                              child: CircularProgressIndicator(),
                            ),
                          )
                        else if (_recentVehiclesError != null)
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _recentVehiclesError!,
                                style: theme.textTheme.bodyMedium?.copyWith(
                                  color: theme.colorScheme.error,
                                ),
                              ),
                              TextButton(
                                onPressed: _loadRecentVehicles,
                                child: const Text('Tentar novamente'),
                              ),
                            ],
                          )
                        else if (_recentVehicles.isEmpty)
                          Text(
                            'Nenhuma pesquisa recente encontrada.',
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: const Color(0xFF667085),
                            ),
                          )
                        else
                          ..._recentVehicles.map(
                            (vehicle) => Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: RecentVehicleCard(vehicle: vehicle),
                            ),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _VehicleLookupDialog extends StatefulWidget {
  const _VehicleLookupDialog({
    required this.title,
    required this.fetchCaptcha,
    required this.plateValidator,
    required this.renavamValidator,
    this.captchaErrorResolver,
    this.submitLabel = 'Consultar',
    this.captchaLabel = 'Informe o captcha',
  });

  final String title;
  final Future<String> Function() fetchCaptcha;
  final bool Function(String value) plateValidator;
  final bool Function(String value) renavamValidator;
  final String Function(Object error)? captchaErrorResolver;
  final String submitLabel;
  final String captchaLabel;

  @override
  State<_VehicleLookupDialog> createState() => _VehicleLookupDialogState();
}

class _VehicleLookupDialogState extends State<_VehicleLookupDialog> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _plateController;
  late final TextEditingController _renavamController;
  late final TextEditingController _captchaController;

  bool _isLoadingCaptcha = false;
  String? _captchaBase64;
  String? _captchaError;

  @override
  void initState() {
    super.initState();
    _plateController = TextEditingController();
    _renavamController = TextEditingController();
    _captchaController = TextEditingController();
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
      _isLoadingCaptcha = true;
      _captchaError = null;
      _captchaBase64 = null;
    });
    try {
      final image = await widget.fetchCaptcha();
      if (!mounted) return;
      setState(() {
        _captchaBase64 = image;
      });
    } catch (Object error) {
      if (!mounted) return;
      setState(() {
        _captchaError = widget.captchaErrorResolver != null
            ? widget.captchaErrorResolver!(error)
            : 'Erro ao carregar captcha.';
      });
    } finally {
      if (!mounted) return;
      setState(() {
        _isLoadingCaptcha = false;
      });
    }
  }

  void _submit() {
    if (_isLoadingCaptcha || _captchaBase64 == null) {
      return;
    }
    if (!_formKey.currentState!.validate()) {
      return;
    }
    Navigator.of(context).pop(
      _BaseEstadualQuery(
        placa: _plateController.text.trim().toUpperCase(),
        renavam: _renavamController.text.trim(),
        captcha: _captchaController.text.trim().toUpperCase(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    Uint8List? captchaBytes;
    if (_captchaBase64 != null && _captchaBase64!.isNotEmpty) {
      try {
        captchaBytes = base64Decode(_captchaBase64!);
      } catch (_) {
        _captchaError ??= 'Captcha recebido em formato inválido.';
      }
    }

    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 24),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(24),
      ),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxHeight: 520),
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    Text(
                      widget.title,
                      style: Theme.of(context).textTheme.titleMedium
                          ?.copyWith(fontWeight: FontWeight.w700),
                    ),
                    const Spacer(),
                    IconButton(
                      onPressed: () => Navigator.of(context).pop(),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _plateController,
                  decoration: const InputDecoration(
                    labelText: 'Placa',
                  ),
                  inputFormatters: [
                    const _UpperCaseTextFormatter(),
                    FilteringTextInputFormatter.allow(
                      RegExp('[A-Za-z0-9]'),
                    ),
                    LengthLimitingTextInputFormatter(7),
                  ],
                  textCapitalization: TextCapitalization.characters,
                  validator: (value) {
                    final text = value?.trim().toUpperCase() ?? '';
                    if (text.isEmpty) {
                      return 'Informe a placa';
                    }
                    final normalized = text.replaceAll('-', '');
                    if (!widget.plateValidator(normalized)) {
                      return 'Placa inválida';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _renavamController,
                  decoration: const InputDecoration(
                    labelText: 'Renavam',
                  ),
                  keyboardType: TextInputType.number,
                  inputFormatters: [
                    FilteringTextInputFormatter.digitsOnly,
                    LengthLimitingTextInputFormatter(11),
                  ],
                  validator: (value) {
                    final text = value?.trim() ?? '';
                    if (text.isEmpty) {
                      return 'Informe o renavam';
                    }
                    if (!widget.renavamValidator(text)) {
                      return 'Renavam inválido';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 20),
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: Theme.of(context)
                          .colorScheme
                          .outline
                          .withOpacity(0.2),
                    ),
                  ),
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Text(
                            'Captcha',
                            style: Theme.of(context)
                                .textTheme
                                .titleSmall
                                ?.copyWith(fontWeight: FontWeight.w600),
                          ),
                          const Spacer(),
                          TextButton.icon(
                            onPressed: _isLoadingCaptcha ? null : _refreshCaptcha,
                            icon: const Icon(Icons.refresh),
                            label: const Text('Atualizar'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      if (_isLoadingCaptcha)
                        const Center(
                          child: Padding(
                            padding: EdgeInsets.symmetric(vertical: 16),
                            child: CircularProgressIndicator(),
                          ),
                        )
                      else if (_captchaError != null)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          child: Text(
                            _captchaError!,
                            style: TextStyle(
                              color: Theme.of(context).colorScheme.error,
                            ),
                          ),
                     )
                    else if (captchaBytes != null)
                      Center(
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: Image.memory(
                            captchaBytes,
                            width: 180,
                            height: 80,
                            fit: BoxFit.contain,
                            errorBuilder: (_, __, ___) {
                              return const Text(
                                'Não foi possível exibir o captcha.',
                              );
                            },
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _captchaController,
                        decoration: InputDecoration(
                          labelText: widget.captchaLabel,
                        ),
                        inputFormatters: [
                          const _UpperCaseTextFormatter(),
                          FilteringTextInputFormatter.allow(
                            RegExp('[A-Za-z0-9]'),
                          ),
                          LengthLimitingTextInputFormatter(10),
                        ],
                        textCapitalization: TextCapitalization.characters,
                        validator: (value) {
                          final text = value?.trim() ?? '';
                          if (text.isEmpty) {
                            return 'Informe o captcha';
                          }
                          return null;
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),
                FilledButton(
                  onPressed:
                      _isLoadingCaptcha || _captchaBase64 == null ? null : _submit,
                  child: Text(widget.submitLabel),
                ),
                const SizedBox(height: 12),
                TextButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text('Cancelar'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _BaseEstadualQuery {
  const _BaseEstadualQuery({
    required this.placa,
    required this.renavam,
    required this.captcha,
  });

  final String placa;
  final String renavam;
  final String captcha;
}

class _BaseOutrosEstadosQuery {
  const _BaseOutrosEstadosQuery({
    required this.chassi,
    required this.uf,
    required this.captcha,
  });

  final String chassi;
  final String uf;
  final String captcha;
}

class _CrlvEmissionRequest {
  const _CrlvEmissionRequest({
    required this.plate,
    required this.renavam,
    required this.document,
    required this.captcha,
  });

  final String plate;
  final String renavam;
  final String document;
  final String captcha;
}

class _AtpvEmissionRequest {
  const _AtpvEmissionRequest({
    required this.plate,
    required this.renavam,
    required this.captcha,
  });

  final String plate;
  final String renavam;
  final String captcha;
}

class _GravameRequest {
  const _GravameRequest({
    required this.plate,
    required this.renavam,
    required this.uf,
    required this.captcha,
  });

  final String plate;
  final String renavam;
  final String uf;
  final String captcha;
}

class _RenainfRequest {
  const _RenainfRequest({
    required this.plate,
    required this.statusCode,
    required this.statusLabel,
    required this.uf,
    required this.captcha,
    required this.startDate,
    required this.endDate,
  });

  final String plate;
  final int statusCode;
  final String statusLabel;
  final String uf;
  final String captcha;
  final DateTime startDate;
  final DateTime endDate;
}

class _BloqueiosAtivosRequest {
  const _BloqueiosAtivosRequest({
    required this.origin,
    required this.captcha,
    required this.chassi,
    required this.opcaoPesquisa,
  });

  final String origin;
  final String captcha;
  final String chassi;
  final String opcaoPesquisa;
}

class _EcrvProcessRequest {
  const _EcrvProcessRequest({this.plate, this.chassi});

  final String? plate;
  final String? chassi;
}

class _UpperCaseTextFormatter extends TextInputFormatter {
  const _UpperCaseTextFormatter();

  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    return newValue.copyWith(
      text: newValue.text.toUpperCase(),
      selection: newValue.selection,
      composing: TextRange.empty,
    );
  }
}
  
