import 'package:flutter/material.dart';

import '../home_models.dart';

class RecentVehicleCard extends StatelessWidget {
  const RecentVehicleCard({super.key, required this.vehicle});

  final RecentVehicle vehicle;

  String _formatDate(DateTime dateTime) {
    final day = dateTime.day.toString().padLeft(2, '0');
    final month = dateTime.month.toString().padLeft(2, '0');
    final year = dateTime.year.toString();
    final hour = dateTime.hour.toString().padLeft(2, '0');
    final minute = dateTime.minute.toString().padLeft(2, '0');
    return '$day/$month/$year – $hour:$minute';
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return AspectRatio(
      aspectRatio: 16 / 6,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: const Color(0x0D101828),
              blurRadius: 12,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        padding: const EdgeInsets.fromLTRB(20, 18, 20, 20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              vehicle.plate,
              style: theme.textTheme.headlineSmall?.copyWith(
                fontSize: 20,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 12),
            Expanded(
              child: Text(
                vehicle.summary,
                style: theme.textTheme.bodyLarge?.copyWith(
                  fontSize: 16,
                  height: 1.4,
                ),
              ),
            ),
            const SizedBox(height: 16),
            Align(
              alignment: Alignment.bottomRight,
              child: Text(
                _formatDate(vehicle.dateTime),
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: const Color(0xFF667085),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
