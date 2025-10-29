// This is a basic Flutter widget test.
//
// To perform an interaction with a widget in your test, use the WidgetTester
// utility in the flutter_test package. For example, you can send tap and scroll
// gestures. You can also use WidgetTester to find child widgets in the widget
// tree, read text, and verify that the values of widget properties are correct.

import 'package:flutter_test/flutter_test.dart';

import 'package:frontend_app/main.dart';

void main() {
  testWidgets('renders home header and sections', (tester) async {
    await tester.pumpWidget(const FrontendApp());

    expect(find.text('Usuário: Lucas'), findsOneWidget);
    expect(find.text('Consultar'), findsOneWidget);
    expect(find.text('Últimos veículos pesquisados'), findsOneWidget);
    expect(find.text('Base estadual'), findsNothing);

    await tester.tap(find.text('Pesquisas'));
    await tester.pumpAndSettle();

    expect(find.text('Base estadual'), findsOneWidget);

    await tester.tap(find.text('Base estadual').last);
    await tester.pumpAndSettle();

    expect(find.text('Informações do veículo'), findsOneWidget);

    await tester.tap(find.text('Gravame').last);
    await tester.pumpAndSettle();

    expect(find.text('Gravame: Ativo'), findsOneWidget);
  });
}
