import 'package:flutter/material.dart';

class RestrictionEntry {
  const RestrictionEntry({
    required this.icon,
    required this.label,
    required this.status,
  });

  final IconData icon;
  final String label;
  final String status;
}

const defaultRestrictionEntries = [
  RestrictionEntry(
    icon: Icons.security_outlined,
    label: 'Furto',
    status: 'Não consta',
  ),
  RestrictionEntry(
    icon: Icons.local_shipping_outlined,
    label: 'Guincho',
    status: 'Não consta',
  ),
  RestrictionEntry(
    icon: Icons.folder_outlined,
    label: 'Administrativo',
    status: 'Não consta',
  ),
  RestrictionEntry(
    icon: Icons.gavel_outlined,
    label: 'Judicial',
    status: 'Não consta',
  ),
  RestrictionEntry(
    icon: Icons.description_outlined,
    label: 'Renajud',
    status: 'Não consta',
  ),
  RestrictionEntry(
    icon: Icons.eco_outlined,
    label: 'Inspeção Ambiental',
    status: 'Não consta',
  ),
];

class RestrictionListCard extends StatelessWidget {
  const RestrictionListCard({
    super.key,
    this.entries = defaultRestrictionEntries,
  });

  final List<RestrictionEntry> entries;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: const [
          BoxShadow(
            color: Color(0x0D101828),
            blurRadius: 18,
            offset: Offset(0, 10),
          ),
        ],
      ),
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Column(
        children: [
          for (var i = 0; i < entries.length; i++) ...[
            if (i > 0)
              const Divider(
                height: 1,
                thickness: 1,
                indent: 20,
                endIndent: 20,
                color: Color(0xFFE4E7EC),
              ),
            ListTile(
              leading: Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFF8F9FC),
                  borderRadius: BorderRadius.circular(14),
                ),
                padding: const EdgeInsets.all(12),
                child: Icon(
                  entries[i].icon,
                  color: const Color(0xFF667085),
                  size: 24,
                ),
              ),
              title: Text(
                entries[i].label,
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                  color: const Color(0xFF1D2939),
                ),
              ),
              trailing: Text(
                entries[i].status,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: const Color(0xFF039855),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
