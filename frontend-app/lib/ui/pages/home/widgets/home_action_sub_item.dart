import 'package:flutter/material.dart';

import '../home_models.dart';

class HomeActionSubItem extends StatelessWidget {
  const HomeActionSubItem({
    super.key,
    required this.subAction,
    this.onTap,
  });

  final HomeSubAction subAction;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 8),
          child: Row(
            children: [
              Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFE7EDFF),
                  borderRadius: BorderRadius.circular(12),
                ),
                padding: const EdgeInsets.all(10),
                child: Icon(
                  subAction.icon,
                  size: 20,
                  color: colorScheme.primary,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Text(
                  subAction.label,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: const Color(0xFF1D2939),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
              Icon(
                Icons.keyboard_arrow_right_rounded,
                color: Colors.indigo.shade300,
                size: 22,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
