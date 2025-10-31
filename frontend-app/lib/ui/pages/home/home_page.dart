import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'package:frontend_app/services/auth_service.dart';
import 'package:frontend_app/services/base_estadual_service.dart';

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
  const HomePage({super.key});

  static const routeName = '/home';

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  final _authService = AuthService();
  final _baseEstadualService = BaseEstadualService();
  AuthUser? _currentUser;
  bool _isFetchingUser = false;

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

  static final List<RecentVehicle> _recentVehicles = [
    RecentVehicle(
      plate: 'GEP-1E11',
      summary: 'Pesquisa de débitos',
      dateTime: DateTime(2025, 10, 6, 14, 32),
    ),
    RecentVehicle(
      plate: 'GEP-1E11',
      summary: 'Pesquisa de débitos',
      dateTime: DateTime(2025, 10, 6, 14, 32),
    ),
  ];

  static final RegExp _oldPlatePattern = RegExp(r'^[A-Z]{3}[0-9]{4}$');
  static final RegExp _mercosurPlatePattern = RegExp(
    r'^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$',
  );
  static final RegExp _chassiPattern = RegExp(r'^[A-HJ-NPR-Z0-9]{17}$');

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

  Future<_BaseEstadualQuery?> _showBaseEstadualDialog() async {
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

    final result = await showDialog<_BaseEstadualQuery>(
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
                              'Consulta base estadual',
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
                                  Navigator.of(dialogContext).pop(
                                    _BaseEstadualQuery(
                                      placa: plateController.text
                                          .trim()
                                          .toUpperCase(),
                                      renavam: renavamController.text.trim(),
                                      captcha:
                                          captchaController.text.trim().toUpperCase(),
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
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => BinResultPage(
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
      _showErrorMessage('Não foi possível concluir a pesquisa BIN.');
    }
  }

  Future<void> _handleRenainfFlow() async {
    final request = await _showRenainfDialog();
    if (request == null || !mounted) return;

    await _showLoadingDialog();
    if (!mounted) return;

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => RenainfPage(
          plate: request.plate,
          status: request.status,
          startDate: request.startDate,
          endDate: request.endDate,
        ),
      ),
    );
  }

  Future<void> _handleEcrvProcessFlow() async {
    final request = await _showEcrvProcessDialog();
    if (request == null || !mounted) return;

    await _showLoadingDialog();
    if (!mounted) return;

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
        placa: request.plate,
        chassi: request.chassi,
      );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => BloqueiosPage(
            origin: request.origin,
            plate: request.plate,
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
    final ufController = TextEditingController(text: 'SP');
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
                      TextFormField(
                        controller: ufController,
                        decoration: const InputDecoration(
                          labelText: 'UF',
                          hintText: 'Ex: SP',
                        ),
                        inputFormatters: [
                          const _UpperCaseTextFormatter(),
                          FilteringTextInputFormatter.allow(
                            RegExp('[A-Za-z]'),
                          ),
                          LengthLimitingTextInputFormatter(2),
                        ],
                        textCapitalization: TextCapitalization.characters,
                        validator: (value) {
                          final text = value?.trim().toUpperCase() ?? '';
                          if (text.isEmpty) {
                            return 'Informe a UF';
                          }
                          if (!RegExp(r'^[A-Z]{2}$').hasMatch(text)) {
                            return 'UF inválida';
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
                                FocusManager.instance.primaryFocus?.unfocus();
                                Navigator.of(dialogContext).pop(
                                  _BaseOutrosEstadosQuery(
                                    chassi: chassiController.text
                                        .trim()
                                        .toUpperCase(),
                                    uf: ufController.text.trim().toUpperCase(),
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
      ufController.dispose();
      captchaController.dispose();
    });
    return result;
  }

  Future<_SimplePlateChassiRequest?> _showSimplePlateChassiDialog({
    required String title,
  }) async {
    final plateController = TextEditingController();
    final chassiController = TextEditingController();

    final result = await showDialog<_SimplePlateChassiRequest>(
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
                constraints: const BoxConstraints(maxHeight: 460),
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Text(
                            title,
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
                      const SizedBox(height: 20),
                      FilledButton(
                        onPressed: isValid
                            ? () {
                                FocusManager.instance.primaryFocus?.unfocus();
                                Navigator.of(dialogContext).pop(
                                  _SimplePlateChassiRequest(
                                    plate: plateValid ? plateText : '',
                                    chassi: chassiValid ? chassiText : '',
                                  ),
                                );
                              }
                            : null,
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
      plateController.dispose();
      chassiController.dispose();
    });
    return result;
  }

  Future<void> _handleGravameFlow() async {
    final request = await _showSimplePlateChassiDialog(title: 'Gravame');
    if (request == null || !mounted) return;

    await _showLoadingDialog();
    if (!mounted) return;

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => const GravamePage(),
      ),
    );
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
    final plateController = TextEditingController();
    final startDateController = TextEditingController();
    final endDateController = TextEditingController();

    final statusOptions = ['Todas', 'Em aberto', 'Pago', 'Em recurso'];

    String? selectedStatus = 'Todas';
    DateTime? startDate;
    DateTime? endDate;
    bool dateRangeValid = true;

    Future<void> pickDate({required bool isStart}) async {
      final initialDate = isStart ? startDate ?? DateTime.now() : endDate ?? DateTime.now();
      final firstDate = DateTime(DateTime.now().year - 5);
      final lastDate = DateTime.now();

      final picked = await showDatePicker(
        context: context,
        initialDate: initialDate,
        firstDate: firstDate,
        lastDate: lastDate,
      );

      if (picked == null) return;

      if (isStart) {
        startDate = picked;
        startDateController.text = _formatDate(picked);
        if (endDate != null && picked.isAfter(endDate!)) {
          dateRangeValid = false;
        } else {
          dateRangeValid = endDate != null;
        }
      } else {
        endDate = picked;
        endDateController.text = _formatDate(picked);
        if (startDate != null && picked.isBefore(startDate!)) {
          dateRangeValid = false;
        } else {
          dateRangeValid = startDate != null;
        }
      }
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
              final plateText = plateController.text.trim().toUpperCase();
              final plateValid =
                  plateText.isNotEmpty && _isValidPlate(plateText);

              final readyToSubmit =
                  plateValid && startDate != null && endDate != null && dateRangeValid;

              return Padding(
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
                    DropdownButtonFormField<String>(
                      decoration: const InputDecoration(
                        labelText: 'Status da multa',
                      ),
                      value: selectedStatus,
                      items: statusOptions
                          .map(
                            (status) => DropdownMenuItem(
                              value: status,
                              child: Text(status),
                            ),
                          )
                          .toList(),
                      onChanged: (value) {
                        setState(() {
                          selectedStatus = value;
                        });
                      },
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: GestureDetector(
                            onTap: () async {
                              FocusManager.instance.primaryFocus?.unfocus();
                              await pickDate(isStart: true);
                              setState(() {});
                            },
                            child: AbsorbPointer(
                              child: TextField(
                                controller: startDateController,
                                readOnly: true,
                                decoration: InputDecoration(
                                  labelText: 'Data inicial',
                                  errorText: dateRangeValid ? null : '',
                                ),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: GestureDetector(
                            onTap: () async {
                              FocusManager.instance.primaryFocus?.unfocus();
                              await pickDate(isStart: false);
                              setState(() {});
                            },
                            child: AbsorbPointer(
                              child: TextField(
                                controller: endDateController,
                                readOnly: true,
                                decoration: InputDecoration(
                                  labelText: 'Data final',
                                  errorText: dateRangeValid ? null : '',
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    if (!dateRangeValid)
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Text(
                          'A data final deve ser posterior à data inicial.',
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.error,
                            fontSize: 12,
                          ),
                        ),
                      ),
                    const SizedBox(height: 24),
                    FilledButton(
                      onPressed: readyToSubmit
                          ? () {
                              FocusManager.instance.primaryFocus?.unfocus();
                              Navigator.of(dialogContext).pop(
                                _RenainfRequest(
                                  plate: plateText,
                                  status: selectedStatus ?? 'Todas',
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
    });
    return result;
  }

  Future<_BloqueiosAtivosRequest?> _showBloqueiosAtivosDialog() async {
    final plateController = TextEditingController();
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

              final plateText = plateController.text.trim().toUpperCase();
              final chassiText = chassiController.text.trim().toUpperCase();

              final plateValid =
                  plateText.isNotEmpty && _isValidPlate(plateText);
              final chassiValid =
                  chassiText.isNotEmpty && _isValidChassi(chassiText);
              final captchaValid =
                  captchaController.text.trim().isNotEmpty;
              final isValid = (plateValid || chassiValid) &&
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
                                    captcha: captchaController.text
                                        .trim()
                                        .toUpperCase(),
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
      plateController.dispose();
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
    _currentUser = _authService.session?.user;
    _loadCurrentUser();
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

class _SimplePlateChassiRequest {
  const _SimplePlateChassiRequest({
    required this.plate,
    required this.chassi,
  });

  final String plate;
  final String chassi;
}

class _RenainfRequest {
  const _RenainfRequest({
    required this.plate,
    required this.status,
    required this.startDate,
    required this.endDate,
  });

  final String plate;
  final String status;
  final DateTime startDate;
  final DateTime endDate;
}

class _BloqueiosAtivosRequest {
  const _BloqueiosAtivosRequest({
    required this.origin,
    required this.captcha,
    this.plate,
    this.chassi,
  });

  final String origin;
  final String captcha;
  final String? plate;
  final String? chassi;
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
  
