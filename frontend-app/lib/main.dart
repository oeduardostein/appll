import 'package:flutter/material.dart';

import 'ui/pages/auth/forgot_password_request_page.dart';
import 'ui/pages/auth/login_page.dart';
import 'ui/pages/auth/register_page.dart';
import 'ui/pages/home/home_page.dart';
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
      initialRoute: LoginPage.routeName,
      routes: {
        LoginPage.routeName: (_) => const LoginPage(),
        RegisterPage.routeName: (_) => const RegisterPage(),
        ForgotPasswordRequestPage.routeName: (_) =>
            const ForgotPasswordRequestPage(),
        HomePage.routeName: (_) => const HomePage(),
      },
    );
  }
}
