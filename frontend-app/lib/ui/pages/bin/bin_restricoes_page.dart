import 'package:flutter/material.dart';

import '../shared/common_page_header.dart';
import '../shared/restriction_list_card.dart';

class BinRestricoesPage extends StatelessWidget {
  const BinRestricoesPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      body: SafeArea(
        child: Column(
          children: [
            const CommonPageHeader(title: 'Restrições'),
            const SizedBox(height: 20),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: const RestrictionListCard(),
              ),
            ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }
}
