import 'package:flutter/material.dart';

import '../../../services/auth_service.dart';
import '../auth/login_page.dart';
import '../shared/loading_dialog.dart';
import '../../widgets/app_error_dialog.dart';
import '../../widgets/response_top_bar.dart';

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
    final gradientEnd = theme.colorScheme.primaryContainer;

    return Scaffold(
      appBar: ResponseTopBar(
        title: 'Meu perfil',
        subtitle: 'Gerencie suas informações',
        actions: [
          IconButton(
            tooltip: 'Atualizar dados',
            onPressed: _isLoadingUser ? null : _loadUser,
            icon: const Icon(Icons.refresh_outlined),
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _ProfileHeroCard(
                user: user,
                isLoading: _isLoadingUser,
                gradientStart: theme.colorScheme.primary,
                gradientEnd: gradientEnd,
              ),
              const SizedBox(height: 20),
              const _SecurityTipsCard(),
              const SizedBox(height: 24),
              _DangerZoneCard(
                dangerBackground: dangerBackground,
                dangerBorder: dangerBorder,
                dangerColor: dangerColor,
                isDeleting: _isDeleting,
                onDelete: _confirmDeleteAccount,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProfileHeroCard extends StatelessWidget {
  const _ProfileHeroCard({
    required this.user,
    required this.isLoading,
    required this.gradientStart,
    required this.gradientEnd,
  });

  final AuthUser? user;
  final bool isLoading;
  final Color gradientStart;
  final Color gradientEnd;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final Widget content;
    if (isLoading) {
      content = const SizedBox(
        height: 120,
        child: Center(child: CircularProgressIndicator(color: Colors.white)),
      );
    } else if (user == null) {
      content = Text(
        'Não foi possível carregar os dados do usuário.',
        style: theme.textTheme.bodyMedium?.copyWith(color: Colors.white),
      );
    } else {
      final safeUser = user!;
      final trimmedName = safeUser.username.trim();
      final avatarLabel = trimmedName.isNotEmpty
          ? trimmedName.substring(0, 1).toUpperCase()
          : '?';

      content = Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 28,
            backgroundColor: Colors.white.withValues(alpha: 0.15),
            child: Text(
              avatarLabel,
              style: theme.textTheme.titleLarge?.copyWith(
                color: Colors.white,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const SizedBox(height: 16),
          Text(
            safeUser.username,
            style: theme.textTheme.headlineSmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            safeUser.email,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: Colors.white.withValues(alpha: 0.85),
            ),
          ),
          const SizedBox(height: 12),
          Container(
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(16),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.badge_outlined, color: Colors.white, size: 20),
                const SizedBox(width: 8),
                Text(
                  'ID do usuário: ${safeUser.id}',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ],
      );
    }

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [gradientStart, gradientEnd],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: gradientStart.withValues(alpha: 0.25),
            blurRadius: 24,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      padding: const EdgeInsets.all(24),
      child: content,
    );
  }
}

class _SecurityTipsCard extends StatelessWidget {
  const _SecurityTipsCard();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final tips = const [
      'Mantenha seu email atualizado para receber avisos importantes.',
      'Use senhas fortes e altere-as regularmente.',
      'Finalize a sessão ao utilizar dispositivos compartilhados.',
    ];

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFFF8F9FB),
        borderRadius: BorderRadius.circular(24),
      ),
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Boas práticas de segurança',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 12),
          for (final tip in tips)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(
                    Icons.check_circle_outline,
                    color: theme.colorScheme.primary,
                    size: 18,
                  ),
                  const SizedBox(width: 10),
                  Expanded(child: Text(tip, style: theme.textTheme.bodyMedium)),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _DangerZoneCard extends StatelessWidget {
  const _DangerZoneCard({
    required this.dangerBackground,
    required this.dangerBorder,
    required this.dangerColor,
    required this.isDeleting,
    required this.onDelete,
  });

  final Color dangerBackground;
  final Color dangerBorder;
  final Color dangerColor;
  final bool isDeleting;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) {
    return Container(
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
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              color: dangerColor,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            'Excluir sua conta remove definitivamente seus dados e acessos. Esta ação não pode ser desfeita.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 20),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: dangerColor,
              minimumSize: const Size.fromHeight(58),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
            ),
            onPressed: isDeleting ? null : onDelete,
            child: isDeleting
                ? const SizedBox(
                    height: 22,
                    width: 22,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  )
                : const Text('Excluir conta', textAlign: TextAlign.center),
          ),
        ],
      ),
    );
  }
}
