import 'package:flutter/material.dart';

class AuthBackButton extends StatelessWidget {
  const AuthBackButton({
    super.key,
    required this.label,
    required this.onPressed,
  });

  final String label;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final color = theme.colorScheme.primary;

    return TextButton.icon(
      onPressed: onPressed,
      icon: Icon(
        Icons.arrow_back_ios_new,
        size: 18,
        color: color,
      ),
      label: Text(
        label,
        style: theme.textTheme.bodyMedium?.copyWith(
          color: color,
          fontWeight: FontWeight.w600,
        ),
      ),
      style: TextButton.styleFrom(
        padding: EdgeInsets.zero,
        minimumSize: const Size(0, 0),
        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
        alignment: Alignment.centerLeft,
      ),
    );
  }
}
