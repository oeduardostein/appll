import 'package:flutter/material.dart';

import 'services/auth_service.dart';
import 'ui/pages/auth/forgot_password_request_page.dart';
import 'ui/pages/auth/login_page.dart';
import 'ui/pages/auth/register_page.dart';
import 'ui/pages/home/home_page.dart';
import 'ui/pages/profile/profile_page.dart';
import 'ui/theme/app_theme.dart';

void main() {
  runApp(const FrontendApp());
}

class FrontendApp extends StatelessWidget {
  const FrontendApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'frontend-app',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.theme,
      home: const AuthGate(),
      routes: {
        LoginPage.routeName: (_) => const LoginPage(),
        RegisterPage.routeName: (_) => const RegisterPage(),
        ForgotPasswordRequestPage.routeName: (_) =>
            const ForgotPasswordRequestPage(),
        HomePage.routeName: (_) => const HomePage(),
        ProfilePage.routeName: (_) => const ProfilePage(),
      },
    );
  }
}

class AuthGate extends StatefulWidget {
  const AuthGate({super.key});

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  final AuthService _authService = AuthService();
  late final Future<bool> _restoreFuture = _authService.restoreSession();

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<bool>(
      future: _restoreFuture,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Scaffold(
            body: Center(child: CircularProgressIndicator()),
          );
        }
        final hasSession = snapshot.data ?? false;
        return hasSession ? const HomePage() : const LoginPage();
      },
    );
  }
}
