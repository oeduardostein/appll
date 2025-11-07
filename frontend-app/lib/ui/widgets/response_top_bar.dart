import 'package:flutter/material.dart';

class ResponseTopBar extends StatelessWidget implements PreferredSizeWidget {
  const ResponseTopBar({
    super.key,
    required this.title,
    this.subtitle,
    this.onShare,
    this.actions = const [],
  });

  final String title;
  final String? subtitle;
  final VoidCallback? onShare;
  final List<Widget> actions;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final canPop = Navigator.of(context).canPop();
    return AppBar(
      automaticallyImplyLeading: canPop,
      backgroundColor: theme.colorScheme.primary,
      elevation: 0,
      toolbarHeight: 108,
      titleSpacing: 0,
      iconTheme: const IconThemeData(color: Colors.white),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(28)),
      ),
      title: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (subtitle != null && subtitle!.trim().isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    subtitle!,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: Colors.white.withOpacity(0.85),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
      actions: [
        ...actions.map(
          (action) => IconTheme(
            data: const IconThemeData(color: Colors.white),
            child: action,
          ),
        ),
        if (onShare != null)
          IconButton(
            tooltip: 'Compartilhar',
            onPressed: onShare,
            icon: const Icon(Icons.share_outlined, color: Colors.white),
          ),
      ],
    );
  }

  @override
  Size get preferredSize => const Size.fromHeight(108);
}
