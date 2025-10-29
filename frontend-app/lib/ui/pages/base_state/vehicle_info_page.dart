import 'package:flutter/material.dart';

import '../shared/common_page_header.dart';
import '../shared/vehicle_info_content.dart';

class VehicleInfoPage extends StatelessWidget {
  const VehicleInfoPage({super.key});

  @override
  Widget build(BuildContext context) {
    const summary = VehicleSummaryData(
      plate: 'BUL8C39',
      description: 'Hyundai HB20 Vision\n2021/2022',
      chips: [
        VehicleSummaryChip(label: 'Cor', value: 'Prata'),
        VehicleSummaryChip(label: 'Combustível', value: 'Álcool/Gasolina'),
        VehicleSummaryChip(
          label: 'Licenciamento',
          value: '2024',
          status: 'Em dia',
        ),
      ],
    );

    const sections = [
      VehicleInfoSectionData(
        title: 'Identificação',
        rows: [
          VehicleInfoRowData(
            leftLabel: 'Chassi',
            leftValue: '9BHCUS1AANP186618',
            rightLabel: 'Motor',
            rightValue: 'F3LALU465926',
          ),
          VehicleInfoRowData(
            leftLabel: 'Renavam',
            leftValue: '01265660120',
            rightLabel: 'Procedência',
            rightValue: 'Nacional',
          ),
        ],
      ),
      VehicleInfoSectionData(
        title: 'Especificações',
        rows: [
          VehicleInfoRowData(
            leftLabel: 'Tipo',
            leftValue: 'Automóvel',
            rightLabel: 'Espécie',
            rightValue: 'Passageiro',
          ),
          VehicleInfoRowData(
            leftLabel: 'Categoria',
            leftValue: 'Particular',
            rightLabel: 'Carroceria',
            rightValue: 'Nenhuma',
          ),
        ],
      ),
      VehicleInfoSectionData(
        title: 'Performance',
        rows: [
          VehicleInfoRowData(
            leftLabel: 'Cilindradas',
            leftValue: '998',
            rightLabel: 'Passageiros',
            rightValue: '5',
          ),
          VehicleInfoRowData(
            leftLabel: 'Capacidade de carga',
            leftValue: '0,00',
            rightLabel: 'C.M.T.',
            rightValue: '1,82',
          ),
          VehicleInfoRowData(
            leftLabel: 'P.B.T.',
            leftValue: '1,42',
            rightLabel: 'Potência',
            rightValue: '80 cv',
          ),
          VehicleInfoRowData(
            leftLabel: 'Eixos',
            leftValue: '0',
            rightLabel: '',
            rightValue: '',
          ),
        ],
      ),
      VehicleInfoSectionData(
        title: 'Administrativo',
        rows: [
          VehicleInfoRowData(
            leftLabel: 'Licenciamento',
            leftValue: '24/04/2025',
            rightLabel: 'Emissão CRV',
            rightValue: '14/08/2023',
          ),
          VehicleInfoRowData(
            leftLabel: 'Proprietário anterior',
            leftValue: 'GWB Distribuidora de Veículos LTDA',
            rightLabel: '',
            rightValue: '',
          ),
        ],
      ),
    ];

    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: const [
              CommonPageHeader(title: 'Informações do veículo'),
              SizedBox(height: 20),
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 20),
                child: VehicleInfoContent(summary: summary, sections: sections),
              ),
              SizedBox(height: 28),
            ],
          ),
        ),
      ),
    );
  }
}
