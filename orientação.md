# Agente do Projeto — Versão Simplificada

> Quando eu disser “Agente, siga o guia e faça X”, você deve **entender e executar** o pedido conforme este arquivo.

---

## Estrutura do Projeto

* **Frontend (Flutter):** `frontend-app/`
* **Backend (Laravel + painel admin):** `backend/`
* **API Base URL:** `https://applldespachante.skalacode.com`

---

## Função do Agente

1. Entender o pedido (ex.: criar feature, corrigir bug, gerar migration, etc.).
2. Aplicar o contexto do projeto (Flutter + Laravel).
3. Entregar:

   * **Passos e comandos** (sem executar de fato)
   * **Arquivos e código/diff prontos**
   * **Teste e validação básica**
   * **Mensagem de commit**

> Tudo deve ser entregue na resposta atual, de forma curta e prática.

---

## Padrão de Resposta

**Formato simplificado:**

1. **Resumo**
2. **Passos rápidos**
3. **Comandos**
4. **Arquivos e Código/Diff**
5. **Validação/Commit**

---

## Comandos Essenciais

### Flutter (`frontend-app/`)

```bash
cd frontend-app
flutter pub get
flutter run
flutter build apk --release
flutter analyze
flutter test
```

### Laravel (`backend/`)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan test
```

> Todas as requisições externas do backend devem usar a base URL `https://applldespachante.skalacode.com`.

---

## Convenções

* **Banco:** PostgreSQL padrão.
* **Autenticação:** Laravel Sanctum.
* **State management Flutter:** Riverpod (ou Bloc se já usado).
* **Git:** `main` e `feature/*`.

---

## Commit Types

* `feat:` nova feature
* `fix:` correção
* `docs:` documentação
* `test:` testes
* `refactor:` refatoração

---

## Como Usar

Diga:

* “Agente, siga o guia e crie endpoint X.”
* “Agente, gere tela Flutter Y.”
* “Agente, corrija bug Z.”

E eu responderei de forma direta seguindo este padrão simplificado e usando como base de API o domínio **`https://applldespachante.skalacode.com`**.
