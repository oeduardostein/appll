import 'package:flutter/material.dart';

import '../base_state/base_estadual_page.dart';

class BinResultPage extends StatelessWidget {
  const BinResultPage({
    super.key,
    required this.placa,
    required this.renavam,
    required this.payload,
  });

  final String placa;
  final String renavam;
  final Map<String, dynamic> payload;

  @override
  Widget build(BuildContext context) {
    return BaseEstadualPage(
      placa: placa,
      renavam: renavam,
      payload: payload,
      pageTitle: 'Pesquisa BIN',
    );
  }
}
