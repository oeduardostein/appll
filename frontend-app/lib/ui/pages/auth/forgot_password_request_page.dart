import 'package:flutter/material.dart';

import 'package:frontend_app/services/auth_service.dart';
import 'package:frontend_app/services/password_reset_service.dart';

import '../shared/loading_dialog.dart';
import 'forgot_password_reset_page.dart';
import 'widgets/auth_back_button.dart';
import 'widgets/auth_form_container.dart';
import 'widgets/auth_header.dart';

class ForgotPasswordRequestPage extends StatefulWidget {
  const ForgotPasswordRequestPage({super.key});

  static const routeName = '/forgot-password';

  @override
  State<ForgotPasswordRequestPage> createState() =>
      _ForgotPasswordRequestPageState();
}

class _ForgotPasswordRequestPageState extends State<ForgotPasswordRequestPage> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordResetService = PasswordResetService();

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  String? _validateEmail(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Informe um email válido';
    }
    final pattern = RegExp(r'^[\w\.-]+@[\w-]+\.\w{2,}$', caseSensitive: false);
    if (!pattern.hasMatch(value.trim())) {
      return 'Email inválido';
    }
    return null;
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final email = _emailController.text.trim();

    FocusScope.of(context).unfocus();
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      final message = await _passwordResetService.requestCode(email: email);
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _showSnackBar(message);
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ForgotPasswordResetPage(email: email),
        ),
      );
    } on AuthException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _showSnackBar(e.message);
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _showSnackBar('Não foi possível enviar o código. Tente novamente.');
    }
  }

  void _showSnackBar(String message) {
    ScaffoldMessenger.of(context)
      ..clearSnackBars()
      ..showSnackBar(
        SnackBar(content: Text(message)),
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
                    title: 'Esqueci minha senha',
                    subtitle:
                        'Informe seu email para enviarmos um código de verificação.',
                  ),
                  const SizedBox(height: 24),
                  AuthFormContainer(
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          TextFormField(
                            controller: _emailController,
                            decoration: const InputDecoration(
                              labelText: 'Email',
                            ),
                            keyboardType: TextInputType.emailAddress,
                            validator: _validateEmail,
                          ),
                          const SizedBox(height: 24),
                          FilledButton(
                            onPressed: _submit,
                            child: const Text('Enviar código'),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Você receberá um código de seis dígitos no seu email.',
                    style: theme.textTheme.bodySmall,
                    textAlign: TextAlign.center,
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
