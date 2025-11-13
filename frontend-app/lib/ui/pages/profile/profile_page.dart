import 'package:flutter/material.dart';

import '../../../services/auth_service.dart';
import '../auth/login_page.dart';
import '../shared/loading_dialog.dart';
import '../../widgets/app_error_dialog.dart';

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key, this.authService});

  static const routeName = '/profile';

  final AuthService? authService;

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  late final AuthService _authService;
  AuthUser? _user;
  bool _isLoadingUser = false;
  bool _isDeleting = false;

  @override
  void initState() {
    super.initState();
    _authService = widget.authService ?? AuthService();
    _user = _authService.session?.user;
    if (_authService.session == null) {
      _handleUnauthorized();
      return;
    }
    _loadUser();
  }

  Future<void> _loadUser() async {
    setState(() {
      _isLoadingUser = true;
    });

    try {
      final user = await _authService.fetchCurrentUser();
      if (!mounted) return;
      setState(() {
        _user = user;
        _isLoadingUser = false;
      });
    } on AuthException catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoadingUser = false;
      });
      if (e.message.contains('Não autenticado') ||
          e.message.contains('Sessão expirada')) {
        _handleUnauthorized();
        return;
      }
      await AppErrorDialog.show(context, message: e.message);
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _isLoadingUser = false;
      });
      await AppErrorDialog.show(
        context,
        message: 'Não foi possível carregar seu perfil. Tente novamente.',
      );
    }
  }

  Future<void> _confirmDeleteAccount() async {
    final shouldDelete =
        await showDialog<bool>(
          context: context,
          builder: (dialogContext) {
            return AlertDialog(
              title: const Text('Excluir conta'),
              content: const Text(
                'Tem certeza que deseja excluir a sua conta? Essa ação é definitiva.',
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(dialogContext).pop(false),
                  child: const Text('Cancelar'),
                ),
                FilledButton(
                  style: FilledButton.styleFrom(
                    backgroundColor: Theme.of(context).colorScheme.error,
                  ),
                  onPressed: () => Navigator.of(dialogContext).pop(true),
                  child: const Text('Excluir'),
                ),
              ],
            );
          },
        ) ??
        false;

    if (!shouldDelete) return;

    await _deleteAccount();
  }

  Future<void> _deleteAccount() async {
    setState(() {
      _isDeleting = true;
    });

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const LoadingDialog(),
    );

    try {
      await _authService.deleteAccount();
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
      await AppErrorDialog.show(context, message: e.message);
    } catch (_) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      if (!mounted) return;
      await AppErrorDialog.show(
        context,
        message: 'Não foi possível excluir a conta. Tente novamente.',
      );
    } finally {
      if (mounted) {
        setState(() {
          _isDeleting = false;
        });
      }
    }
  }

  void _handleUnauthorized() {
    _authService.clearSession();
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
    final user = _user;
    final dangerColor = theme.colorScheme.error;
    final dangerBackground = dangerColor.withValues(alpha: 0.08);
    final dangerBorder = dangerColor.withValues(alpha: 0.4);

    return Scaffold(
      appBar: AppBar(title: const Text('Meu perfil')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Card(
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: _isLoadingUser
                      ? const Center(child: CircularProgressIndicator())
                      : user == null
                      ? Text(
                          'Não foi possível carregar os dados do usuário.',
                          style: theme.textTheme.bodyMedium,
                        )
                      : Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              user.username,
                              style: theme.textTheme.titleMedium?.copyWith(
                                fontSize: 20,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              user.email,
                              style: theme.textTheme.bodyMedium?.copyWith(
                                color: const Color(0xFF667085),
                              ),
                            ),
                          ],
                        ),
                ),
              ),
              const SizedBox(height: 24),
              Container(
                decoration: BoxDecoration(
                  color: dangerBackground,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: dangerBorder),
                ),
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Zona vermelha',
                      style: theme.textTheme.titleMedium?.copyWith(
                        color: dangerColor,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'Excluir sua conta remove definitivamente seus dados e acessos. Esta ação não pode ser desfeita.',
                      style: theme.textTheme.bodyMedium,
                    ),
                    const SizedBox(height: 20),
                    FilledButton(
                      style: FilledButton.styleFrom(
                        backgroundColor: dangerColor,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      onPressed: _isDeleting ? null : _confirmDeleteAccount,
                      child: _isDeleting
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                valueColor: AlwaysStoppedAnimation<Color>(
                                  Colors.white,
                                ),
                              ),
                            )
                          : const Text('Excluir conta'),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
