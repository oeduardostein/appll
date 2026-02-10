import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:path_provider/path_provider.dart';
import 'package:universal_io/io.dart' as io;

import 'package:frontend_app/models/pesquisa_models.dart';
import 'package:frontend_app/services/auth_service.dart';
import 'package:frontend_app/services/base_estadual_service.dart';
import 'package:frontend_app/services/bin_service.dart';
import 'package:frontend_app/services/renainf_service.dart';
import 'package:frontend_app/services/gravame_service.dart';
import 'package:frontend_app/services/ficha_cadastral_service.dart';
import 'package:frontend_app/services/pesquisa_service.dart';
import 'package:frontend_app/ui/widgets/app_error_dialog.dart';
import 'package:frontend_app/ui/widgets/app_notice_dialog.dart';
import 'package:frontend_app/services/file_opener_service.dart';
import 'package:frontend_app/ui/formatters/plate_input_formatter.dart';

import '../base_state/base_estadual_page.dart';
import '../base_state/base_outros_estados_page.dart';
import '../bin/bin_result_page.dart';
import '../bloqueios/bloqueios_page.dart';
import '../ecrv/ecrv_process_page.dart';
import '../fines/renainf_page.dart';
import '../gravame/gravame_page.dart';
import '../shared/loading_dialog.dart';
import '../atpv/atpv_options_page.dart';
import '../auth/login_page.dart';
import '../profile/profile_page.dart';
import 'home_models.dart';
import 'widgets/home_action_card.dart';
import 'widgets/home_header.dart';
import 'widgets/recent_vehicle_card.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key, this.authService, this.pesquisaService});

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
  final _fichaCadastralService = FichaCadastralService();
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
  Set<String> _allowedPermissionSlugs = {};
  bool _isLoadingPermissions = false;
  String? _permissionsError;

  static final List<HomeAction> _actions = [
    HomeAction(
      icon: Icons.search,
      title: 'Pesquisas',
      description:
          'Base estadual, BIN, outros Estados, RENAINF, Gravame e bloqueios ativos.',
      subActions: [
        HomeSubAction(
          icon: Icons.location_on_outlined,
          label: 'Base estadual',
          permissionSlug: 'pesquisa_base_estadual',
        ),
        HomeSubAction(
          icon: Icons.public_outlined,
          label: 'Base Outros Estados',
          permissionSlug: 'pesquisa_base_outros_estados',
        ),
        HomeSubAction(
          icon: Icons.credit_card,
          label: 'BIN',
          permissionSlug: 'pesquisa_bin',
        ),
        HomeSubAction(
          icon: Icons.assignment_outlined,
          label: 'Gravame',
          permissionSlug: 'pesquisa_gravame',
        ),
        HomeSubAction(
          icon: Icons.directions_car_outlined,
          label: 'Renainf',
          permissionSlug: 'pesquisa_renainf',
        ),
        HomeSubAction(
          icon: Icons.lock_outline,
          label: 'Bloqueios Ativos',
          permissionSlug: 'pesquisa_bloqueios_ativos',
        ),
        HomeSubAction(
          icon: Icons.timeline_outlined,
          label: 'Andamento do processo e-CRV',
          permissionSlug: 'pesquisa_andamento_processo',
        ),
      ],
    ),
    HomeAction(
      icon: Icons.description_outlined,
      title: 'CRLV-e',
      description: 'Emissão do CRLV digital',
      permissionSlug: 'crlv',
    ),
    HomeAction(
      icon: Icons.assignment_turned_in_outlined,
      title: 'Emissão da ATPV-e',
      description: 'Preencher a autorização para transferência',
      permissionSlug: 'atpv',
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

  String? _expandedActionKey;

  void _toggleExpanded(String key) {
    setState(() {
      _expandedActionKey = _expandedActionKey == key ? null : key;
    });
  }

  bool _ensurePermission(String? slug) {
    if (slug == null) return true;
    if (_allowedPermissionSlugs.contains(slug)) {
      return true;
    }
    _showErrorMessage(
      'Você não tem permissão para acessar esta funcionalidade.',
    );
    return false;
  }

  List<HomeAction> _buildVisibleActions() {
    final allowed = _allowedPermissionSlugs;
    final result = <HomeAction>[];

    for (final action in _actions) {
      if (action.subActions.isEmpty) {
        final slug = action.permissionSlug;
        if (slug == null || allowed.contains(slug)) {
          result.add(action);
        }
        continue;
      }

      final filteredSubs = action.subActions
          .where(
            (sub) =>
                sub.permissionSlug == null ||
                allowed.contains(sub.permissionSlug!),
          )
          .toList();

      if (filteredSubs.isEmpty) continue;
      result.add(action.copyWith(subActions: filteredSubs));
    }

    return result;
  }

  Future<void> _handleSubActionTap(
    HomeAction action,
    HomeSubAction subAction,
  ) async {
    if (!_ensurePermission(subAction.permissionSlug)) {
      return;
    }

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

    final autoCompleted = await _executeBaseEstadualQuery(
      placa: query.placa,
      renavam: query.renavam,
      chassi: query.chassi,
      autoSolve: true,
    );

    if (!mounted || autoCompleted) return;

    _showErrorMessage(
      'Não foi possível resolver o captcha automaticamente. Informe-o manualmente.',
    );

    final manualQuery = await _showVehicleLookupDialog(
      title: 'Consulta base estadual',
      fetchCaptcha: () => _baseEstadualService.fetchCaptcha(),
      captchaErrorResolver: _mapBaseEstadualCaptchaError,
      includeRenavam: false,
      includeChassi: true,
      chassiValidator: _isValidChassi,
      requireCaptcha: true,
      initialPlate: query.placa,
      initialRenavam: query.renavam,
      initialChassi: query.chassi,
    );

    if (manualQuery == null || !mounted) {
      return;
    }

    await _executeBaseEstadualQuery(
      placa: manualQuery.placa,
      renavam: manualQuery.renavam,
      chassi: manualQuery.chassi,
      autoSolve: false,
      captchaOverride: manualQuery.captcha,
    );
  }

  Future<_BaseEstadualQuery?> _showBaseEstadualDialog() {
    return _showVehicleLookupDialog(
      title: 'Consulta base estadual',
      fetchCaptcha: () => _baseEstadualService.fetchCaptcha(),
      captchaErrorResolver: _mapBaseEstadualCaptchaError,
      includeRenavam: false,
      includeChassi: true,
      chassiValidator: _isValidChassi,
      requireCaptcha: false,
    );
  }

  Future<_BaseEstadualQuery?> _showBinDialog({
    bool requireCaptcha = true,
    String? initialPlate,
    String? initialRenavam,
    String? initialChassi,
  }) {
    return _showVehicleLookupDialog(
      title: 'Pesquisa BIN',
      fetchCaptcha: () => _binService.fetchCaptcha(),
      captchaErrorResolver: _mapBinCaptchaError,
      requireCaptcha: requireCaptcha,
      initialPlate: initialPlate,
      initialRenavam: initialRenavam,
      includeChassi: true,
      chassiValidator: _isValidChassi,
      initialChassi: initialChassi,
    );
  }

  Future<_GravameRequest?> _showGravameDialog({
    bool requireCaptcha = true,
    String? initialPlate,
    String? initialChassi,
  }) {
    return showDialog<_GravameRequest>(
      context: context,
      barrierDismissible: false,
      builder: (_) => _GravameDialog(
        fetchCaptcha: () => _baseEstadualService.fetchCaptcha(),
        captchaErrorResolver: _mapBaseEstadualCaptchaError,
        plateValidator: _isValidPlate,
        chassiValidator: _isValidChassi,
        requireCaptcha: requireCaptcha,
        initialPlate: initialPlate,
        initialChassi: initialChassi,
      ),
    );
  }

  Future<_FichaConsultaRequest?> _showFichaCadastralConsultaDialog({
    bool requireCaptcha = true,
    String? initialPlate,
  }) {
    return showDialog<_FichaConsultaRequest>(
      context: context,
      barrierDismissible: false,
      builder: (_) => _FichaConsultaDialog(
        fetchCaptcha: () => _baseEstadualService.fetchCaptcha(),
        captchaErrorResolver: _mapBaseEstadualCaptchaError,
        plateValidator: _isValidPlate,
        requireCaptcha: requireCaptcha,
        initialPlate: initialPlate,
      ),
    );
  }

  Future<_FichaAndamentoRequest?> _showFichaAndamentoDialog({
    required String numeroFicha,
    required String anoFicha,
  }) {
    return showDialog<_FichaAndamentoRequest>(
      context: context,
      barrierDismissible: false,
      builder: (_) => _FichaAndamentoDialog(
        numeroFicha: numeroFicha,
        anoFicha: anoFicha,
        fetchCaptcha: () => _baseEstadualService.fetchCaptcha(),
        captchaErrorResolver: _mapBaseEstadualCaptchaError,
      ),
    );
  }

  Future<_BaseEstadualQuery?> _showVehicleLookupDialog({
    required String title,
    required Future<String> Function() fetchCaptcha,
    String Function(Object error)? captchaErrorResolver,
    bool includeRenavam = true,
    bool requireCaptcha = true,
    bool includeChassi = false,
    bool Function(String value)? chassiValidator,
    String? initialPlate,
    String? initialRenavam,
    String? initialChassi,
  }) {
    return showDialog<_BaseEstadualQuery>(
      context: context,
      barrierDismissible: false,
      builder: (_) => _VehicleLookupDialog(
        title: title,
        fetchCaptcha: fetchCaptcha,
        captchaErrorResolver: captchaErrorResolver,
        plateValidator: _isValidPlate,
        includeRenavam: includeRenavam,
        renavamValidator: _isValidRenavam,
        includeChassi: includeChassi,
        chassiValidator: chassiValidator,
        requireCaptcha: requireCaptcha,
        initialPlate: initialPlate,
        initialRenavam: initialRenavam,
        initialChassi: initialChassi,
      ),
    );
  }

  Future<bool> _executeBaseEstadualQuery({
    required String placa,
    required String renavam,
    String? chassi,
    required bool autoSolve,
    String? captchaOverride,
  }) async {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      String captcha;
      if (autoSolve) {
        try {
          captcha = await _baseEstadualService.solveCaptcha();
        } on BaseEstadualException {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        } catch (_) {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        }
      } else {
        captcha = captchaOverride ?? '';
      }

      if (!autoSolve && captcha.isEmpty) {
        throw BaseEstadualException('Informe o captcha para continuar.');
      }

      final result = await _baseEstadualService.consultar(
        placa: PlateUtils.sanitize(placa),
        renavam: renavam,
        chassi: chassi,
        captcha: captcha,
      );

      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return true;

      final resultMessage = _extractResultMessage(result);
      if (resultMessage != null) {
        _showNoticeMessage(resultMessage, title: 'Consulta sem dados');
        return true;
      }

      _registerPesquisa(
        nome: 'Base estadual',
        placa: placa,
        renavam: renavam,
        chassi: chassi,
        opcaoPesquisa: (chassi != null && chassi.trim().isNotEmpty) ? 'chassi' : 'placa',
      );

      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) =>
              BaseEstadualPage(
                placa: placa,
                renavam: renavam,
                chassi: chassi,
                payload: result,
              ),
        ),
      );

      return true;
    } on BaseEstadualException catch (e) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();

      _showErrorMessage(e.message);
      return true;
    } catch (error) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(
        _resolveErrorMessage(
          error,
          fallback: 'Não foi possível consultar a base estadual.',
        ),
      );
      return true;
    }
  }

  Future<bool> _executeBaseOutrosEstadosQuery({
    required String chassi,
    required String uf,
    required bool autoSolve,
    String? captchaOverride,
  }) async {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      String captcha;
      if (autoSolve) {
        try {
          captcha = await _baseEstadualService.solveCaptcha();
        } on BaseEstadualException {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        } catch (_) {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        }
      } else {
        captcha = captchaOverride ?? '';
      }

      if (!autoSolve && captcha.isEmpty) {
        throw BaseEstadualException('Informe o captcha para continuar.');
      }

      final result = await _baseEstadualService.consultarOutrosEstados(
        chassi: chassi,
        uf: uf,
        captcha: captcha,
      );

      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return true;

      final resultMessage = _extractResultMessage(result);
      if (resultMessage != null) {
        _showNoticeMessage(resultMessage, title: 'Consulta sem dados');
        return true;
      }

      _registerPesquisa(
        nome: 'Base outros estados',
        chassi: chassi,
        opcaoPesquisa: uf,
      );

      final htmlResponse = result['html'];
      if (htmlResponse is String && htmlResponse.isNotEmpty) {
        await _showBaseOutrosEstadosHtmlDialog(
          chassi: chassi,
          html: htmlResponse,
        );
      } else {
        if (!mounted) return true;
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) =>
                BaseOutrosEstadosPage(chassi: chassi, payload: result),
          ),
        );
      }

      return true;
    } on BaseEstadualException catch (e) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();

      _showErrorMessage(e.message);
      return true;
    } catch (error) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(
        _resolveErrorMessage(
          error,
          fallback: 'Não foi possível consultar a base de outros estados.',
        ),
      );
      return true;
    }
  }

  Future<bool> _executeBinQuery({
    String? placa,
    String? renavam,
    String? chassi,
    String? opcao,
    required bool autoSolve,
    String? captchaOverride,
  }) async {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      String captcha;
      if (autoSolve) {
        try {
          captcha = await _baseEstadualService.solveCaptcha();
        } on BaseEstadualException {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        } catch (_) {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        }
      } else {
        captcha = captchaOverride ?? '';
      }

      if (!autoSolve && captcha.isEmpty) {
        throw BinException('Informe o captcha para continuar.');
      }

      final searchOption = opcao ??
          ((chassi != null && chassi.trim().isNotEmpty) ? '1' : '2');

      final result = await _binService.consultar(
        placa: placa,
        renavam: renavam,
        chassi: chassi,
        opcao: searchOption,
        captcha: captcha,
      );

      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return true;

      final resultMessage = _extractResultMessage(result);
      if (resultMessage != null) {
        _showNoticeMessage(resultMessage, title: 'Consulta sem dados');
        return true;
      }

      _registerPesquisa(
        nome: 'BIN',
        placa: placa,
        renavam: renavam,
        chassi: chassi,
        opcaoPesquisa: searchOption,
      );

      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) =>
              BinResultPage(
                placa: placa ?? '',
                renavam: renavam ?? '',
                chassi: chassi,
                payload: result,
              ),
        ),
      );

      return true;
    } on BaseEstadualException catch (e) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();

      _showErrorMessage(e.message);
      return true;
    } on BinException catch (e) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
      return true;
    } catch (error) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(
        _resolveErrorMessage(
          error,
          fallback: 'Não foi possível concluir a pesquisa BIN.',
        ),
      );
      return true;
    }
  }

  Future<bool> _executeGravameQuery({
    required _GravameRequest request,
    required bool autoSolve,
    String? captchaOverride,
  }) async {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      final option = request.option;
      final placa = option == 'placa' ? (request.plate ?? '').trim().toUpperCase() : '';
      final chassi =
          option == 'chassi' ? (request.chassi ?? '').trim().toUpperCase() : '';

      String captcha;
      if (autoSolve) {
        try {
          captcha = await _baseEstadualService.solveCaptcha();
        } on BaseEstadualException {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        } catch (_) {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        }
      } else {
        captcha = captchaOverride ?? '';
      }

      if (!autoSolve && captcha.isEmpty) {
        throw GravameException('Informe o captcha para continuar.');
      }

      final normalizedCaptcha = captcha.trim().toUpperCase();

      final result = await _gravameService.consultar(
        placa: placa.isEmpty ? null : PlateUtils.sanitize(placa),
        chassi: chassi.isEmpty ? null : chassi,
        captcha: normalizedCaptcha,
      );

      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return true;

      final resultMessage = _extractResultMessage(result);
      if (resultMessage != null) {
        _showNoticeMessage(resultMessage, title: 'Consulta sem dados');
        return true;
      }

      _registerPesquisa(
        nome: 'Gravame',
        placa: placa.isEmpty ? null : placa,
        chassi: chassi.isEmpty ? null : chassi,
        opcaoPesquisa: option,
      );

      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => GravamePage(
            placa: placa.isEmpty ? null : placa,
            chassi: chassi.isEmpty ? null : chassi,
            renavam: null,
            uf: null,
            payload: result,
          ),
        ),
      );

      return true;
    } on BaseEstadualException catch (e) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();

      _showErrorMessage(e.message);
      return true;
    } on GravameException catch (e) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
      return true;
    } catch (error) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(
        _resolveErrorMessage(
          error,
          fallback: 'Não foi possível consultar o gravame.',
        ),
      );
      return true;
    }
  }

  Future<bool> _executeRenainfQuery({
    required _RenainfRequest request,
    required bool autoSolve,
    String? captchaOverride,
  }) async {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      String captcha;
      if (autoSolve) {
        try {
          captcha = await _baseEstadualService.solveCaptcha();
        } on BaseEstadualException {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        } catch (_) {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        }
      } else {
        captcha = captchaOverride ?? request.captcha;
      }

      if (!autoSolve && (captcha.isEmpty)) {
        throw RenainfException('Informe o captcha para continuar.');
      }

      final normalizedCaptcha = captcha.trim().toUpperCase();

      final result = await _renainfService.consultar(
        plate: request.plate,
        statusCode: request.statusCode,
        statusLabel: request.statusLabel,
        uf: request.uf,
        startDate: request.startDate,
        endDate: request.endDate,
        captcha: normalizedCaptcha,
      );

      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return true;

      _registerPesquisa(
        nome: 'RENAINF',
        placa: request.plate,
        opcaoPesquisa: '${request.statusCode}-${request.statusLabel}',
      );

      Navigator.of(
        context,
      ).push(MaterialPageRoute(builder: (_) => RenainfPage(result: result)));

      return true;
    } on BaseEstadualException catch (e) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();

      _showErrorMessage(e.message);
      return true;
    } on RenainfException catch (e) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
      return true;
    } catch (error) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(
        _resolveErrorMessage(
          error,
          fallback: 'Não foi possível consultar o RENAINF.',
        ),
      );
      return true;
    }
  }

  Future<bool> _executeBloqueiosAtivosQuery({
    required String origin,
    required String chassi,
    required String opcaoPesquisa,
    required bool autoSolve,
    String? captchaOverride,
  }) async {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      String captcha;
      if (autoSolve) {
        try {
          captcha = await _baseEstadualService.solveCaptcha();
        } on BaseEstadualException {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        } catch (_) {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        }
      } else {
        captcha = captchaOverride ?? '';
      }

      if (!autoSolve && captcha.isEmpty) {
        throw BaseEstadualException('Informe o captcha para continuar.');
      }

      final result = await _baseEstadualService.consultarBloqueiosAtivos(
        origin: origin,
        captcha: captcha.trim().toUpperCase(),
        chassi: chassi,
        opcaoPesquisa: opcaoPesquisa,
      );

      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return true;

      final bloqueiosValidationMessage = _validateBloqueiosAtivosResult(result);
      if (bloqueiosValidationMessage != null) {
        _showNoticeMessage(
          bloqueiosValidationMessage,
          title: 'Consulta sem dados',
        );
        return true;
      }

      _registerPesquisa(
        nome: 'Bloqueios ativos',
        chassi: chassi,
        opcaoPesquisa: opcaoPesquisa,
      );

      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) =>
              BloqueiosPage(origin: origin, chassi: chassi, payload: result),
        ),
      );

      return true;
    } on BaseEstadualException catch (e) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      if (e.statusCode == 404) {
        _showNoticeMessage(e.message, title: 'Consulta sem dados');
      } else {
        _showErrorMessage(e.message);
      }
      return true;
    } catch (error) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(
        _resolveErrorMessage(
          error,
          fallback: 'Não foi possível consultar os bloqueios ativos.',
        ),
      );
      return true;
    }
  }

  String? _validateBloqueiosAtivosResult(Map<String, dynamic> result) {
    final message = _extractResultMessage(result);
    if (message != null) return message;

    final consulta = result['consulta'];
    final renajud = result['renajud'];

    if (consulta is! Map) {
      return 'A API retornou um formato inesperado para esta consulta.';
    }

    final chassi = consulta['chassi']?.toString().trim();
    final placa = consulta['placa']?.toString().trim();
    final municipioPlaca = consulta['municipio_placa']?.toString().trim();
    final quantidade = consulta['quantidade'];

    String? ocorrenciasEncontradas;
    String? ocorrenciasExibidas;
    if (quantidade is Map) {
      ocorrenciasEncontradas =
          quantidade['ocorrencias_encontradas']?.toString().trim();
      ocorrenciasExibidas =
          quantidade['ocorrencias_exibidas']?.toString().trim();
    }

    final hasConsultaData = _hasNonEmptyValue(chassi) ||
        _hasNonEmptyValue(placa) ||
        _hasNonEmptyValue(municipioPlaca) ||
        _hasNonEmptyValue(ocorrenciasEncontradas) ||
        _hasNonEmptyValue(ocorrenciasExibidas);

    final hasRenajudData = _hasAnyNonEmptyNestedValue(renajud);

    if (hasConsultaData || hasRenajudData) {
      return null;
    }

    return 'A API não retornou nenhum dado para esta consulta. '
        'Isso pode significar que não existe registro para as especificações informadas '
        'ou que houve algum erro na fonte. Tente novamente em alguns instantes.';
  }

  bool _hasNonEmptyValue(String? value) {
    if (value == null) return false;
    final trimmed = value.trim();
    if (trimmed.isEmpty) return false;
    if (trimmed == '—' || trimmed == '-') return false;
    return true;
  }

  bool _hasAnyNonEmptyNestedValue(dynamic value) {
    if (value == null) return false;
    if (value is String) return _hasNonEmptyValue(value);
    if (value is num || value is bool) return true;
    if (value is List) {
      for (final item in value) {
        if (_hasAnyNonEmptyNestedValue(item)) return true;
      }
      return false;
    }
    if (value is Map) {
      for (final entry in value.entries) {
        if (_hasAnyNonEmptyNestedValue(entry.value)) return true;
      }
      return false;
    }
    return _hasNonEmptyValue(value.toString());
  }

  String? _extractResultMessage(Map<String, dynamic> result) {
    final dynamic raw =
        result['message'] ?? result['error'] ?? result['status'] ?? result['detail'];
    if (raw is String && raw.trim().isNotEmpty) {
      return raw.trim();
    }
    return null;
  }

  Future<bool> _executeCrlvEmission({
    required _CrlvEmissionRequest request,
    required bool autoSolve,
    String? captchaOverride,
  }) async {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      String captcha;
      if (autoSolve) {
        try {
          captcha = await _baseEstadualService.solveCaptcha();
        } on BaseEstadualException {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        } catch (_) {
          if (!mounted) return true;
          Navigator.of(context, rootNavigator: true).pop();
          return false;
        }
      } else {
        captcha = captchaOverride ?? request.captcha;
      }

      final normalizedCaptcha = captcha.trim().toUpperCase();
      if (!autoSolve && normalizedCaptcha.isEmpty) {
        throw BaseEstadualException('Informe o captcha para continuar.');
      }

      final rawDigits = request.document.replaceAll(RegExp(r'[^0-9]'), '');
      final isCpf = rawDigits.length <= 11;
      final formattedCpf = _formatCpf(rawDigits);
      final formattedCnpj = _formatCnpj(rawDigits);
      final cpf = isCpf ? (formattedCpf ?? request.document) : '';
      final cnpj = isCpf ? '' : (formattedCnpj ?? request.document);
      final opcao = isCpf ? '1' : '2';

      final pdfBytes = await _baseEstadualService.emitirCrlv(
        placa: request.plate,
        renavam: request.renavam,
        cpf: cpf,
        cnpj: cnpj,
        captchaResponse: normalizedCaptcha,
        opcaoPesquisa: opcao,
      );

      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return true;

      _registerPesquisa(
        nome: 'Emissão do CRLV-e',
        placa: request.plate,
        renavam: request.renavam,
        opcaoPesquisa: opcao,
      );

      final pdfResult = await _saveAndOpenPdf(pdfBytes, request.plate);
      if (!mounted) return true;
      if (pdfResult.opened) {
        _showSuccessMessage('CRLV-e emitido com sucesso.');
      } else {
        final fallbackMessage = pdfResult.path != null
            ? 'CRLV-e emitido. Abra o arquivo manualmente em ${pdfResult.path}.'
            : kIsWeb
            ? 'CRLV-e emitido. Baixe o PDF utilizando o aplicativo móvel.'
            : 'CRLV-e emitido, mas não foi possível abrir o PDF automaticamente.';
        _showErrorMessage(fallbackMessage);
      }

      return true;
    } on BaseEstadualException catch (e) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();

      _showErrorMessage(e.message);
      return true;
    } catch (error) {
      if (!mounted) return true;
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(
        _resolveErrorMessage(
          error,
          fallback: 'Não foi possível emitir o CRLV-e.',
        ),
      );
      return true;
    }
  }

  Future<_CaptchaOperationResult<Map<String, dynamic>>> _executeFichaConsulta({
    required String placa,
    required bool autoSolve,
    String? captchaOverride,
  }) async {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      String captcha;
      if (autoSolve) {
        try {
          captcha = await _baseEstadualService.solveCaptcha();
        } on BaseEstadualException {
          if (!mounted) return const _CaptchaOperationResult.failure();
          Navigator.of(context, rootNavigator: true).pop();
          return const _CaptchaOperationResult<Map<String, dynamic>>.manual();
        } catch (_) {
          if (!mounted) return const _CaptchaOperationResult.failure();
          Navigator.of(context, rootNavigator: true).pop();
          return const _CaptchaOperationResult<Map<String, dynamic>>.manual();
        }
      } else {
        captcha = captchaOverride ?? '';
      }

      if (!autoSolve && captcha.isEmpty) {
        throw FichaCadastralException('Informe o captcha para continuar.');
      }

      final result = await _fichaCadastralService.consultarFicha(
        placa: PlateUtils.sanitize(placa),
        captcha: captcha.trim().toUpperCase(),
      );

      if (!mounted) return const _CaptchaOperationResult.failure();
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return const _CaptchaOperationResult.failure();

      return _CaptchaOperationResult<Map<String, dynamic>>.success(result);
    } on BaseEstadualException catch (e) {
      if (!mounted) return const _CaptchaOperationResult.failure();
      Navigator.of(context, rootNavigator: true).pop();

      _showErrorMessage(e.message);
      return const _CaptchaOperationResult<Map<String, dynamic>>.failure();
    } on FichaCadastralException catch (e) {
      if (!mounted) return const _CaptchaOperationResult.failure();
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
      return const _CaptchaOperationResult<Map<String, dynamic>>.failure();
    } catch (error) {
      if (!mounted) return const _CaptchaOperationResult.failure();
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(
        _resolveErrorMessage(
          error,
          fallback: 'Não foi possível consultar a ficha cadastral.',
        ),
      );
      return const _CaptchaOperationResult<Map<String, dynamic>>.failure();
    }
  }

  Future<_CaptchaOperationResult<Map<String, dynamic>>> _executeFichaAndamento({
    required String numeroFicha,
    required String anoFicha,
    required String placa,
    required bool autoSolve,
    String? captchaOverride,
  }) async {
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      String captcha;
      if (autoSolve) {
        try {
          captcha = await _baseEstadualService.solveCaptcha();
        } on BaseEstadualException {
          if (!mounted) return const _CaptchaOperationResult.failure();
          Navigator.of(context, rootNavigator: true).pop();
          return const _CaptchaOperationResult<Map<String, dynamic>>.manual();
        } catch (_) {
          if (!mounted) return const _CaptchaOperationResult.failure();
          Navigator.of(context, rootNavigator: true).pop();
          return const _CaptchaOperationResult<Map<String, dynamic>>.manual();
        }
      } else {
        captcha = captchaOverride ?? '';
      }

      if (!autoSolve && captcha.isEmpty) {
        throw FichaCadastralException('Informe o captcha para continuar.');
      }

      final result = await _fichaCadastralService.consultarAndamento(
        numeroFicha: numeroFicha,
        anoFicha: anoFicha,
        captcha: captcha.trim().toUpperCase(),
        placa: PlateUtils.sanitize(placa),
      );

      if (!mounted) return const _CaptchaOperationResult.failure();
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return const _CaptchaOperationResult.failure();

      return _CaptchaOperationResult<Map<String, dynamic>>.success(result);
    } on BaseEstadualException catch (e) {
      if (!mounted) return const _CaptchaOperationResult.failure();
      Navigator.of(context, rootNavigator: true).pop();

      _showErrorMessage(e.message);
      return const _CaptchaOperationResult<Map<String, dynamic>>.failure();
    } on FichaCadastralException catch (e) {
      if (!mounted) return const _CaptchaOperationResult.failure();
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(e.message);
      return const _CaptchaOperationResult<Map<String, dynamic>>.failure();
    } catch (error) {
      if (!mounted) return const _CaptchaOperationResult.failure();
      Navigator.of(context, rootNavigator: true).pop();
      _showErrorMessage(
        _resolveErrorMessage(
          error,
          fallback: 'Não foi possível consultar o andamento do processo.',
        ),
      );
      return const _CaptchaOperationResult<Map<String, dynamic>>.failure();
    }
  }

  Future<void> _handleBaseOutrosEstadosFlow() async {
    final query = await _showBaseOutrosEstadosDialog(requireCaptcha: false);
    if (query == null || !mounted) return;

    final autoCompleted = await _executeBaseOutrosEstadosQuery(
      chassi: query.chassi,
      uf: query.uf,
      autoSolve: true,
    );

    if (!mounted || autoCompleted) return;

    _showErrorMessage(
      'Não foi possível resolver o captcha automaticamente. Informe-o manualmente.',
    );

    final manualQuery = await _showBaseOutrosEstadosDialog(
      requireCaptcha: true,
      initialChassi: query.chassi,
      initialUf: query.uf,
    );

    if (manualQuery == null || !mounted) {
      return;
    }

    await _executeBaseOutrosEstadosQuery(
      chassi: manualQuery.chassi,
      uf: manualQuery.uf,
      autoSolve: false,
      captchaOverride: manualQuery.captcha,
    );
  }

  Future<void> _handleBinFlow() async {
    final query = await _showBinDialog(requireCaptcha: false);
    if (query == null || !mounted) return;

    final autoCompleted = await _executeBinQuery(
      placa: query.placa,
      renavam: query.renavam,
      chassi: query.chassi,
      opcao: query.opcao,
      autoSolve: true,
    );

    if (!mounted || autoCompleted) return;

    _showErrorMessage(
      'Não foi possível resolver o captcha automaticamente. Informe-o manualmente.',
    );

    final manualQuery = await _showBinDialog(
      requireCaptcha: true,
      initialPlate: query.placa,
      initialRenavam: query.renavam,
      initialChassi: query.chassi,
    );

    if (manualQuery == null || !mounted) {
      return;
    }

    await _executeBinQuery(
      placa: manualQuery.placa,
      renavam: manualQuery.renavam,
      chassi: manualQuery.chassi,
      opcao: manualQuery.opcao,
      autoSolve: false,
      captchaOverride: manualQuery.captcha,
    );
  }

  Future<void> _handleRenainfFlow() async {
    final request = await _showRenainfDialog(requireCaptcha: false);
    if (request == null || !mounted) return;

    final autoCompleted = await _executeRenainfQuery(
      request: request,
      autoSolve: true,
    );

    if (!mounted || autoCompleted) return;

    _showErrorMessage(
      'Não foi possível resolver o captcha automaticamente. Informe-o manualmente.',
    );

    final manualRequest = await _showRenainfDialog(
      requireCaptcha: true,
      initialRequest: request,
    );

    if (manualRequest == null || !mounted) return;

    await _executeRenainfQuery(
      request: manualRequest,
      autoSolve: false,
      captchaOverride: manualRequest.captcha,
    );
  }

  Future<void> _handleEcrvProcessFlow() async {
    final initialConsultaRequest = await _showFichaCadastralConsultaDialog(
      requireCaptcha: false,
    );
    if (initialConsultaRequest == null || !mounted) return;

    var effectiveConsultaRequest = initialConsultaRequest;

    final consultaAttempt = await _executeFichaConsulta(
      placa: effectiveConsultaRequest.plate,
      autoSolve: true,
    );
    if (!mounted) return;

    Map<String, dynamic>? fichaResult;

    if (consultaAttempt.requiresManual) {
      _showErrorMessage(
        'Não foi possível resolver o captcha automaticamente. Informe-o manualmente.',
      );
      final manualConsulta = await _showFichaCadastralConsultaDialog(
        requireCaptcha: true,
        initialPlate: effectiveConsultaRequest.plate,
      );
      if (manualConsulta == null || !mounted) return;
      effectiveConsultaRequest = manualConsulta;
      final manualResult = await _executeFichaConsulta(
        placa: manualConsulta.plate,
        autoSolve: false,
        captchaOverride: manualConsulta.captcha,
      );
      if (!mounted) return;
      fichaResult = manualResult.data;
      if (fichaResult == null) {
        return;
      }
    } else {
      fichaResult = consultaAttempt.data;
      if (fichaResult == null) {
        return;
      }
    }

    final fichaPayload = _asMap(fichaResult['payload']);
    final fichaNormalized = _asMap(
      fichaPayload?['normalized'],
    )?['dados_da_ficha_cadastral'];
    final numeroFicha = fichaNormalized?['n_da_ficha']?.toString();
    final anoFicha = fichaNormalized?['ano_ficha']?.toString();
    final renavamFicha = fichaNormalized?['renavam']?.toString();
    final chassiFicha = fichaNormalized?['chassi']?.toString();

    if (numeroFicha == null ||
        numeroFicha.isEmpty ||
        anoFicha == null ||
        anoFicha.isEmpty) {
      _showErrorMessage(
        'Consulta retornou sem número/ano da ficha. Verifique os dados informados.',
      );
      return;
    }

    final andamentoAttempt = await _executeFichaAndamento(
      numeroFicha: numeroFicha,
      anoFicha: anoFicha,
      placa: effectiveConsultaRequest.plate,
      autoSolve: true,
    );
    if (!mounted) return;

    Map<String, dynamic>? andamentoResult;

    if (andamentoAttempt.requiresManual) {
      _showErrorMessage(
        'Não foi possível resolver o captcha automaticamente. Informe-o manualmente.',
      );
      final andamentoRequest = await _showFichaAndamentoDialog(
        numeroFicha: numeroFicha,
        anoFicha: anoFicha,
      );
      if (andamentoRequest == null || !mounted) return;
      final manualAndamento = await _executeFichaAndamento(
        numeroFicha: andamentoRequest.numeroFicha,
        anoFicha: andamentoRequest.anoFicha,
        placa: effectiveConsultaRequest.plate,
        autoSolve: false,
        captchaOverride: andamentoRequest.captcha,
      );
      if (!mounted) return;
      andamentoResult = manualAndamento.data;
      if (andamentoResult == null) {
        return;
      }
    } else {
      andamentoResult = andamentoAttempt.data;
      if (andamentoResult == null) {
        return;
      }
    }

    _registerPesquisa(
      nome: 'Processo e-CRVsp',
      placa: effectiveConsultaRequest.plate,
      renavam: renavamFicha,
      chassi: chassiFicha,
    );

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => EcrvProcessPage(
          placa: effectiveConsultaRequest.plate,
          numeroFicha: numeroFicha,
          anoFicha: anoFicha,
          fichaPayload: fichaPayload ?? const {},
          andamentoPayload:
              _asMap(andamentoResult!['payload']) ?? andamentoResult,
        ),
      ),
    );
  }

  Future<void> _handleBloqueiosAtivosFlow() async {
    final request = await _showBloqueiosAtivosDialog(requireCaptcha: false);
    if (request == null || !mounted) return;

    final autoCompleted = await _executeBloqueiosAtivosQuery(
      origin: request.origin,
      chassi: request.chassi,
      opcaoPesquisa: request.opcaoPesquisa,
      autoSolve: true,
    );

    if (!mounted || autoCompleted) return;

    _showErrorMessage(
      'Não foi possível resolver o captcha automaticamente. Informe-o manualmente.',
    );

    final manualRequest = await _showBloqueiosAtivosDialog(
      requireCaptcha: true,
      initialRequest: request,
    );
    if (manualRequest == null || !mounted) return;

    await _executeBloqueiosAtivosQuery(
      origin: manualRequest.origin,
      chassi: manualRequest.chassi,
      opcaoPesquisa: manualRequest.opcaoPesquisa,
      autoSolve: false,
      captchaOverride: manualRequest.captcha,
    );
  }

  Future<void> _handlePrimaryActionTap(HomeAction action) async {
    if (!_ensurePermission(action.permissionSlug)) {
      return;
    }

    if (action.title == 'CRLV-e') {
      await _handleCrlvEmissionFlow();
    } else if (action.title == 'Emissão da ATPV-e') {
      await _handleAtpvEmissionFlow();
    }
  }

  Future<void> _handleCrlvEmissionFlow() async {
    final request = await _showCrlvEmissionDialog(requireCaptcha: false);
    if (request == null || !mounted) return;

    final autoCompleted = await _executeCrlvEmission(
      request: request,
      autoSolve: true,
    );

    if (!mounted || autoCompleted) return;

    _showErrorMessage(
      'Não foi possível resolver o captcha automaticamente. Informe-o manualmente.',
    );

    final manualRequest = await _showCrlvEmissionDialog(
      requireCaptcha: true,
      initialRequest: request,
    );
    if (manualRequest == null || !mounted) return;

    await _executeCrlvEmission(
      request: manualRequest,
      autoSolve: false,
      captchaOverride: manualRequest.captcha,
    );
  }

  Future<void> _handleAtpvEmissionFlow() async {
    if (!mounted) return;
    Navigator.of(
      context,
    ).push(MaterialPageRoute(builder: (_) => const AtpvOptionsPage()));
  }

  String? _formatCpf(String value) {
    final digits = value.replaceAll(RegExp(r'[^0-9]'), '');
    if (digits.length != 11) return null;
    return '${digits.substring(0, 3)}.${digits.substring(3, 6)}.${digits.substring(6, 9)}-${digits.substring(9)}';
  }

  String? _formatCnpj(String value) {
    final digits = value.replaceAll(RegExp(r'[^0-9]'), '');
    if (digits.length != 14) return null;
    return '${digits.substring(0, 2)}.${digits.substring(2, 5)}.${digits.substring(5, 8)}/${digits.substring(8, 12)}-${digits.substring(12)}';
  }

  Future<({bool opened, String? path})> _saveAndOpenPdf(
    Uint8List pdfBytes,
    String plate,
  ) async {
    if (kIsWeb) {
      return (opened: false, path: null);
    }

    try {
      final directory = await getTemporaryDirectory();
      final sanitizedPlate = plate
          .replaceAll(RegExp(r'[^A-Za-z0-9]'), '')
          .toUpperCase();
      final timestamp = DateTime.now().millisecondsSinceEpoch;
      final filename =
          'crlv_${sanitizedPlate.isEmpty ? 'veiculo' : sanitizedPlate}_$timestamp.pdf';
      final file = io.File('${directory.path}/$filename');

      await file.writeAsBytes(pdfBytes, flush: true);

      final opened = await FileOpenerService.openFile(
        file.path,
        mimeType: 'application/pdf',
      );
      return (opened: opened, path: file.path);
    } catch (_) {
      return (opened: false, path: null);
    }
  }

  Future<_CrlvEmissionRequest?> _showCrlvEmissionDialog({
    bool requireCaptcha = true,
    _CrlvEmissionRequest? initialRequest,
  }) async {
    final formKey = GlobalKey<FormState>();
    final plateController = TextEditingController(
      text: initialRequest?.plate ?? '',
    );
    PlateFormat? plateFormat = initialRequest?.plate?.trim().isNotEmpty ?? false
        ? PlateUtils.inferFormat(initialRequest!.plate)
        : null;
    if (plateFormat != null && plateController.text.trim().isNotEmpty) {
      plateController.text = PlateUtils.format(
        plateController.text,
        plateFormat,
      );
    }
    final renavamController = TextEditingController(
      text: initialRequest?.renavam ?? '',
    );
    final documentController = TextEditingController(
      text: initialRequest?.document ?? '',
    );
    final captchaController = TextEditingController();

    String? captchaBase64;
    String? captchaError;
    bool isLoadingCaptcha = false;
    bool initialized = false;

    Future<void> refreshCaptcha(StateSetter setState) async {
      if (!requireCaptcha) return;

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
                if (requireCaptcha) {
                  Future.microtask(() => refreshCaptcha(setState));
                }
              }

              Uint8List? captchaBytes;
              if (requireCaptcha &&
                  captchaBase64 != null &&
                  captchaBase64!.isNotEmpty) {
                try {
                  captchaBytes = base64Decode(captchaBase64!);
                } catch (_) {
                  captchaError ??= 'Captcha recebido em formato inválido.';
                }
              }

              final captchaWidgets = <Widget>[];
              if (requireCaptcha) {
                captchaWidgets.addAll([
                  const SizedBox(height: 24),
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: Theme.of(
                          context,
                        ).colorScheme.outline.withOpacity(0.2),
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
                              style: Theme.of(context).textTheme.titleSmall
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
                            padding: const EdgeInsets.symmetric(vertical: 8),
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
                ]);
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
                        Text(
                          requireCaptcha
                              ? 'Informe os dados e o captcha exibido para emitir o CRLV-e.'
                              : 'Informe apenas os dados. Resolveremos o captcha automaticamente.',
                          style: Theme.of(context).textTheme.bodyMedium
                              ?.copyWith(color: const Color(0xFF475467)),
                        ),
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            Expanded(
                              child: RadioListTile<PlateFormat>(
                                value: PlateFormat.antiga,
                                groupValue: plateFormat,
                                contentPadding: EdgeInsets.zero,
                                dense: true,
                                title: const Text('Antiga (ABC-1234)'),
                                onChanged: (value) {
                                  if (value == null) return;
                                  setState(() {
                                    plateFormat = value;
                                    plateController.clear();
                                  });
                                },
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: RadioListTile<PlateFormat>(
                                value: PlateFormat.mercosul,
                                groupValue: plateFormat,
                                contentPadding: EdgeInsets.zero,
                                dense: true,
                                title: const Text('Mercosul (ABC-1D23)'),
                                onChanged: (value) {
                                  if (value == null) return;
                                  setState(() {
                                    plateFormat = value;
                                    plateController.clear();
                                  });
                                },
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: plateController,
                          decoration: const InputDecoration(labelText: 'Placa'),
                          enabled: plateFormat != null,
                          inputFormatters: [
                            if (plateFormat != null)
                              PlateInputFormatter(plateFormat!),
                            LengthLimitingTextInputFormatter(8),
                          ],
                          textCapitalization: TextCapitalization.characters,
                          validator: (value) {
                            final text = value?.trim().toUpperCase() ?? '';
                            if (plateFormat == null) {
                              return 'Selecione o padrão da placa';
                            }
                            if (text.isEmpty) {
                              return 'Informe a placa';
                            }
                            if (!PlateUtils.isValid(text, plateFormat!)) {
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
                        ...captchaWidgets,
                        const SizedBox(height: 24),
                        FilledButton(
                          onPressed:
                              (!requireCaptcha ||
                                  (!isLoadingCaptcha && captchaBase64 != null))
                              ? () {
                                  if (!formKey.currentState!.validate()) {
                                    return;
                                  }
                                  if (plateFormat == null) {
                                    return;
                                  }
                                  final plateValue = PlateUtils.format(
                                    plateController.text,
                                    plateFormat!,
                                  );
                                  Navigator.of(dialogContext).pop(
                                    _CrlvEmissionRequest(
                                      plate: plateValue,
                                      renavam: renavamController.text.trim(),
                                      document: documentController.text.trim(),
                                      captcha: requireCaptcha
                                          ? captchaController.text
                                                .trim()
                                                .toUpperCase()
                                          : '',
                                    ),
                                  );
                                }
                              : null,
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

  Future<_BaseOutrosEstadosQuery?> _showBaseOutrosEstadosDialog({
    bool requireCaptcha = true,
    String? initialChassi,
    String? initialUf,
  }) async {
    final formKey = GlobalKey<FormState>();
    final chassiController = TextEditingController(text: initialChassi ?? '');
    final captchaController = TextEditingController();
    String? selectedUf =
        initialUf != null && _brazilUfCodes.contains(initialUf.toUpperCase())
        ? initialUf.toUpperCase()
        : null;

    String? captchaBase64;
    String? captchaError;
    bool isLoadingCaptcha = false;
    bool initialized = false;

    Future<void> refreshCaptcha(StateSetter setState) async {
      if (!requireCaptcha) return;

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
                if (requireCaptcha) {
                  Future.microtask(() => refreshCaptcha(setState));
                }
              }

              Uint8List? captchaBytes;
              if (requireCaptcha &&
                  captchaBase64 != null &&
                  captchaBase64!.isNotEmpty) {
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
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.w700,
                            ),
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
                        requireCaptcha
                            ? 'Informe o chassi, UF e o captcha exibido para consultar.'
                            : 'Informe o chassi e a UF. O captcha será resolvido automaticamente.',
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: const Color(0xFF475467),
                        ),
                      ),
                      const SizedBox(height: 20),
                      TextFormField(
                        controller: chassiController,
                        decoration: const InputDecoration(labelText: 'Chassi'),
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
                      if (requireCaptcha) ...[
                        const SizedBox(height: 20),
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: theme.colorScheme.outline.withOpacity(0.2),
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
                                    style: theme.textTheme.titleSmall?.copyWith(
                                      fontWeight: FontWeight.w600,
                                    ),
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
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 8,
                                  ),
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
                                ),
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
                                  if (!requireCaptcha) {
                                    return null;
                                  }
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
                      ],
                      const SizedBox(height: 24),
                      FilledButton(
                        onPressed:
                            (!requireCaptcha ||
                                (!isLoadingCaptcha && captchaBase64 != null))
                            ? () {
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
                                    captcha: requireCaptcha
                                        ? captchaController.text
                                              .trim()
                                              .toUpperCase()
                                        : '',
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

  Future<void> _handleGravameFlow() async {
    final request = await _showGravameDialog(requireCaptcha: false);
    if (request == null || !mounted) return;

    final autoCompleted = await _executeGravameQuery(
      request: request,
      autoSolve: true,
    );

    if (!mounted || autoCompleted) return;

    _showErrorMessage(
      'Não foi possível resolver o captcha automaticamente. Informe-o manualmente.',
    );

    final manualRequest = await _showGravameDialog(
      requireCaptcha: true,
      initialPlate: request.plate,
      initialChassi: request.chassi,
    );

    if (manualRequest == null || !mounted) return;

    await _executeGravameQuery(
      request: manualRequest,
      autoSolve: false,
      captchaOverride: manualRequest.captcha,
    );
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

  Future<_RenainfRequest?> _showRenainfDialog({
    bool requireCaptcha = true,
    _RenainfRequest? initialRequest,
  }) async {
    final formKey = GlobalKey<FormState>();
    final plateController = TextEditingController(
      text: initialRequest?.plate ?? '',
    );
    PlateFormat? plateFormat =
        initialRequest?.plate?.trim().isNotEmpty ?? false
            ? PlateUtils.inferFormat(initialRequest!.plate)
            : null;
    if (plateFormat != null && plateController.text.trim().isNotEmpty) {
      plateController.text = PlateUtils.format(
        plateController.text,
        plateFormat,
      );
    }
    final startDateController = TextEditingController(
      text: initialRequest?.startDate != null
          ? _formatDate(initialRequest!.startDate)
          : '',
    );
    final endDateController = TextEditingController(
      text: initialRequest?.endDate != null
          ? _formatDate(initialRequest!.endDate)
          : '',
    );
    final captchaController = TextEditingController();

    const statusOptions = [
      {'label': 'Todas', 'value': 2},
      {'label': 'Multas em cobrança', 'value': 1},
    ];

    int? selectedStatusValue = initialRequest?.statusCode ?? 2;
    String selectedStatusLabel =
        initialRequest?.statusLabel ??
        (statusOptions.firstWhere(
              (status) => status['value'] == selectedStatusValue,
              orElse: () => statusOptions.first,
            )['label']!
            as String);
    String? selectedUf = initialRequest?.uf;
    if (selectedUf != null) {
      selectedUf = selectedUf.toUpperCase();
    }
    DateTime? startDate = initialRequest?.startDate;
    DateTime? endDate = initialRequest?.endDate;

    String? captchaBase64;
    String? captchaError;
    bool isLoadingCaptcha = false;
    bool initialized = false;

    Future<void> refreshCaptcha(StateSetter setState) async {
      if (!requireCaptcha) return;

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
      final initialDate = isStart
          ? (startDate ?? DateTime.now())
          : (endDate ?? DateTime.now());
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
                if (requireCaptcha) {
                  Future.microtask(() => refreshCaptcha(setState));
                }
              }

              Uint8List? captchaBytes;
              if (requireCaptcha &&
                  captchaBase64 != null &&
                  captchaBase64!.isNotEmpty) {
                try {
                  captchaBytes = base64Decode(captchaBase64!);
                } catch (_) {
                  captchaError ??= 'Captcha recebido em formato inválido.';
                }
              }

              final plateText = plateController.text.trim().toUpperCase();
              final captchaText = captchaController.text.trim().toUpperCase();
              final plateValid =
                  plateFormat != null &&
                  plateText.isNotEmpty &&
                  PlateUtils.isValid(plateText, plateFormat!);
              final datesValid =
                  startDate != null &&
                  endDate != null &&
                  !startDate!.isAfter(endDate!);
              final captchaLoaded =
                  !requireCaptcha ||
                  (captchaBase64 != null && captchaBase64!.isNotEmpty);
              final captchaFilled = !requireCaptcha || captchaText.isNotEmpty;

              final readyToSubmit =
                  plateValid &&
                  datesValid &&
                  selectedStatusValue != null &&
                  selectedUf != null &&
                  captchaLoaded &&
                  captchaFilled &&
                  !isLoadingCaptcha;

              return Form(
                key: formKey,
                autovalidateMode: AutovalidateMode.onUserInteraction,
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Text(
                            'Consulta RENAINF',
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
                    Row(
                      children: [
                        Expanded(
                          child: RadioListTile<PlateFormat>(
                            value: PlateFormat.antiga,
                            groupValue: plateFormat,
                            contentPadding: EdgeInsets.zero,
                            dense: true,
                            title: const Text('Antiga (ABC-1234)'),
                            onChanged: (value) {
                              if (value == null) return;
                              setState(() {
                                plateFormat = value;
                                plateController.clear();
                              });
                              formKey.currentState?.validate();
                            },
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: RadioListTile<PlateFormat>(
                            value: PlateFormat.mercosul,
                            groupValue: plateFormat,
                            contentPadding: EdgeInsets.zero,
                            dense: true,
                            title: const Text('Mercosul (ABC-1D23)'),
                            onChanged: (value) {
                              if (value == null) return;
                              setState(() {
                                plateFormat = value;
                                plateController.clear();
                              });
                              formKey.currentState?.validate();
                            },
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: plateController,
                      decoration: const InputDecoration(labelText: 'Placa'),
                      enabled: plateFormat != null,
                      inputFormatters: [
                        if (plateFormat != null)
                          PlateInputFormatter(plateFormat!),
                        LengthLimitingTextInputFormatter(8),
                      ],
                      textCapitalization: TextCapitalization.characters,
                      validator: (value) {
                        final text = value?.trim().toUpperCase() ?? '';
                        if (plateFormat == null) {
                          return 'Selecione o padrão da placa';
                        }
                        if (text.isEmpty) {
                          return 'Informe a placa';
                        }
                        if (!PlateUtils.isValid(text, plateFormat!)) {
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
                            selectedStatusLabel =
                                statusOptions.firstWhere(
                                      (status) => status['value'] == value,
                                    )['label']!
                                    as String;
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
                      if (requireCaptcha) ...[
                        const SizedBox(height: 20),
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: Theme.of(
                                context,
                              ).colorScheme.outline.withOpacity(0.2),
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
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 8,
                                  ),
                                  child: Text(
                                    captchaError!,
                                    style: TextStyle(
                                      color: Theme.of(
                                        context,
                                      ).colorScheme.error,
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
                                ),
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
                                  if (!requireCaptcha) {
                                    return null;
                                  }
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
                      ],
                      const SizedBox(height: 24),
                      FilledButton(
                        onPressed: readyToSubmit
                            ? () {
                                if (!formKey.currentState!.validate()) {
                                  return;
                                }
                                if (plateFormat == null) {
                                  return;
                                }
                                if (selectedUf == null ||
                                    selectedStatusValue == null ||
                                    startDate == null ||
                                    endDate == null) {
                                  return;
                                }
                                FocusManager.instance.primaryFocus?.unfocus();
                                final plateValue = PlateUtils.format(
                                  plateController.text,
                                  plateFormat!,
                                );
                                Navigator.of(dialogContext).pop(
                                  _RenainfRequest(
                                    plate: plateValue,
                                    statusCode: selectedStatusValue!,
                                    statusLabel: selectedStatusLabel,
                                    uf: selectedUf!,
                                    captcha: requireCaptcha
                                        ? captchaController.text
                                              .trim()
                                              .toUpperCase()
                                        : '',
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

    plateController.dispose();
    startDateController.dispose();
    endDateController.dispose();
    captchaController.dispose();

    return result;
  }

  Future<_BloqueiosAtivosRequest?> _showBloqueiosAtivosDialog({
    bool requireCaptcha = true,
    _BloqueiosAtivosRequest? initialRequest,
  }) async {
    final chassiController = TextEditingController(
      text: initialRequest?.chassi ?? '',
    );
    final captchaController = TextEditingController();

    String selectedSource = initialRequest?.origin ?? 'DETRAN';

    String? captchaBase64;
    String? captchaError;
    bool isLoadingCaptcha = false;
    bool initialized = false;

    Future<void> refreshCaptcha(StateSetter setState) async {
      if (!requireCaptcha) return;

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
              final captchaValid = !requireCaptcha || captchaValue.isNotEmpty;
              final captchaReady =
                  !requireCaptcha ||
                  (captchaBase64 != null && !isLoadingCaptcha);
              final isValid = chassiValid && captchaValid && captchaReady;

              Color backgroundFor(String source) => selectedSource == source
                  ? Theme.of(context).colorScheme.primary
                  : const Color(0xFFE7EDFF);
              Color foregroundFor(String source) => selectedSource == source
                  ? Colors.white
                  : const Color(0xFF344054);

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
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 12,
                                  ),
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
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 12,
                                  ),
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
                      if (requireCaptcha) ...[
                        const SizedBox(height: 20),
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: Theme.of(
                                context,
                              ).colorScheme.outline.withOpacity(0.2),
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
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 8,
                                  ),
                                  child: Text(
                                    captchaError!,
                                    style: TextStyle(
                                      color: Theme.of(
                                        context,
                                      ).colorScheme.error,
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
                      ],
                      const SizedBox(height: 24),
                      FilledButton(
                        onPressed: isValid
                            ? () {
                                FocusManager.instance.primaryFocus?.unfocus();
                                Navigator.of(dialogContext).pop(
                                  _BloqueiosAtivosRequest(
                                    origin: selectedSource,
                                    captcha: requireCaptcha ? captchaValue : '',
                                    chassi: chassiText,
                                    opcaoPesquisa: selectedSource == 'DETRAN'
                                        ? '1'
                                        : '2',
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
    unawaited(
      AppErrorDialog.show(context, message: message, title: 'Algo deu errado'),
    );
  }

  void _showNoticeMessage(String message, {String? title}) {
    if (!mounted) return;
    unawaited(AppNoticeDialog.show(context, message: message, title: title));
  }

  String _resolveErrorMessage(
    Object? error, {
    required String fallback,
  }) {
    if (error == null) return fallback;
    if (error is BaseEstadualException) return error.message;
    if (error is BinException) return error.message;
    if (error is GravameException) return error.message;
    if (error is RenainfException) return error.message;
    if (error is PesquisaException) return error.message;
    if (error is FichaCadastralException) return error.message;
    if (error is AuthException) return error.message;
    if (error is Exception) {
      final message = error.toString();
      if (message.isNotEmpty) {
        return message;
      }
    }
    if (error is String && error.isNotEmpty) {
      return error;
    }
    return fallback;
  }

  void _showSuccessMessage(String message) {
    if (!mounted) return;
    final theme = Theme.of(context);
    ScaffoldMessenger.of(context)
      ..clearSnackBars()
      ..showSnackBar(
        SnackBar(
          content: Text(message),
          backgroundColor: theme.colorScheme.primary,
        ),
      );
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

  Map<String, dynamic>? _asMap(dynamic value) {
    if (value is Map<String, dynamic>) {
      return value;
    }
    if (value is Map) {
      return value.map((key, dynamic val) => MapEntry(key.toString(), val));
    }
    return null;
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
    final normalized = value
        .replaceAll(RegExp('[^A-Za-z0-9]'), '')
        .toUpperCase();
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
    _loadPermissions();
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

  Future<void> _loadPermissions() async {
    if (!mounted) return;
    if (_authService.session == null) {
      setState(() {
        _allowedPermissionSlugs = {};
        _permissionsError = null;
        _isLoadingPermissions = false;
      });
      return;
    }

    final cached = _authService.permissions;
    if (cached.isNotEmpty) {
      setState(() {
        _allowedPermissionSlugs = cached.toSet();
        _permissionsError = null;
        _isLoadingPermissions = false;
      });
      return;
    }

    setState(() {
      _isLoadingPermissions = true;
      _permissionsError = null;
    });

    try {
      final permissions = await _authService.fetchPermissions();
      if (!mounted) return;
      setState(() {
        _allowedPermissionSlugs = permissions.toSet();
        _isLoadingPermissions = false;
      });
    } on AuthException catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoadingPermissions = false;
        _permissionsError = e.message;
      });
      if (e.message.contains('Não autenticado') ||
          e.message.contains('Sessão expirada')) {
        _handleUnauthorized();
      }
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _isLoadingPermissions = false;
        _permissionsError = 'Não foi possível carregar as permissões.';
      });
    }
  }

  RecentVehicle _mapPesquisaResumoToRecentVehicle(PesquisaResumo resumo) {
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
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _isFetchingUser = false;
      });
      _showErrorMessage(
        _resolveErrorMessage(
          error,
          fallback: 'Não foi possível carregar os dados do usuário.',
        ),
      );
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
      Navigator.of(
        context,
      ).pushNamedAndRemoveUntil(LoginPage.routeName, (route) => false);
    } on AuthException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _showErrorMessage(e.message);
    } catch (error) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _showErrorMessage(
        _resolveErrorMessage(
          error,
          fallback: 'Não foi possível sair. Tente novamente.',
        ),
      );
    }
  }

  void _openProfile() {
    Navigator.of(context).pushNamed(ProfilePage.routeName);
  }

  void _handleUnauthorized() {
    _authService.clearSession(clearPersisted: true);
    if (mounted) {
      setState(() {
        _allowedPermissionSlugs = {};
      });
    }
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      Navigator.of(
        context,
      ).pushNamedAndRemoveUntil(LoginPage.routeName, (route) => false);
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final visibleActions = _buildVisibleActions();
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
                    onProfileTap: _authService.session != null
                        ? _openProfile
                        : null,
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
                        if (_isLoadingPermissions)
                          const Center(
                            child: Padding(
                              padding: EdgeInsets.symmetric(vertical: 32),
                              child: CircularProgressIndicator(),
                            ),
                          )
                        else if (_permissionsError != null)
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _permissionsError!,
                                style: theme.textTheme.bodyMedium?.copyWith(
                                  color: theme.colorScheme.error,
                                ),
                              ),
                              TextButton(
                                onPressed: _loadPermissions,
                                child: const Text('Tentar novamente'),
                              ),
                            ],
                          )
                        else if (visibleActions.isEmpty)
                          Text(
                            'Nenhuma funcionalidade liberada para este usuário.',
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: const Color(0xFF667085),
                            ),
                          )
                        else
                          for (var i = 0; i < visibleActions.length; i++)
                            Padding(
                              padding: const EdgeInsets.only(bottom: 16),
                              child: Builder(
                                builder: (_) {
                                  final action = visibleActions[i];
                                  final hasSubActions =
                                      action.subActions.isNotEmpty;
                                  return HomeActionCard(
                                    action: action,
                                    isExpanded: hasSubActions
                                        ? _expandedActionKey == action.title
                                        : false,
                                    onTap: hasSubActions
                                        ? () => _toggleExpanded(action.title)
                                        : () => _handlePrimaryActionTap(action),
                                    onSubActionTap: (subAction) =>
                                        _handleSubActionTap(action, subAction),
                                  );
                                },
                              ),
                            ),
                        Text(
                          'Últimos veículos pesquisados',
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontSize: 16,
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

 

enum _BinSearchOption { placaRenavam, chassi }

class _VehicleLookupDialog extends StatefulWidget {
  const _VehicleLookupDialog({
    required this.title,
    required this.fetchCaptcha,
    required this.plateValidator,
    this.submitLabel = "Consultar",
    this.captchaLabel = "Informe o captcha",
    this.captchaErrorResolver,
    this.includeRenavam = true,
    this.renavamValidator,
    this.includeChassi = false,
    this.chassiValidator,
    this.requireCaptcha = true,
    this.initialPlate,
    this.initialRenavam,
    this.initialChassi,
  });

  final String title;
  final Future<String> Function() fetchCaptcha;
  final bool Function(String value) plateValidator;
  final bool Function(String value)? renavamValidator;
  final bool Function(String value)? chassiValidator;
  final String Function(Object error)? captchaErrorResolver;
  final String submitLabel;
  final String captchaLabel;
  final bool includeRenavam;
  final bool includeChassi;
  final bool requireCaptcha;
  final String? initialPlate;
  final String? initialRenavam;
  final String? initialChassi;

  @override
  State<_VehicleLookupDialog> createState() => _VehicleLookupDialogState();
}

class _VehicleLookupDialogState extends State<_VehicleLookupDialog> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _plateController;
  late final TextEditingController _renavamController;
  late final TextEditingController _chassiController;
  late final TextEditingController _captchaController;

  bool _isLoadingCaptcha = false;
  String? _captchaBase64;
  String? _captchaError;
  _BinSearchOption _binSearchOption = _BinSearchOption.placaRenavam;
  PlateFormat? _plateFormat;

  bool get _isBinLookup => widget.includeChassi;
  bool get _isChassiMode =>
      _isBinLookup && _binSearchOption == _BinSearchOption.chassi;
  bool get _isPlacaRenavamMode => !_isChassiMode;

  @override
  void initState() {
    super.initState();
    _plateController = TextEditingController(text: widget.initialPlate ?? '');
    _renavamController = TextEditingController(
      text: widget.initialRenavam ?? '',
    );
    _chassiController = TextEditingController(
      text: widget.initialChassi ?? '',
    );
    if (_isBinLookup && (widget.initialChassi?.trim().isNotEmpty ?? false)) {
      _binSearchOption = _BinSearchOption.chassi;
    }
    _captchaController = TextEditingController();
    if (_isPlacaRenavamMode && _plateController.text.trim().isNotEmpty) {
      _plateFormat = PlateUtils.inferFormat(_plateController.text);
      if (_plateFormat != null) {
        _plateController.text = PlateUtils.format(
          _plateController.text,
          _plateFormat!,
        );
      }
    }
    if (widget.requireCaptcha) {
      _refreshCaptcha();
    }
  }

  @override
  void dispose() {
    _plateController.dispose();
    _renavamController.dispose();
    _chassiController.dispose();
    _captchaController.dispose();
    super.dispose();
  }

  Future<void> _refreshCaptcha() async {
    if (!widget.requireCaptcha) {
      return;
    }
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
    } catch (error) {
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
    if (widget.requireCaptcha) {
      if (_isLoadingCaptcha || _captchaBase64 == null) {
        return;
      }
    }
    if (!_formKey.currentState!.validate()) {
      return;
    }
    final rawPlate = _plateController.text.trim().toUpperCase();
    final placaValue = (_plateFormat != null)
        ? PlateUtils.format(rawPlate, _plateFormat!)
        : rawPlate;
    final renavamValue = widget.includeRenavam
        ? _renavamController.text.trim().toUpperCase()
        : '';
    final chassiValue = widget.includeChassi
        ? _chassiController.text.trim().toUpperCase()
        : '';

    if (_isBinLookup) {
      if (_isChassiMode && chassiValue.isEmpty) {
        _showSnack('Informe o chassi para consultar.');
        return;
      }
      if (_isPlacaRenavamMode &&
          (placaValue.isEmpty || (widget.includeRenavam && renavamValue.isEmpty))) {
        _showSnack('Informe placa e renavam para consultar.');
        return;
      }
    }

    if (_isPlacaRenavamMode) {
      if (_plateFormat == null) {
        _showSnack('Selecione o padrão da placa para consultar.');
        return;
      }
      if (!PlateUtils.isValid(placaValue, _plateFormat!)) {
        _showSnack('Placa inválida.');
        return;
      }
    }

    final selectedOption = _isBinLookup
        ? (_isChassiMode ? '1' : '2')
        : null;

    Navigator.of(context).pop(
      _BaseEstadualQuery(
        placa: _isChassiMode ? '' : placaValue,
        renavam: _isChassiMode ? '' : renavamValue,
        chassi: _isChassiMode && chassiValue.isNotEmpty ? chassiValue : null,
        opcao: selectedOption,
        captcha: widget.requireCaptcha
            ? _captchaController.text.trim().toUpperCase()
            : '',
      ),
    );
  }

  void _showSnack(String message) {
    ScaffoldMessenger.of(context)
      ..clearSnackBars()
      ..showSnackBar(
        SnackBar(content: Text(message)),
      );
  }

  @override
  Widget build(BuildContext context) {
    Uint8List? captchaBytes;
    if (widget.requireCaptcha &&
        _captchaBase64 != null &&
        _captchaBase64!.isNotEmpty) {
      try {
        captchaBytes = base64Decode(_captchaBase64!);
      } catch (_) {
        _captchaError ??= 'Captcha recebido em formato inválido.';
      }
    }

    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 24),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
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
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const Spacer(),
                    IconButton(
                      onPressed: () => Navigator.of(context).pop(),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                if (widget.includeChassi) ...[
                  Row(
                    children: [
                      Expanded(
                        child: RadioListTile<_BinSearchOption>(
                          value: _BinSearchOption.placaRenavam,
                          groupValue: _binSearchOption,
                          contentPadding: EdgeInsets.zero,
                          dense: true,
                          title: Text(
                            widget.includeRenavam ? 'Placa + Renavam' : 'Placa',
                          ),
                          onChanged: (value) {
                            if (value == null) return;
                            setState(() {
                              _binSearchOption = value;
                              _plateFormat = null;
                              _plateController.clear();
                            });
                          },
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: RadioListTile<_BinSearchOption>(
                          value: _BinSearchOption.chassi,
                          groupValue: _binSearchOption,
                          contentPadding: EdgeInsets.zero,
                          dense: true,
                          title: const Text('Chassi'),
                          onChanged: (value) {
                            if (value == null) return;
                            setState(() {
                              _binSearchOption = value;
                            });
                          },
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                ],
                if (_isPlacaRenavamMode) ...[
                  Row(
                    children: [
                      Expanded(
                        child: RadioListTile<PlateFormat>(
                          value: PlateFormat.antiga,
                          groupValue: _plateFormat,
                          contentPadding: EdgeInsets.zero,
                          dense: true,
                          title: const Text('Antiga (ABC-1234)'),
                          onChanged: (value) {
                            if (value == null) return;
                            setState(() {
                              _plateFormat = value;
                              _plateController.clear();
                            });
                          },
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: RadioListTile<PlateFormat>(
                          value: PlateFormat.mercosul,
                          groupValue: _plateFormat,
                          contentPadding: EdgeInsets.zero,
                          dense: true,
                          title: const Text('Mercosul (ABC-1D23)'),
                          onChanged: (value) {
                            if (value == null) return;
                            setState(() {
                              _plateFormat = value;
                              _plateController.clear();
                            });
                          },
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _plateController,
                    decoration: const InputDecoration(labelText: 'Placa'),
                    enabled: _plateFormat != null,
                    inputFormatters: [
                      if (_plateFormat != null) PlateInputFormatter(_plateFormat!),
                      LengthLimitingTextInputFormatter(8),
                    ],
                    textCapitalization: TextCapitalization.characters,
                    validator: (value) {
                      final text = value?.trim().toUpperCase() ?? '';
                      if (_plateFormat == null) {
                        return 'Selecione o padrão da placa';
                      }
                      if (text.isEmpty) {
                        return 'Informe a placa';
                      }
                      if (!PlateUtils.isValid(text, _plateFormat!)) {
                        return 'Placa inválida';
                      }
                      return null;
                    },
                  ),
                  if (widget.includeRenavam) ...[
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
                        if (!widget.includeRenavam) {
                          return null;
                        }
                        if (text.isEmpty) {
                          return 'Informe o renavam';
                        }
                        if (widget.renavamValidator != null &&
                            !widget.renavamValidator!(text)) {
                          return 'Renavam inválido';
                        }
                        return null;
                      },
                    ),
                  ],
                ],
                if (widget.includeChassi && _isChassiMode) ...[
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _chassiController,
                    decoration: const InputDecoration(labelText: 'Chassi'),
                    inputFormatters: [
                      const _UpperCaseTextFormatter(),
                      FilteringTextInputFormatter.allow(
                        RegExp('[A-Za-z0-9]'),
                      ),
                      LengthLimitingTextInputFormatter(17),
                    ],
                    textCapitalization: TextCapitalization.characters,
                    validator: (value) {
                      if (!widget.includeChassi) return null;
                      final text = value?.trim().toUpperCase() ?? '';
                      if (text.isEmpty) {
                        return 'Informe o chassi';
                      }
                      if (widget.chassiValidator != null &&
                          !widget.chassiValidator!(text)) {
                        return 'Chassi inválido';
                      }
                      return null;
                    },
                  ),
                ],
                if (widget.requireCaptcha) ...[
                  const SizedBox(height: 20),
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: Theme.of(
                          context,
                        ).colorScheme.outline.withOpacity(0.2),
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
                              style: Theme.of(context).textTheme.titleSmall
                                  ?.copyWith(fontWeight: FontWeight.w600),
                            ),
                            const Spacer(),
                            TextButton.icon(
                              onPressed: _isLoadingCaptcha
                                  ? null
                                  : _refreshCaptcha,
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
                ],
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: widget.requireCaptcha
                      ? (_isLoadingCaptcha || _captchaBase64 == null
                            ? null
                            : _submit)
                      : _submit,
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

class _GravameDialog extends StatefulWidget {
  const _GravameDialog({
    required this.fetchCaptcha,
    required this.plateValidator,
    required this.chassiValidator,
    this.captchaErrorResolver,
    this.requireCaptcha = true,
    this.initialPlate,
    this.initialChassi,
  });

  final Future<String> Function() fetchCaptcha;
  final bool Function(String value) plateValidator;
  final bool Function(String value) chassiValidator;
  final String Function(Object error)? captchaErrorResolver;
  final bool requireCaptcha;
  final String? initialPlate;
  final String? initialChassi;

  @override
  State<_GravameDialog> createState() => _GravameDialogState();
}

enum _GravameSearchOption { placa, chassi }

class _GravameDialogState extends State<_GravameDialog> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _plateController;
  late final TextEditingController _chassiController;
  late final TextEditingController _captchaController;

  bool _isLoadingCaptcha = false;
  String? _captchaBase64;
  String? _captchaError;
  _GravameSearchOption _searchOption = _GravameSearchOption.placa;
  PlateFormat? _plateFormat;

  @override
  void initState() {
    super.initState();
    _plateController = TextEditingController(text: widget.initialPlate ?? '');
    _chassiController = TextEditingController(text: widget.initialChassi ?? '');
    _captchaController = TextEditingController();
    if (widget.initialChassi?.trim().isNotEmpty ?? false) {
      _searchOption = _GravameSearchOption.chassi;
    }
    if (_searchOption == _GravameSearchOption.placa &&
        _plateController.text.trim().isNotEmpty) {
      _plateFormat = PlateUtils.inferFormat(_plateController.text);
      if (_plateFormat != null) {
        _plateController.text = PlateUtils.format(
          _plateController.text,
          _plateFormat!,
        );
      }
    }
    if (widget.requireCaptcha) {
      _refreshCaptcha();
    }
  }

  @override
  void dispose() {
    _plateController.dispose();
    _chassiController.dispose();
    _captchaController.dispose();
    super.dispose();
  }

  Future<void> _refreshCaptcha() async {
    if (!widget.requireCaptcha) return;

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
    } catch (error) {
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
    if (widget.requireCaptcha) {
      if (_isLoadingCaptcha || _captchaBase64 == null) return;
    }
    if (!_formKey.currentState!.validate()) return;

    final plateValue = _searchOption == _GravameSearchOption.placa
        ? (_plateFormat == null
              ? ''
              : PlateUtils.format(_plateController.text, _plateFormat!))
        : '';

    Navigator.of(context).pop(
      _GravameRequest(
        option: _searchOption == _GravameSearchOption.chassi ? 'chassi' : 'placa',
        plate: _searchOption == _GravameSearchOption.placa
            ? plateValue
            : null,
        chassi: _searchOption == _GravameSearchOption.chassi
            ? _chassiController.text.trim().toUpperCase()
            : null,
        captcha: widget.requireCaptcha
            ? _captchaController.text.trim().toUpperCase()
            : '',
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    Uint8List? captchaBytes;
    if (widget.requireCaptcha &&
        _captchaBase64 != null &&
        _captchaBase64!.isNotEmpty) {
      try {
        captchaBytes = base64Decode(_captchaBase64!);
      } catch (_) {
        _captchaError ??= 'Captcha recebido em formato inválido.';
      }
    }

    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 24),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxHeight: 580),
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
                      'Consultar gravame',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const Spacer(),
                    IconButton(
                      onPressed: () => Navigator.of(context).pop(),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  widget.requireCaptcha
                      ? 'Informe a placa ou o chassi e o captcha para consultar o gravame.'
                      : 'Informe a placa ou o chassi. Resolveremos o captcha automaticamente.',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: const Color(0xFF475467),
                  ),
                ),
                const SizedBox(height: 20),
                Row(
                  children: [
                    Expanded(
                      child: RadioListTile<_GravameSearchOption>(
                        value: _GravameSearchOption.placa,
                        groupValue: _searchOption,
                        contentPadding: EdgeInsets.zero,
                        dense: true,
                        title: const Text('Placa'),
                        onChanged: (value) {
                          if (value == null) return;
                          setState(() {
                            _searchOption = value;
                            _plateFormat = null;
                            _plateController.clear();
                            _chassiController.clear();
                          });
                        },
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: RadioListTile<_GravameSearchOption>(
                        value: _GravameSearchOption.chassi,
                        groupValue: _searchOption,
                        contentPadding: EdgeInsets.zero,
                        dense: true,
                        title: const Text('Chassi'),
                        onChanged: (value) {
                          if (value == null) return;
                          setState(() {
                            _searchOption = value;
                            _plateFormat = null;
                            _plateController.clear();
                            _chassiController.clear();
                          });
                        },
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                if (_searchOption == _GravameSearchOption.placa) ...[
                  Row(
                    children: [
                      Expanded(
                        child: RadioListTile<PlateFormat>(
                          value: PlateFormat.antiga,
                          groupValue: _plateFormat,
                          contentPadding: EdgeInsets.zero,
                          dense: true,
                          title: const Text('Antiga (ABC-1234)'),
                          onChanged: (value) {
                            if (value == null) return;
                            setState(() {
                              _plateFormat = value;
                              _plateController.clear();
                            });
                          },
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: RadioListTile<PlateFormat>(
                          value: PlateFormat.mercosul,
                          groupValue: _plateFormat,
                          contentPadding: EdgeInsets.zero,
                          dense: true,
                          title: const Text('Mercosul (ABC-1D23)'),
                          onChanged: (value) {
                            if (value == null) return;
                            setState(() {
                              _plateFormat = value;
                              _plateController.clear();
                            });
                          },
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _plateController,
                    decoration: const InputDecoration(labelText: 'Placa'),
                    enabled: _plateFormat != null,
                    inputFormatters: [
                      if (_plateFormat != null)
                        PlateInputFormatter(_plateFormat!),
                      LengthLimitingTextInputFormatter(8),
                    ],
                    textCapitalization: TextCapitalization.characters,
                    validator: (value) {
                      final text = value?.trim().toUpperCase() ?? '';
                      if (_plateFormat == null) {
                        return 'Selecione o padrão da placa';
                      }
                      if (text.isEmpty) {
                        return 'Informe a placa';
                      }
                      if (!PlateUtils.isValid(text, _plateFormat!)) {
                        return 'Placa inválida';
                      }
                      return null;
                    },
                  ),
                ] else
                  TextFormField(
                    controller: _chassiController,
                    decoration: const InputDecoration(labelText: 'Chassi'),
                    inputFormatters: [
                      const _UpperCaseTextFormatter(),
                      FilteringTextInputFormatter.allow(RegExp('[A-Za-z0-9]')),
                      LengthLimitingTextInputFormatter(17),
                    ],
                    textCapitalization: TextCapitalization.characters,
                    validator: (value) {
                      final text = value?.trim().toUpperCase() ?? '';
                      if (text.isEmpty) {
                        return 'Informe o chassi';
                      }
                      if (!widget.chassiValidator(text)) {
                        return 'Chassi inválido';
                      }
                      return null;
                    },
                  ),
                if (widget.requireCaptcha) ...[
                  const SizedBox(height: 20),
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: Theme.of(
                          context,
                        ).colorScheme.outline.withOpacity(0.2),
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
                              style: Theme.of(context).textTheme.titleSmall
                                  ?.copyWith(fontWeight: FontWeight.w600),
                            ),
                            const Spacer(),
                            TextButton.icon(
                              onPressed: _isLoadingCaptcha
                                  ? null
                                  : _refreshCaptcha,
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
                          textCapitalization: TextCapitalization.characters,
                          validator: (value) {
                            if (!widget.requireCaptcha) return null;
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
                ],
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: widget.requireCaptcha
                      ? (_isLoadingCaptcha || _captchaBase64 == null
                            ? null
                            : _submit)
                      : _submit,
                  child: const Text('Consultar'),
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

class _FichaConsultaDialog extends StatefulWidget {
  const _FichaConsultaDialog({
    required this.fetchCaptcha,
    required this.captchaErrorResolver,
    required this.plateValidator,
    this.requireCaptcha = true,
    this.initialPlate,
  });

  final Future<String> Function() fetchCaptcha;
  final String Function(Object error)? captchaErrorResolver;
  final bool Function(String value) plateValidator;
  final bool requireCaptcha;
  final String? initialPlate;

  @override
  State<_FichaConsultaDialog> createState() => _FichaConsultaDialogState();
}

class _FichaConsultaDialogState extends State<_FichaConsultaDialog> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _plateController;
  late final TextEditingController _captchaController;

  bool _isLoadingCaptcha = false;
  String? _captchaBase64;
  String? _captchaError;
  PlateFormat? _plateFormat;

  @override
  void initState() {
    super.initState();
    _plateController = TextEditingController(text: widget.initialPlate ?? '');
    _captchaController = TextEditingController();
    if (_plateController.text.trim().isNotEmpty) {
      _plateFormat = PlateUtils.inferFormat(_plateController.text);
      if (_plateFormat != null) {
        _plateController.text = PlateUtils.format(
          _plateController.text,
          _plateFormat!,
        );
      }
    }
    if (widget.requireCaptcha) {
      _refreshCaptcha();
    }
  }

  @override
  void dispose() {
    _plateController.dispose();
    _captchaController.dispose();
    super.dispose();
  }

  Future<void> _refreshCaptcha() async {
    if (!widget.requireCaptcha) return;

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
    } catch (error) {
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
    if (widget.requireCaptcha) {
      if (_isLoadingCaptcha || _captchaBase64 == null) return;
    }
    if (!_formKey.currentState!.validate()) return;

    if (_plateFormat == null) return;

    Navigator.of(context).pop(
      _FichaConsultaRequest(
        plate: PlateUtils.format(_plateController.text, _plateFormat!),
        captcha: widget.requireCaptcha
            ? _captchaController.text.trim().toUpperCase()
            : '',
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    Uint8List? captchaBytes;
    if (widget.requireCaptcha &&
        _captchaBase64 != null &&
        _captchaBase64!.isNotEmpty) {
      try {
        captchaBytes = base64Decode(_captchaBase64!);
      } catch (_) {
        _captchaError ??= 'Captcha recebido em formato inválido.';
      }
    }

    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 24),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
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
                      'Ficha cadastral - passo 1',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const Spacer(),
                    IconButton(
                      onPressed: () => Navigator.of(context).pop(),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                if (widget.requireCaptcha)
                  Text(
                    'Digite a placa e o captcha exibido para recuperar o número da ficha.',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: const Color(0xFF475467),
                    ),
                  )
                else
                  Text(
                    'Informe somente a placa. Resolveremos o captcha automaticamente.',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: const Color(0xFF475467),
                    ),
                  ),
                const SizedBox(height: 20),
                Row(
                  children: [
                    Expanded(
                      child: RadioListTile<PlateFormat>(
                        value: PlateFormat.antiga,
                        groupValue: _plateFormat,
                        contentPadding: EdgeInsets.zero,
                        dense: true,
                        title: const Text('Antiga (ABC-1234)'),
                        onChanged: (value) {
                          if (value == null) return;
                          setState(() {
                            _plateFormat = value;
                            _plateController.clear();
                          });
                        },
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: RadioListTile<PlateFormat>(
                        value: PlateFormat.mercosul,
                        groupValue: _plateFormat,
                        contentPadding: EdgeInsets.zero,
                        dense: true,
                        title: const Text('Mercosul (ABC-1D23)'),
                        onChanged: (value) {
                          if (value == null) return;
                          setState(() {
                            _plateFormat = value;
                            _plateController.clear();
                          });
                        },
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _plateController,
                  decoration: const InputDecoration(labelText: 'Placa'),
                  enabled: _plateFormat != null,
                  inputFormatters: [
                    if (_plateFormat != null)
                      PlateInputFormatter(_plateFormat!),
                    LengthLimitingTextInputFormatter(8),
                  ],
                  textCapitalization: TextCapitalization.characters,
                  validator: (value) {
                    final text = value?.trim().toUpperCase() ?? '';
                    if (_plateFormat == null) {
                      return 'Selecione o padrão da placa';
                    }
                    if (text.isEmpty) {
                      return 'Informe a placa';
                    }
                    if (!PlateUtils.isValid(text, _plateFormat!)) {
                      return 'Placa inválida';
                    }
                    return null;
                  },
                ),
                if (widget.requireCaptcha) ...[
                  const SizedBox(height: 20),
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: Theme.of(
                          context,
                        ).colorScheme.outline.withOpacity(0.2),
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
                              style: Theme.of(context).textTheme.titleSmall
                                  ?.copyWith(fontWeight: FontWeight.w600),
                            ),
                            const Spacer(),
                            TextButton.icon(
                              onPressed: _isLoadingCaptcha
                                  ? null
                                  : _refreshCaptcha,
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
                              ),
                            ),
                          ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _captchaController,
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
                ],
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: widget.requireCaptcha
                      ? (_isLoadingCaptcha || _captchaBase64 == null
                            ? null
                            : _submit)
                      : _submit,
                  child: const Text('Avançar'),
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

class _FichaAndamentoDialog extends StatefulWidget {
  const _FichaAndamentoDialog({
    required this.numeroFicha,
    required this.anoFicha,
    required this.fetchCaptcha,
    required this.captchaErrorResolver,
  });

  final String numeroFicha;
  final String anoFicha;
  final Future<String> Function() fetchCaptcha;
  final String Function(Object error)? captchaErrorResolver;

  @override
  State<_FichaAndamentoDialog> createState() => _FichaAndamentoDialogState();
}

class _FichaAndamentoDialogState extends State<_FichaAndamentoDialog> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _captchaController;

  bool _isLoadingCaptcha = false;
  String? _captchaBase64;
  String? _captchaError;

  @override
  void initState() {
    super.initState();
    _captchaController = TextEditingController();
    _refreshCaptcha();
  }

  @override
  void dispose() {
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
    } catch (error) {
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
    if (_isLoadingCaptcha || _captchaBase64 == null) return;
    if (!_formKey.currentState!.validate()) return;

    Navigator.of(context).pop(
      _FichaAndamentoRequest(
        numeroFicha: widget.numeroFicha,
        anoFicha: widget.anoFicha,
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
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxHeight: 480),
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
                      'Andamento do processo - passo 2',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const Spacer(),
                    IconButton(
                      onPressed: () => Navigator.of(context).pop(),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  'Confirme os dados e preencha o novo captcha para consultar o andamento.',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: const Color(0xFF475467),
                  ),
                ),
                const SizedBox(height: 20),
                TextFormField(
                  initialValue: widget.numeroFicha,
                  decoration: const InputDecoration(
                    labelText: 'Número da ficha',
                  ),
                  enabled: false,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  initialValue: widget.anoFicha,
                  decoration: const InputDecoration(labelText: 'Ano da ficha'),
                  enabled: false,
                ),
                const SizedBox(height: 20),
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: Theme.of(
                        context,
                      ).colorScheme.outline.withOpacity(0.2),
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
                            style: Theme.of(context).textTheme.titleSmall
                                ?.copyWith(fontWeight: FontWeight.w600),
                          ),
                          const Spacer(),
                          TextButton.icon(
                            onPressed: _isLoadingCaptcha
                                ? null
                                : _refreshCaptcha,
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
                            ),
                          ),
                        ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _captchaController,
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
                  onPressed: _isLoadingCaptcha || _captchaBase64 == null
                      ? null
                      : _submit,
                  child: const Text('Consultar andamento'),
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
    this.chassi,
    this.opcao,
  });

  final String placa;
  final String renavam;
  final String captcha;
  final String? chassi;
  final String? opcao;
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

class _GravameRequest {
  const _GravameRequest({
    required this.option,
    required this.captcha,
    this.plate,
    this.chassi,
  });

  final String option;
  final String? plate;
  final String? chassi;
  final String captcha;
}

class _FichaConsultaRequest {
  const _FichaConsultaRequest({required this.plate, required this.captcha});

  final String plate;
  final String captcha;
}

class _FichaAndamentoRequest {
  const _FichaAndamentoRequest({
    required this.numeroFicha,
    required this.anoFicha,
    required this.captcha,
  });

  final String numeroFicha;
  final String anoFicha;
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

class _CaptchaOperationResult<T> {
  const _CaptchaOperationResult.success(this.data) : requiresManual = false;

  const _CaptchaOperationResult.manual() : data = null, requiresManual = true;

  const _CaptchaOperationResult.failure() : data = null, requiresManual = false;

  final T? data;
  final bool requiresManual;
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
