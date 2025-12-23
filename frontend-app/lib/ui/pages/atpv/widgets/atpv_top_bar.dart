import 'package:flutter/material.dart';

class AtpvTopBar extends StatelessWidget implements PreferredSizeWidget {
  const AtpvTopBar({
    super.key,
    required this.title,
    this.subtitle = 'Confira os dados antes de enviar',
  });

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final canPop = ModalRoute.of(context)?.canPop ?? false;

    return AppBar(
      automaticallyImplyLeading: canPop,
      backgroundColor: theme.colorScheme.primary,
      elevation: 0,
      toolbarHeight: 110,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(32)),
      ),
      iconTheme: const IconThemeData(color: Colors.white),
      titleSpacing: 0,
      title: Row(
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: Colors.white,
              shape: BoxShape.circle,
              image: const DecorationImage(
                image: AssetImage('assets/images/logoLL.png'),
                fit: BoxFit.cover,
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.08),
                  blurRadius: 10,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: Colors.white.withOpacity(0.85),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  @override
  Size get preferredSize => const Size.fromHeight(110);
}
