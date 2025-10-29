import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../atpv/atpv_form_page.dart';
import '../base_state/base_estadual_page.dart';
import '../base_state/base_outros_estados_page.dart';
import '../bin/bin_result_page.dart';
import '../bloqueios/bloqueios_page.dart';
import '../ecrv/ecrv_process_page.dart';
import '../fines/renainf_page.dart';
import '../gravame/gravame_page.dart';
import '../shared/loading_dialog.dart';
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
        Navigator.of(context).push(
          MaterialPageRoute(builder: (context) => const BaseEstadualPage()),
        );
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

  Future<void> _handleBaseOutrosEstadosFlow() async {
    final request = await _showBaseOutrosEstadosDialog();
    if (request == null || !mounted) return;

    await _showLoadingDialog();
    if (!mounted) return;

    Navigator.of(
      context,
    ).push(MaterialPageRoute(builder: (_) => const BaseOutrosEstadosPage()));
  }

  Future<void> _handleBinFlow() async {
    final request = await _showSimplePlateChassiDialog(title: 'Pesquisa BIN');
    if (request == null || !mounted) return;

    await _showLoadingDialog();
    if (!mounted) return;

    Navigator.of(
      context,
    ).push(MaterialPageRoute(builder: (_) => const BinResultPage()));
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

    await _showLoadingDialog();
    if (!mounted) return;

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => BloqueiosPage(
          origin: request.origin,
          plate: request.plate,
          chassi: request.chassi,
        ),
      ),
    );
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

    final confirm = await _showCrlvConfirmationDialog();
    if (!mounted || confirm != true) return;

    await _showLoadingDialog();
    if (!mounted) return;

    await _showCrlvSuccessDialog();
  }

  Future<void> _handleAtpvEmissionFlow() async {
    final plate = await _showAtpvEmissionDialog();
    if (plate == null || !mounted) return;

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => AtpvFormPage(plate: plate),
      ),
    );
  }

  Future<_CrlvEmissionRequest?> _showCrlvEmissionDialog() async {
    final plateController = TextEditingController();
    final renavamController = TextEditingController();
    final documentController = TextEditingController();

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
              final plateText = plateController.text.trim().toUpperCase();
              final renavamText = renavamController.text.trim();
              final documentText = documentController.text.trim();

              final plateValid =
                  plateText.isNotEmpty && _isValidPlate(plateText);
              final renavamValid =
                  renavamText.isNotEmpty && _isValidRenavam(renavamText);
              final documentValid =
                  documentText.isNotEmpty && _isValidCpfCnpj(documentText);

              final isValid = plateValid && renavamValid && documentValid;

              return ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 520),
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
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
                          suffixIcon: Icon(
                            Icons.search,
                            color: Theme.of(context).colorScheme.primary,
                          ),
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
                      TextField(
                        controller: renavamController,
                        decoration: InputDecoration(
                          labelText: 'Renavam',
                          errorText: renavamText.isNotEmpty && !renavamValid
                              ? 'Renavam inválido'
                              : null,
                        ),
                        keyboardType: TextInputType.number,
                        inputFormatters: [
                          FilteringTextInputFormatter.digitsOnly,
                          LengthLimitingTextInputFormatter(11),
                        ],
                        onChanged: (_) => setState(() {}),
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: documentController,
                        decoration: InputDecoration(
                          labelText: 'CPF / CNPJ',
                          errorText: documentText.isNotEmpty && !documentValid
                              ? 'Documento inválido'
                              : null,
                        ),
                        keyboardType: TextInputType.number,
                        inputFormatters: [
                          FilteringTextInputFormatter.digitsOnly,
                          LengthLimitingTextInputFormatter(14),
                        ],
                        onChanged: (_) => setState(() {}),
                      ),
                      const SizedBox(height: 20),
                      ElevatedButton(
                        onPressed: isValid
                            ? () {
                                FocusManager.instance.primaryFocus?.unfocus();
                                Navigator.of(dialogContext).pop(
                                  _CrlvEmissionRequest(
                                    plate: plateText,
                                    renavam: renavamText,
                                    document: documentText,
                                  ),
                                );
                              }
                            : null,
                        style: ElevatedButton.styleFrom(
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
      renavamController.dispose();
      documentController.dispose();
    });
    return result;
  }

  Future<String?> _showAtpvEmissionDialog() async {
    final plateController = TextEditingController();

    final result = await showDialog<String>(
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

              return Padding(
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
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
                        hintText: 'Digite a placa do veículo',
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
                    const SizedBox(height: 20),
                    FilledButton(
                      onPressed: plateValid
                          ? () {
                              final normalized = plateText;
                              FocusManager.instance.primaryFocus?.unfocus();
                              Navigator.of(dialogContext).pop(normalized);
                            }
                          : null,
                      child: const Text('Avançar'),
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
    });
    return result;
  }

  Future<bool?> _showCrlvConfirmationDialog() async {
    return showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(24, 24, 24, 16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'Confirma emissão do CRLV-e?',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                        color: const Color(0xFF0E2945),
                      ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 8),
                Text(
                  'Esta ação não poderá ser desfeita.',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: const Color(0xFF475467),
                      ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 24),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () {
                          Navigator.of(dialogContext).pop(false);
                        },
                        style: OutlinedButton.styleFrom(
                          minimumSize: const Size.fromHeight(48),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: const Text('Não'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: FilledButton(
                        onPressed: () {
                          Navigator.of(dialogContext).pop(true);
                        },
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(48),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: const Text('Sim'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Future<void> _showCrlvSuccessDialog() async {
    if (!mounted) return;

    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Container(
                  height: 72,
                  width: 72,
                  decoration: BoxDecoration(
                    color: const Color(0xFFEFFAF3),
                    shape: BoxShape.circle,
                  ),
                  alignment: Alignment.center,
                  child: const Icon(
                    Icons.check_circle,
                    color: Color(0xFF12B76A),
                    size: 48,
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  'CRLV-e gerado com sucesso',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                        color: const Color(0xFF0E2945),
                      ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 8),
                Text(
                  'O documento digital já está disponível para consulta.',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: const Color(0xFF475467),
                      ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: () {
                      Navigator.of(dialogContext).pop();
                    },
                    style: FilledButton.styleFrom(
                      minimumSize: const Size.fromHeight(48),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    child: const Text('OK'),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

Future<_BloqueiosAtivosRequest?> _showBloqueiosAtivosDialog() async {
    final plateController = TextEditingController();
    final chassiController = TextEditingController();
    String selectedSource = 'DETRAN';

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
              final plateText = plateController.text.trim().toUpperCase();
              final chassiText = chassiController.text.trim().toUpperCase();

              final plateValid =
                  plateText.isNotEmpty && _isValidPlate(plateText);
              final chassiValid =
                  chassiText.isNotEmpty && _isValidChassi(chassiText);
              final isValid = plateValid || chassiValid;

              Color backgroundFor(String source) =>
                  selectedSource == source
                      ? Theme.of(context).colorScheme.primary
                      : const Color(0xFFE7EDFF);
              Color foregroundFor(String source) =>
                  selectedSource == source ? Colors.white : const Color(0xFF344054);

              return ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 520),
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
                        onChanged: (_) => setState(() {}),
                      ),
                      const SizedBox(height: 20),
                      FilledButton(
                        onPressed: isValid
                            ? () {
                                FocusManager.instance.primaryFocus?.unfocus();
                                Navigator.of(dialogContext).pop(
                                  _BloqueiosAtivosRequest(
                                    origin: selectedSource,
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
    });
    return result;
  }

  Future<_OtherStatesSearchRequest?> _showBaseOutrosEstadosDialog() async {
    final plateController = TextEditingController();
    final chassiController = TextEditingController();
    const states = [
      'São Paulo',
      'Rio de Janeiro',
      'Minas Gerais',
      'Paraná',
      'Santa Catarina',
      'Rio Grande do Sul',
      'Bahia',
      'Distrito Federal',
    ];
    String? selectedState;

    final result = await showDialog<_OtherStatesSearchRequest>(
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
              final isValid =
                  selectedState != null && (plateValid || chassiValid);

              return Padding(
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      children: [
                        Text(
                          'Base de outros estados',
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
                    DropdownButtonFormField<String>(
                      decoration: const InputDecoration(
                        labelText: 'Selecionar estado',
                        border: OutlineInputBorder(),
                        filled: true,
                      ),
                      initialValue: selectedState,
                      items: states
                          .map(
                            (state) => DropdownMenuItem(
                              value: state,
                              child: Text(state),
                            ),
                          )
                          .toList(),
                      onChanged: (value) {
                        setState(() {
                          selectedState = value;
                        });
                      },
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: plateController,
                      decoration: InputDecoration(
                        labelText: 'Placa',
                        border: OutlineInputBorder(),
                        filled: true,
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
                        border: OutlineInputBorder(),
                        filled: true,
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
                      onChanged: (_) => setState(() {}),
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: isValid
                          ? () {
                              FocusManager.instance.primaryFocus?.unfocus();
                              Navigator.of(dialogContext).pop(
                                _OtherStatesSearchRequest(
                                  state: selectedState!,
                                  plate: plateValid ? plateText : '',
                                  chassi: chassiValid ? chassiText : '',
                                ),
                              );
                            }
                          : null,
                      style: ElevatedButton.styleFrom(
                        minimumSize: const Size.fromHeight(52),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                      child: const Text('Pesquisar'),
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
      chassiController.dispose();
    });
    return result;
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
                      const SizedBox(height: 12),
                      Text(
                        'Informe a placa ou o chassi para consultar o status do processo.',
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: const Color(0xFF475467),
                            ),
                      ),
                      const SizedBox(height: 20),
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
                          style: Theme.of(context)
                              .textTheme
                              .bodySmall
                              ?.copyWith(
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
                        onChanged: (_) => setState(() {}),
                      ),
                      const SizedBox(height: 20),
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
      chassiController.dispose();
    });
    return result;
  }

Future<_RenainfSearchRequest?> _showRenainfDialog() async {
    final plateController = TextEditingController();
    final startDateController = TextEditingController();
    final endDateController = TextEditingController();

    const statusOptions = [
      'Multas em cobrança',
      'Multas pagas',
      'Todas',
    ];

    DateTime? startDate = DateTime(2000, 1, 1);
    DateTime? endDate = DateTime.now();
    String? selectedStatus;

    startDateController.text = _formatDate(startDate);
    endDateController.text = _formatDate(endDate);

    final result = await showDialog<_RenainfSearchRequest>(
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
              final statusValid = selectedStatus != null;
              final dateRangeValid = startDate != null &&
                  endDate != null &&
                  !endDate!.isBefore(startDate!);
              final isValid = plateValid && statusValid && dateRangeValid;

              Future<void> pickDate({
                required bool isStart,
              }) async {
                final initialDate = isStart
                    ? (startDate ?? DateTime.now())
                    : (endDate ?? DateTime.now());
                final firstDate = DateTime(2000, 1, 1);
                final lastDate =
                    DateTime.now().add(const Duration(days: 365 * 5));

                final selectedDate = await showDatePicker(
                  context: dialogContext,
                  initialDate: initialDate,
                  firstDate: firstDate,
                  lastDate: lastDate,
                );

                if (selectedDate != null) {
                  setState(() {
                    if (isStart) {
                      startDate = selectedDate;
                      startDateController.text = _formatDate(selectedDate);
                    } else {
                      endDate = selectedDate;
                      endDateController.text = _formatDate(selectedDate);
                    }
                  });
                }
              }

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
                        border: const OutlineInputBorder(),
                        filled: true,
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
                      onChanged: (_) => setState(() {}),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      decoration: const InputDecoration(
                        labelText: 'Status da multa',
                      ),
                      initialValue: selectedStatus,
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
                          'Escolha um intervalo de datas válido.',
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: const Color(0xFFD92D20),
                                fontWeight: FontWeight.w600,
                              ),
                        ),
                      ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: isValid
                          ? () {
                              FocusManager.instance.primaryFocus?.unfocus();
                              Navigator.of(dialogContext).pop(
                                _RenainfSearchRequest(
                                  plate: plateText,
                                  status: selectedStatus!,
                                  startDate: startDate!,
                                  endDate: endDate!,
                                ),
                              );
                            }
                          : null,
                      style: ElevatedButton.styleFrom(
                        minimumSize: const Size.fromHeight(52),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                      child: const Text('Pesquisar'),
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

Future<_BinSearchRequest?> _showSimplePlateChassiDialog({
  required String title,
}) async {
    final plateController = TextEditingController();
    final chassiController = TextEditingController();

    final result = await showDialog<_BinSearchRequest>(
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

              return Padding(
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
                        border: const OutlineInputBorder(),
                        filled: true,
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
                        border: const OutlineInputBorder(),
                        filled: true,
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
                      onChanged: (_) => setState(() {}),
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: isValid
                          ? () {
                              FocusManager.instance.primaryFocus?.unfocus();
                              Navigator.of(dialogContext).pop(
                                _BinSearchRequest(
                                  plate: plateValid ? plateText : '',
                                  chassi: chassiValid ? chassiText : '',
                                ),
                              );
                            }
                          : null,
                      style: ElevatedButton.styleFrom(
                        minimumSize: const Size.fromHeight(52),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                      child: const Text('Pesquisar'),
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
      chassiController.dispose();
    });
    return result;
  }

  Future<void> _handleGravameFlow() async {
    final request = await _showSimplePlateChassiDialog(title: 'GRAVAME');
    if (request == null || !mounted) return;

    await _showLoadingDialog();
    if (!mounted) return;

    Navigator.of(
      context,
    ).push(MaterialPageRoute(builder: (_) => const GravamePage()));
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
                  const HomeHeader(userName: 'Lucas'),
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

class _CrlvEmissionRequest {
  const _CrlvEmissionRequest({
    required this.plate,
    required this.renavam,
    required this.document,
  });

  final String plate;
  final String renavam;
  final String document;
}

class _OtherStatesSearchRequest {
  const _OtherStatesSearchRequest({
    required this.state,
    required this.plate,
    required this.chassi,
  });

  final String state;
  final String plate;
  final String chassi;
}

class _BinSearchRequest {
  const _BinSearchRequest({required this.plate, required this.chassi});

  final String plate;
  final String chassi;
}

class _RenainfSearchRequest {
  const _RenainfSearchRequest({
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
    this.plate,
    this.chassi,
  });

  final String origin;
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
