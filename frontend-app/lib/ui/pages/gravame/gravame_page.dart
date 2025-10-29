import 'package:flutter/material.dart';

import '../shared/common_page_header.dart';
import '../shared/gravame_details_card.dart';

class GravamePage extends StatelessWidget {
  const GravamePage({super.key});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: [
              const CommonPageHeader(title: 'Gravame', bottomPadding: 24),
              const SizedBox(height: 16),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: GravameDetailsCard(
                  iconBackgroundColor: colorScheme.primary,
                  iconColor: Colors.white,
                ),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }
}
