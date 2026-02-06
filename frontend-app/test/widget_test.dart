// This is a basic Flutter widget test.
//
// To perform an interaction with a widget in your test, use the WidgetTester
// utility in the flutter_test package. For example, you can send tap and scroll
// gestures. You can also use WidgetTester to find child widgets in the widget
// tree, read text, and verify that the values of widget properties are correct.

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:frontend_app/models/pesquisa_models.dart';
import 'package:frontend_app/services/auth_service.dart';
import 'package:frontend_app/services/pesquisa_service.dart';
import 'package:frontend_app/ui/pages/home/home_page.dart';
import 'package:http/http.dart' as http;

class _FakeHttpClient extends http.BaseClient {
  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) {
    throw UnimplementedError();
  }
}

class _FakeAuthService extends AuthService {
  _FakeAuthService({AuthSession? session})
      : _session = session,
        super(httpClient: _FakeHttpClient(), baseUrl: 'https://localhost');

  AuthSession? _session;

  @override
  AuthSession? get session => _session;

  @override
  Future<AuthUser> fetchCurrentUser() async {
    final user = _session?.user;
    if (user == null) {
      throw AuthException('Não autenticado.');
    }
    return user;
  }

  @override
  Future<void> logout() async {
    _session = null;
  }

  @override
  void clearSession({bool clearPersisted = false}) {
    _session = null;
  }
}

class _FakePesquisaService extends PesquisaService {
  _FakePesquisaService({
    required this.items,
    List<PesquisaResumo>? monthlyItems,
    required AuthService authService,
  })  : monthlyItems = monthlyItems ?? items,
        super(client: _FakeHttpClient(), authService: authService);

  final List<PesquisaResumo> items;
  final List<PesquisaResumo> monthlyItems;

  @override
  Future<List<PesquisaResumo>> listarRecentes() async {
    return items;
  }

  @override
  Future<List<PesquisaResumo>> listarUltimoMes() async {
    return monthlyItems;
  }
}

void main() {
  testWidgets('renders home header and sections', (tester) async {
    final fakeUser = AuthUser(
      id: 1,
      username: 'Lucas',
      email: 'lucas@example.com',
    );
    final fakeSession = AuthSession(token: 'token', user: fakeUser);
    final authService = _FakeAuthService(session: fakeSession);
    final pesquisas = [
      PesquisaResumo(
        id: 10,
        nome: 'Base estadual',
        placa: 'AAA1A23',
        renavam: '12345678901',
        chassi: '9BRBLWHEXAMPLE123',
        opcaoPesquisa: '1',
        createdAt: DateTime(2024, 1, 10, 8, 30),
      ),
    ];
    final pesquisaService =
        _FakePesquisaService(items: pesquisas, authService: authService);

    await tester.pumpWidget(
      MaterialApp(
        home: HomePage(
          authService: authService,
          pesquisaService: pesquisaService,
        ),
      ),
    );

    await tester.pumpAndSettle();

    expect(find.textContaining('Usuário: Lucas'), findsOneWidget);
    expect(find.textContaining('Créditos usados este mês: 1'), findsOneWidget);
    expect(find.text('Consultar'), findsOneWidget);
    expect(find.text('Últimos veículos pesquisados'), findsOneWidget);
    expect(find.text('AAA1A23'), findsOneWidget);
    expect(find.textContaining('Renavam: 12345678901'), findsOneWidget);
    expect(find.text('Base estadual'), findsNothing);

    await tester.tap(find.text('Pesquisas'));
    await tester.pumpAndSettle();

    expect(find.text('Base estadual'), findsWidgets);

    await tester.tap(find.text('Base estadual').last);
    await tester.pumpAndSettle();

    expect(find.text('Informações do veículo'), findsOneWidget);

    await tester.tap(find.text('Gravame').last);
    await tester.pumpAndSettle();

    expect(find.text('Gravame: Ativo'), findsOneWidget);
  });
}
