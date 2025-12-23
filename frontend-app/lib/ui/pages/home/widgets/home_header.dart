import 'package:flutter/material.dart';

class HomeHeader extends StatelessWidget {
  const HomeHeader({
    super.key,
    required this.userName,
    this.monthlyCreditsLabel,
    this.onLogout,
    this.onProfileTap,
  });

  final String userName;
  final String? monthlyCreditsLabel;
  final VoidCallback? onLogout;
  final VoidCallback? onProfileTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    final screenWidth = MediaQuery.of(context).size.width;

    return Container(
      width: screenWidth,
      decoration: BoxDecoration(
        color: colorScheme.primary,
        borderRadius: const BorderRadius.only(
          bottomLeft: Radius.circular(32),
          bottomRight: Radius.circular(32),
        ),
      ),
      padding: const EdgeInsets.fromLTRB(20, 28, 20, 36),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 28,
                backgroundColor: Colors.white,
                backgroundImage: const AssetImage('assets/images/logoLL.png'),
              ),
              const Spacer(),
              if (onProfileTap != null) ...[
                IconButton(
                  tooltip: 'Meu perfil',
                  onPressed: onProfileTap,
                  icon: const Icon(Icons.account_circle_outlined),
                  color: Colors.white,
                ),
                const SizedBox(width: 8),
              ],
              if (onLogout != null) ...[
                OutlinedButton.icon(
                  onPressed: onLogout,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side: const BorderSide(color: Colors.white24),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 10,
                    ),
                    textStyle: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                  icon: const Icon(Icons.logout, size: 18),
                  label: const Text('Sair'),
                ),
                const SizedBox(width: 12),
              ],
            ],
          ),
          const SizedBox(height: 24),
          Text(
            monthlyCreditsLabel != null
                ? 'Usuário: $userName • $monthlyCreditsLabel'
                : 'Usuário: $userName',
            style: theme.textTheme.titleMedium?.copyWith(color: Colors.white),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}
