import 'package:flutter/material.dart';

import '../shared/common_page_header.dart';
import '../shared/gravame_details_card.dart';

class GravameOutrosEstadosPage extends StatelessWidget {
  const GravameOutrosEstadosPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: const [
              CommonPageHeader(title: 'Gravame'),
              SizedBox(height: 20),
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 20),
                child: GravameDetailsCard(highlightBorder: true),
              ),
              SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }
}
