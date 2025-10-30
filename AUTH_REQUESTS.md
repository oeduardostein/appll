# Autenticação: Fluxo de Requisições

Este documento descreve as requisições de autenticação disponíveis no backend Laravel e como o app Flutter (`frontend-app`) monta essas chamadas para cadastro, login e logout.

## Backend (Laravel)

Todas as rotas de autenticação ficam no arquivo `backend/routes/api.php` sob o prefixo `/api/auth`. O controlador responsável é `backend/app/Http/Controllers/Api/Auth/AuthController.php`.

### Cabeçalhos esperados

| Header               | Valor                         | Motivo                          |
|----------------------|--------------------------------|---------------------------------|
| `Content-Type`       | `application/json`             | Todas as rotas recebem JSON     |
| `Accept`             | `application/json`             | Respostas padronizadas em JSON  |
| `X-Requested-With`   | `XMLHttpRequest` (opcional)    | Usado pelo cliente Flutter      |
| `Authorization`      | `Bearer {token}`               | Necessário para rotas protegidas (`user`, `logout`) |

> O token é emitido pelo backend no login/cadastro e deve ser guardado pelo cliente. Envie-o no header `Authorization` para operações autenticadas.

### 1. Cadastro (`POST /api/auth/register`)

- **Body JSON**

```json
{
  "username": "nome-de-usuario",
  "email": "email@exemplo.com",
  "password": "senha123",
  "password_confirmation": "senha123"
}
```

- **Resposta (201)**

```json
{
  "status": "success",
  "message": "Usuário cadastrado com sucesso.",
  "user": {
    "id": 1,
    "username": "nome-de-usuario",
    "email": "email@exemplo.com"
  },
  "token": "token-em-texto-plano",
  "redirect_to": "home"
}
```

- **Validação**: se algum campo falhar, retorna `422` com `status: "error"` e mensagens em `errors`.

### 2. Login (`POST /api/auth/login`)

- **Body JSON**

```json
{
  "identifier": "usuario-ou-email",
  "password": "senha123"
}
```

> O campo `identifier` aceita o nome de usuário (`name`) ou o e-mail cadastrado.

- **Resposta (200)**

```json
{
  "status": "success",
  "message": "Login realizado com sucesso.",
  "user": {
    "id": 1,
    "username": "nome-de-usuario",
    "email": "email@exemplo.com"
  },
  "token": "token-em-texto-plano",
  "redirect_to": "home"
}
```

- **Falha de credenciais**: retorna `422` com `status: "error"` e mensagem `"Credenciais inválidas."`.

### 3. Logout (`POST /api/auth/logout`)

O backend invalida o token atual. Exige o header `Authorization: Bearer {token}`.

- **Body JSON**: enviar um objeto vazio (`{}`).

- **Resposta (200)**

```json
{
  "status": "success",
  "message": "Logout realizado com sucesso.",
  "redirect_to": "login"
}
```

Retorna `401` com `status: "error"` se o token estiver ausente ou inválido.

### 4. Usuário autenticado (`GET /api/auth/user`)

- **Headers**: incluir `Authorization: Bearer {token}`.
- **Resposta (200)**

```json
{
  "status": "success",
  "user": {
    "id": 1,
    "username": "nome-de-usuario",
    "email": "email@exemplo.com",
    "created_at": "2025-10-30T18:00:00.000000Z",
    "updated_at": "2025-10-30T18:00:00.000000Z"
  }
}
```

- **Sem token**: responde `401` com `status: "error"` e mensagem `"Não autenticado."`.

## Frontend (Flutter)

O serviço de autenticação fica em `frontend-app/lib/services/auth_service.dart`. Ele usa a biblioteca `http` e constrói as URLs a partir da variável `BACKEND_BASE_URL`.

- **Base URL**: definida pelo construtor ou pela constante de ambiente de compilação. Valor padrão:

```dart
const String.fromEnvironment(
  'BACKEND_BASE_URL',
  defaultValue: 'https://applldespachante.skalacode.com',
);
```

- **Headers enviados** (em todas as chamadas):

```dart
{
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'X-Requested-With': 'XMLHttpRequest',
}
```

- **Cabeçalhos autorizados**: quando o usuário tem um token ativo, o serviço adiciona automaticamente

```dart
{
  ...headers,
  'Authorization': 'Bearer $token',
}
```

- **Sessão em memória**: `AuthService` guarda o `AuthSession` mais recente (token + usuário). Em caso de erro de autenticação, chame `authService.clearSession()` para limpar o estado local.

- **Método `register`**
  - Chama POST `$_baseUrl/api/auth/register`.
  - Envia o corpo JSON com `username`, `email`, `password`, `password_confirmation`.
  - Retorna um `AuthSession` com token e usuário.

- **Método `login`**
  - Chama POST `$_baseUrl/api/auth/login`.
  - Envia `identifier` e `password`.
  - Retorna um `AuthSession` com token e usuário.

- **Método `logout`**
  - Chama POST `$_baseUrl/api/auth/logout`.
  - Envia corpo `{}` e header `Authorization` com o token atual.

- **Método `fetchCurrentUser`**
  - Chama GET `$_baseUrl/api/auth/user`.
  - Retorna o usuário autenticado e atualiza o `AuthSession` interno.

Cada resposta passa por `_handleResponse`, que:

1. Considera sucesso apenas status `200` ou `201` com `status: "success"` no payload.
2. Lança `AuthException` com mensagem amigável em caso de validação (`422`) ou outros códigos de erro.

## Referências rápidas

| Ação    | Método | Rota                 | Body                                                                              | Headers                                     | Sucesso |
|---------|--------|----------------------|-----------------------------------------------------------------------------------|---------------------------------------------|---------|
| Cadastro| POST   | `/api/auth/register` | `{ "username": "...", "email": "...", "password": "...", "password_confirmation": "..." }` | JSON                                        | `201` + payload `status: "success"` + `token` |
| Login   | POST   | `/api/auth/login`    | `{ "identifier": "...", "password": "..." }`                                      | JSON                                        | `200` + payload `status: "success"` + `token` |
| Logout  | POST   | `/api/auth/logout`   | `{}`                                                                              | JSON + `Authorization: Bearer {token}`      | `200` + payload `status: "success"` |
| Usuário | GET    | `/api/auth/user`     | —                                                                                | JSON + `Authorization: Bearer {token}`      | `200` + payload `status: "success"` + `user` |

> **Importante:** Após deploy em produção, execute `php artisan route:clear` (e demais `cache:clear` se necessário) para garantir que as novas rotas estejam ativas antes de consumir a API.
