import 'package:flutter/material.dart';

class AtpvFormPage extends StatefulWidget {
  const AtpvFormPage({super.key, required this.plate});

  final String plate;

  @override
  State<AtpvFormPage> createState() => _AtpvFormPageState();
}

class _AtpvFormPageState extends State<AtpvFormPage> {
  final _formKey = GlobalKey<FormState>();

  late final TextEditingController _plateController;
  final _renavamController = TextEditingController();
  final _chassiController = TextEditingController();
  final _ownerDocumentController = TextEditingController();
  final _ownerEmailController = TextEditingController();
  final _saleValueController = TextEditingController();
  final _odometerController = TextEditingController();
  final _buyerDocumentController = TextEditingController();
  final _buyerNameController = TextEditingController();
  final _buyerEmailController = TextEditingController();
  final _buyerCepController = TextEditingController();
  final _buyerNumberController = TextEditingController();
  final _buyerComplementController = TextEditingController();
  final _buyerCityController = TextEditingController();

  bool _termsAccepted = false;

  @override
  void initState() {
    super.initState();
    _plateController = TextEditingController(text: widget.plate);
  }

  @override
  void dispose() {
    _plateController.dispose();
    _renavamController.dispose();
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
    super.dispose();
  }

  void _submit() {
    final form = _formKey.currentState;
    if (form == null) return;

    if (!form.validate()) {
      return;
    }

    if (!_termsAccepted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Confirme que as informações acima estão corretas.'),
        ),
      );
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('ATPV-e enviada com sucesso!'),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Preenchimento da ATPV-e'),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _buildSection(
                  context,
                  title: 'Dados do veículo',
                  fields: [
                    _buildTextField(
                      label: 'Placa',
                      controller: _plateController,
                      readOnly: true,
                    ),
                    _buildTextField(
                      label: 'Renavam',
                      controller: _renavamController,
                    ),
                    _buildTextField(
                      label: 'Chassi',
                      controller: _chassiController,
                    ),
                    _buildTextField(
                      label: 'CPF / CNPJ do Proprietário Atual',
                      controller: _ownerDocumentController,
                    ),
                    _buildTextField(
                      label: 'E-mail do Proprietário Atual',
                      controller: _ownerEmailController,
                      keyboardType: TextInputType.emailAddress,
                    ),
                    _buildTextField(
                      label: 'Valor da Venda',
                      controller: _saleValueController,
                      keyboardType: TextInputType.number,
                    ),
                    _buildTextField(
                      label: 'Hodômetro',
                      controller: _odometerController,
                      keyboardType: TextInputType.number,
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                _buildSection(
                  context,
                  title: 'Dados do comprador',
                  fields: [
                    _buildTextField(
                      label: 'CPF / CNPJ',
                      controller: _buyerDocumentController,
                    ),
                    _buildTextField(
                      label: 'Nome Completo',
                      controller: _buyerNameController,
                    ),
                    _buildTextField(
                      label: 'E-mail',
                      controller: _buyerEmailController,
                      keyboardType: TextInputType.emailAddress,
                    ),
                    Row(
                      children: [
                        Expanded(
                          child: _buildTextField(
                            label: 'CEP',
                            controller: _buyerCepController,
                            keyboardType: TextInputType.number,
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
                    _buildTextField(
                      label: 'Complemento',
                      controller: _buyerComplementController,
                      requiredField: false,
                    ),
                    _buildTextField(
                      label: 'Município',
                      controller: _buyerCityController,
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                CheckboxListTile(
                  contentPadding: EdgeInsets.zero,
                  title: const Text(
                    'Confirmo que as informações acima estão corretas.',
                  ),
                  value: _termsAccepted,
                  onChanged: (value) =>
                      setState(() => _termsAccepted = value ?? false),
                ),
                const SizedBox(height: 12),
                FilledButton(
                  onPressed: _submit,
                  child: const Text('Enviar ATPV-e'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildSection(
    BuildContext context, {
    required String title,
    required List<Widget> fields,
  }) {
    final theme = Theme.of(context);
    return Card(
      elevation: 1,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
      ),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: theme.textTheme.titleMedium
                  ?.copyWith(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 12),
            for (var i = 0; i < fields.length; i++) ...[
              if (i > 0) const SizedBox(height: 12),
              fields[i],
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildTextField({
    required String label,
    required TextEditingController controller,
    TextInputType? keyboardType,
    bool readOnly = false,
    bool requiredField = true,
  }) {
    return TextFormField(
      controller: controller,
      decoration: InputDecoration(labelText: label),
      readOnly: readOnly,
      keyboardType: keyboardType,
      validator: (value) {
        if (!requiredField || readOnly) {
          return null;
        }
        if (value == null || value.trim().isEmpty) {
          return 'Campo obrigatório';
        }
        return null;
      },
    );
  }
}

