import 'package:flutter/material.dart';

class AppErrorDialog {
  const AppErrorDialog._();

  static Future<void> show(
    BuildContext context, {
    required String message,
    String? title,
    VoidCallback? onBack,
  }) {
    final effectiveTitle = title ?? 'Algo deu errado';

    return showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return AlertDialog(
          title: Text(
            effectiveTitle,
            style: Theme.of(dialogContext).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
          ),
          content: Text(
            message,
            style: Theme.of(dialogContext).textTheme.bodyMedium,
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(dialogContext).pop();
                if (onBack != null) {
                  onBack();
                  return;
                }
                final navigator = Navigator.of(context);
                if (navigator.canPop()) {
                  navigator.pop();
                }
              },
              child: const Text('Voltar'),
            ),
            FilledButton(
              onPressed: () {
                Navigator.of(dialogContext).pop();
              },
              child: const Text('Entendi'),
            ),
          ],
        );
      },
    );
  }
}
