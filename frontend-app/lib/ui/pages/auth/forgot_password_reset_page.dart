import 'package:flutter/material.dart';

import '../shared/loading_dialog.dart';
import 'login_page.dart';
import 'widgets/auth_back_button.dart';
import 'widgets/auth_form_container.dart';
import 'widgets/auth_header.dart';

class ForgotPasswordResetPage extends StatefulWidget {
  const ForgotPasswordResetPage({super.key, required this.email});

  final String email;

  @override
  State<ForgotPasswordResetPage> createState() =>
      _ForgotPasswordResetPageState();
}

class _ForgotPasswordResetPageState extends State<ForgotPasswordResetPage> {
  final _formKey = GlobalKey<FormState>();
  final _codeController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;

  @override
  void dispose() {
    _codeController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    FocusScope.of(context).unfocus();
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    await Future.delayed(const Duration(seconds: 2));
    if (!mounted) return;
    Navigator.of(context, rootNavigator: true).pop();
    if (!mounted) return;

    Navigator.of(context).pushNamedAndRemoveUntil(
      LoginPage.routeName,
      (route) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(20, 40, 20, 24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  AuthBackButton(
                    label: 'Voltar',
                    onPressed: () => Navigator.of(context).maybePop(),
                  ),
                  const SizedBox(height: 12),
                  const AuthHeader(
                    title: 'Redefinir senha',
                    subtitle: 'Digite o código recebido e crie uma nova senha.',
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Enviamos um código para ${widget.email}. Verifique sua caixa de entrada.',
                    style: theme.textTheme.bodySmall,
                  ),
                  const SizedBox(height: 24),
                  AuthFormContainer(
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          TextFormField(
                            controller: _codeController,
                            decoration: const InputDecoration(
                              labelText: 'Código de verificação',
                            ),
                            keyboardType: TextInputType.number,
                            textInputAction: TextInputAction.next,
                            validator: (value) {
                              if (value == null || value.trim().isEmpty) {
                                return 'Informe o código recebido';
                              }
                              if (value.trim().length < 6) {
                                return 'Informe os 6 dígitos do código';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 16),
                          TextFormField(
                            controller: _passwordController,
                            decoration: InputDecoration(
                              labelText: 'Nova senha',
                              suffixIcon: IconButton(
                                onPressed: () {
                                  setState(() {
                                    _obscurePassword = !_obscurePassword;
                                  });
                                },
                                icon: Icon(
                                  _obscurePassword
                                      ? Icons.visibility_off_outlined
                                      : Icons.visibility_outlined,
                                ),
                              ),
                            ),
                            obscureText: _obscurePassword,
                            textInputAction: TextInputAction.next,
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Informe uma nova senha';
                              }
                              if (value.length < 6) {
                                return 'A senha deve ter pelo menos 6 caracteres';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 16),
                          TextFormField(
                            controller: _confirmPasswordController,
                            decoration: InputDecoration(
                              labelText: 'Confirme a nova senha',
                              suffixIcon: IconButton(
                                onPressed: () {
                                  setState(() {
                                    _obscureConfirmPassword =
                                        !_obscureConfirmPassword;
                                  });
                                },
                                icon: Icon(
                                  _obscureConfirmPassword
                                      ? Icons.visibility_off_outlined
                                      : Icons.visibility_outlined,
                                ),
                              ),
                            ),
                            obscureText: _obscureConfirmPassword,
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Confirme a nova senha';
                              }
                              if (value != _passwordController.text) {
                                return 'As senhas não coincidem';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 24),
                          FilledButton(
                            onPressed: _submit,
                            child: const Text('Redefinir senha'),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
