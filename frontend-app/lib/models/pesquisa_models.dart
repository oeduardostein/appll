class PesquisaResumo {
  const PesquisaResumo({
    required this.id,
    required this.nome,
    this.placa,
    this.renavam,
    this.chassi,
    this.opcaoPesquisa,
    required this.createdAt,
  });

  final int id;
  final String nome;
  final String? placa;
  final String? renavam;
  final String? chassi;
  final String? opcaoPesquisa;
  final DateTime createdAt;

  factory PesquisaResumo.fromJson(Map<String, dynamic> json) {
    return PesquisaResumo(
      id: json['id'] is int ? json['id'] as int : int.tryParse('${json['id']}') ?? 0,
      nome: (json['nome'] as String?)?.trim() ?? 'Pesquisa',
      placa: _stringOrNull(json['placa']),
      renavam: _stringOrNull(json['renavam']),
      chassi: _stringOrNull(json['chassi']),
      opcaoPesquisa: _stringOrNull(json['opcao_pesquisa']),
      createdAt: _parseDate(json['created_at']) ?? DateTime.now(),
    );
  }

  static String? _stringOrNull(Object? value) {
    if (value == null) return null;
    final text = value.toString().trim();
    return text.isEmpty ? null : text;
  }

  static DateTime? _parseDate(Object? value) {
    if (value is DateTime) return value;
    if (value is String && value.isNotEmpty) {
      return DateTime.tryParse(value);
    }
    return null;
  }
}
