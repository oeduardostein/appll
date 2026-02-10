import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'package:frontend_app/services/auth_service.dart';

import '../home/home_page.dart';
import '../shared/loading_dialog.dart';
import 'forgot_password_request_page.dart';
import 'register_page.dart';
import 'widgets/auth_form_container.dart';
import 'widgets/auth_header.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  static const routeName = '/login';

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _step1FormKey = GlobalKey<FormState>();
  final _step2FormKey = GlobalKey<FormState>();
  final _identifierController = TextEditingController(text: '');
  final _passwordController = TextEditingController(text: '');
  final _securityKeyController = TextEditingController(text: '');
  final _authService = AuthService();
  bool _obscurePassword = true;
  bool _rememberMe = false;
  String? _challengeId;

  @override
  void dispose() {
    _identifierController.dispose();
    _passwordController.dispose();
    _securityKeyController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_challengeId != null) {
      await _submitStep2();
      return;
    }

    if (!_step1FormKey.currentState!.validate()) return;

    FocusScope.of(context).unfocus();
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      final challenge = await _authService.startLogin(
        identifier: _identifierController.text.trim(),
        password: _passwordController.text,
        rememberMe: _rememberMe,
      );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      setState(() {
        _challengeId = challenge.challengeId;
        _securityKeyController.clear();
      });
      _showErrorMessage(challenge.message);
    } on AuthException catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _showErrorMessage(e.message);
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      _showErrorMessage('Não foi possível entrar. Tente novamente.');
    }
  }

  Future<void> _submitStep2() async {
    if (!_step2FormKey.currentState!.validate()) return;
    final challengeId = _challengeId;
    if (challengeId == null) return;

    FocusScope.of(context).unfocus();
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      await _authService.verifyLogin(
        challengeId: challengeId,
        securityKey: _securityKeyController.text.trim(),
        rememberMe: _rememberMe,
      );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const HomePage()),
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
      _showErrorMessage('Não foi possível validar a chave. Tente novamente.');
    }
  }

  void _resetToStep1() {
    setState(() {
      _challengeId = null;
      _securityKeyController.clear();
    });
  }

  void _showErrorMessage(String message) {
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
                  const AuthHeader(
                    title: 'Bem-vindo de volta',
                    subtitle: 'Acesse sua conta com seus dados de login.',
                  ),
                  const SizedBox(height: 24),
                  AuthFormContainer(
                    child: _challengeId == null
                        ? Form(
                            key: _step1FormKey,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                TextFormField(
                                  controller: _identifierController,
                                  decoration: const InputDecoration(
                                    labelText: 'Usuário ou email',
                                  ),
                                  textInputAction: TextInputAction.next,
                                  onFieldSubmitted: (_) {
                                    FocusScope.of(context).nextFocus();
                                  },
                                  validator: (value) {
                                    if (value == null ||
                                        value.trim().isEmpty) {
                                      return 'Informe usuário ou email';
                                    }
                                    return null;
                                  },
                                ),
                                const SizedBox(height: 16),
                                TextFormField(
                                  controller: _passwordController,
                                  decoration: InputDecoration(
                                    labelText: 'Senha',
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
                                  textInputAction: TextInputAction.done,
                                  onFieldSubmitted: (_) => _submit(),
                                  validator: (value) {
                                    if (value == null || value.isEmpty) {
                                      return 'Informe sua senha';
                                    }
                                    if (value.length < 6) {
                                      return 'A senha deve ter pelo menos 6 caracteres';
                                    }
                                    return null;
                                  },
                                ),
                                const SizedBox(height: 12),
                                CheckboxListTile(
                                  value: _rememberMe,
                                  contentPadding: EdgeInsets.zero,
                                  controlAffinity:
                                      ListTileControlAffinity.leading,
                                  title: const Text('Manter conectado'),
                                  onChanged: (value) {
                                    setState(() {
                                      _rememberMe = value ?? false;
                                    });
                                  },
                                ),
                                Align(
                                  alignment: Alignment.centerRight,
                                  child: TextButton(
                                    onPressed: () {
                                      Navigator.of(context).pushNamed(
                                        ForgotPasswordRequestPage.routeName,
                                      );
                                    },
                                    child: const Text('Esqueci minha senha'),
                                  ),
                                ),
                                const SizedBox(height: 24),
                                FilledButton(
                                  onPressed: _submit,
                                  child: const Text('Enviar chave'),
                                ),
                              ],
                            ),
                          )
                        : Form(
                            key: _step2FormKey,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                Text(
                                  'Digite a chave de segurança enviada para o seu e-mail.',
                                  style: theme.textTheme.bodyMedium,
                                ),
                                const SizedBox(height: 16),
                                TextFormField(
                                  controller: _securityKeyController,
                                  decoration: const InputDecoration(
                                    labelText: 'Chave de segurança',
                                  ),
                                  keyboardType: TextInputType.number,
                                  inputFormatters: [
                                    FilteringTextInputFormatter.digitsOnly,
                                    LengthLimitingTextInputFormatter(6),
                                  ],
                                  textInputAction: TextInputAction.done,
                                  onFieldSubmitted: (_) => _submit(),
                                  validator: (value) {
                                    final text = value?.trim() ?? '';
                                    if (text.isEmpty) {
                                      return 'Informe a chave de segurança';
                                    }
                                    if (text.length < 6) {
                                      return 'Informe uma chave válida';
                                    }
                                    return null;
                                  },
                                ),
                                const SizedBox(height: 24),
                                FilledButton(
                                  onPressed: _submit,
                                  child: const Text('Entrar'),
                                ),
                                const SizedBox(height: 8),
                                TextButton(
                                  onPressed: _resetToStep1,
                                  child: const Text('Voltar'),
                                ),
                              ],
                            ),
                          ),
                  ),
                  const SizedBox(height: 20),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'Não tem conta?',
                        style: theme.textTheme.bodyMedium,
                      ),
                      TextButton(
                        onPressed: () {
                          Navigator.of(context).pushReplacementNamed(
                            RegisterPage.routeName,
                          );
                        },
                        child: const Text('Cadastre-se'),
                      ),
                    ],
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
