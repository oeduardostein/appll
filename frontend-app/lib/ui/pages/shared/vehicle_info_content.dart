import 'package:flutter/material.dart';

class VehicleSummaryData {
  const VehicleSummaryData({
    required this.plate,
    required this.description,
    this.chips = const [],
  });

  final String plate;
  final String description;
  final List<VehicleSummaryChip> chips;
}

class VehicleSummaryChip {
  const VehicleSummaryChip({
    required this.label,
    required this.value,
    this.status,
  });

  final String label;
  final String value;
  final String? status;
}

class VehicleInfoSectionData {
  const VehicleInfoSectionData({required this.title, required this.rows});

  final String title;
  final List<VehicleInfoRowData> rows;
}

class VehicleInfoRowData {
  const VehicleInfoRowData({
    required this.leftLabel,
    required this.leftValue,
    required this.rightLabel,
    required this.rightValue,
  });

  final String leftLabel;
  final String leftValue;
  final String rightLabel;
  final String rightValue;
}

class VehicleInfoContent extends StatelessWidget {
  const VehicleInfoContent({
    super.key,
    required this.summary,
    required this.sections,
  });

  final VehicleSummaryData summary;
  final List<VehicleInfoSectionData> sections;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        VehicleSummaryCard(summary: summary),
        const SizedBox(height: 16),
        for (var i = 0; i < sections.length; i++) ...[
          VehicleInfoSection(section: sections[i]),
          if (i < sections.length - 1) const SizedBox(height: 16),
        ],
      ],
    );
  }
}

class VehicleSummaryCard extends StatelessWidget {
  const VehicleSummaryCard({super.key, required this.summary});

  final VehicleSummaryData summary;

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
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFF2F6FF),
                  borderRadius: BorderRadius.circular(18),
                ),
                padding: const EdgeInsets.all(16),
                child: const Icon(
                  Icons.directions_car_filled_outlined,
                  color: Color(0xFF123D99),
                  size: 36,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      summary.plate,
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      summary.description,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: const Color(0xFF475467),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (summary.chips.isNotEmpty) ...[
            const SizedBox(height: 16),
            const Divider(color: Color(0xFFE4E7EC)),
            const SizedBox(height: 16),
            Wrap(
              spacing: 16,
              runSpacing: 12,
              children: summary.chips
                  .map(
                    (chip) => SizedBox(
                      width: (MediaQuery.of(context).size.width - 56) / 2,
                      child: _SummaryChip(chip: chip),
                    ),
                  )
                  .toList(),
            ),
          ],
        ],
      ),
    );
  }
}

class _SummaryChip extends StatelessWidget {
  const _SummaryChip({required this.chip});

  final VehicleSummaryChip chip;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          chip.label,
          style: theme.textTheme.bodySmall?.copyWith(
            color: const Color(0xFF667085),
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          chip.value,
          style: theme.textTheme.bodyMedium?.copyWith(
            color: const Color(0xFF1D2939),
            fontWeight: FontWeight.w600,
          ),
        ),
        if (chip.status != null) ...[
          const SizedBox(height: 4),
          Text(
            chip.status!,
            style: theme.textTheme.bodySmall?.copyWith(
              color: const Color(0xFF039855),
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ],
    );
  }
}

class VehicleInfoSection extends StatelessWidget {
  const VehicleInfoSection({super.key, required this.section});

  final VehicleInfoSectionData section;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      width: double.infinity,
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
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            section.title,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
              color: const Color(0xFF1849A9),
            ),
          ),
          const SizedBox(height: 16),
          Column(
            children: [
              for (var i = 0; i < section.rows.length; i++) ...[
                if (i > 0) const SizedBox(height: 16),
                _InfoRow(row: section.rows[i]),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.row});

  final VehicleInfoRowData row;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    Widget column(String label, String value) {
      return Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label,
              style: theme.textTheme.bodySmall?.copyWith(
                color: const Color(0xFF667085),
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              value,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: const Color(0xFF1D2939),
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      );
    }

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        column(row.leftLabel, row.leftValue),
        const SizedBox(width: 20),
        column(row.rightLabel, row.rightValue),
      ],
    );
  }
}
