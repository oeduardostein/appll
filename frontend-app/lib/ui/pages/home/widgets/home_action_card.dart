import 'package:flutter/material.dart';

import '../home_models.dart';
import 'home_action_sub_item.dart';

class HomeActionCard extends StatelessWidget {
  const HomeActionCard({
    super.key,
    required this.action,
    this.onTap,
    this.isExpanded = false,
    this.onSubActionTap,
  });

  final HomeAction action;
  final bool isExpanded;
  final VoidCallback? onTap;
  final ValueChanged<HomeSubAction>? onSubActionTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final hasSubActions = action.subActions.isNotEmpty;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(20),
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          decoration: BoxDecoration(
            color: const Color(0xFFE7EDFF),
            borderRadius: BorderRadius.circular(20),
            boxShadow: [
              BoxShadow(
                color: const Color(0x140E3B91),
                blurRadius: 12,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Padding(
                padding: const EdgeInsets.all(18),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                      ),
                      padding: const EdgeInsets.all(12),
                      child: Icon(
                        action.icon,
                        color: colorScheme.primary,
                        size: 28,
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            action.title,
                            style: theme.textTheme.titleMedium,
                          ),
                          const SizedBox(height: 4),
                          Text(
                            action.description,
                            style: theme.textTheme.bodyMedium,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    if (hasSubActions)
                      Icon(
                        isExpanded
                            ? Icons.keyboard_arrow_up_rounded
                            : Icons.keyboard_arrow_right_rounded,
                        color: Colors.indigo.shade400,
                      ),
                  ],
                ),
              ),
              if (hasSubActions && isExpanded)
                Container(
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.vertical(
                      bottom: Radius.circular(20),
                    ),
                  ),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
                  child: Column(
                    children: [
                      for (var i = 0; i < action.subActions.length; i++) ...[
                        if (i > 0)
                          const Divider(
                            height: 18,
                            color: Color(0xFFE4E7EC),
                          ),
                        HomeActionSubItem(
                          subAction: action.subActions[i],
                          onTap: () => onSubActionTap?.call(action.subActions[i]),
                        ),
                      ],
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
