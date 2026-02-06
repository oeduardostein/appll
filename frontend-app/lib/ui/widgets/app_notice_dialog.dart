import 'package:flutter/material.dart';

class AppNoticeDialog {
  const AppNoticeDialog._();

  static Future<void> show(
    BuildContext context, {
    required String message,
    String? title,
  }) {
    final effectiveTitle = title ?? 'Aviso';

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
            FilledButton(
              onPressed: () => Navigator.of(dialogContext).pop(),
              child: const Text('Entendi'),
            ),
          ],
        );
      },
    );
  }
}

