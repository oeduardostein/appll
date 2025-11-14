# Integração Flutter (frontend-app) x Laravel (backend)

## 1. Visão geral
- O backend Laravel expõe todos os recursos REST sob `/api`, definidos em `backend/routes/api.php`. Cada controlador lida com um domínio (autenticação, pesquisas, emissão de ATPV, etc.).
- O app Flutter consome essas rotas usando o pacote `http` e uma camada de serviços localizada em `frontend-app/lib/services`. Cada serviço conhece apenas o path relativo, enquanto a URL base é resolvida em tempo de build por `BACKEND_BASE_URL` (ver seção 4).
- A autenticação usa tokens `api_token` persistidos no banco. O Laravel gera um hash SHA-256 do token e valida o header `Authorization: Bearer <token>` em cada request autenticada. O Flutter guarda o token em memória (`AuthService._sharedSession`).
- Para liberar o tráfego entre domínios, o CORS permite o domínio oficial e padrões de localhost (ver `backend/config/cors.php`).

## 2. Fluxo ponta a ponta
1. O usuário realiza login/registro no app (`AuthService.login`/`register`).
2. O serviço chama `/api/auth/*` enviando JSON. O backend valida via `LoginRequest`/`RegisterRequest`, emite o token e devolve `user` + `token`.
3. O Flutter persiste o token em `AuthSession` e passa a injetá-lo no header `Authorization` através de cada serviço específico (por exemplo `PesquisaService`, `AtpvService`, etc.).
4. As rotas protegidas no Laravel utilizam o helper `findUserFromRequest` (vide `App\Http\Controllers\Api\Auth\AuthController`) para localizar o usuário pelo token hasheado e responder.
5. Quando o usuário sai da sessão, o app chama `/api/auth/logout`, e o backend invalida o hash, impedindo reuso do token.
6. Requisições não autenticadas (captcha, CEP, etc.) usam apenas headers JSON padrão.

## 3. O que foi necessário configurar no Laravel
- **Rotas e controladores:** consolidamos todos os endpoints REST em `backend/routes/api.php`, agrupando `Route::prefix('auth')` e demais recursos (`pesquisas`, `emissao-atpv`, `captcha`, etc.).
- **Tokens próprios:** em `App\Http\Controllers\Api\Auth\AuthController`, o método `issueToken` gera um token randômico, armazena o hash no campo `api_token` e devolve o valor puro para o app.
- **Validações dedicadas:** usamos `LoginRequest` e `RegisterRequest` para validar payloads antes de atingir a lógica de negócio.
- **CORS liberado para o app:** `backend/config/cors.php` lista `https://applldespachante.skalacode.com` e padrões de localhost, permitindo que o Flutter Web/Desktop ou um emulador acesse a API durante o desenvolvimento.
- **.env harmonizado:** o `.env` define `APP_URL` e drivers de sessão/cache para que os links gerados e uploads funcionem idem no domínio público que o Flutter consome.

## 4. O que foi necessário configurar no Flutter
- **Serviço de autenticação central:** `frontend-app/lib/services/auth_service.dart` encapsula a criação de `http.Client`, o saneamento da base URL e o cache do token/usuário. Isso evita repetir headers e parse em cada tela.
- **Serviços específicos por domínio:** arquivos como `pesquisa_service.dart`, `atpv_service.dart`, `cep_service.dart` mapeiam cada rota API. Todos recebem a instância compartilhada do token e constroem URLs relativos (`/api/pesquisas`, `/api/emissao-atpv`, etc.).
- **Definição da URL base:** a constante `String.fromEnvironment('BACKEND_BASE_URL', defaultValue: 'https://applldespachante.skalacode.com')` permite trocar o backend em tempo de build sem alterar código. Ao rodar o app, usamos:
  ```bash
  flutter run --dart-define=BACKEND_BASE_URL=https://seu-backend-local.test
  ```
- **Tratamento unificado de erros:** `_handleResponse` verifica status code, decodifica JSON e lança exceções com mensagens amigáveis. Isso garante que o backend devolvendo 4xx/5xx seja exibido corretamente no app.
- **Headers consistentes:** `_jsonHeaders()` mantém `Content-Type`, `Accept` e `X-Requested-With`, espelhando o que o Laravel espera.

## 5. Passo a passo para subir o conjunto
1. **Backend**
   ```bash
   cd backend
   cp .env.example .env
   php artisan key:generate
   php artisan migrate --seed   # se aplicável
   php artisan serve --host=0.0.0.0 --port=8000
   ```
2. **Frontend Flutter**
   ```bash
   cd frontend-app
   flutter pub get
   flutter run --dart-define=BACKEND_BASE_URL=http://127.0.0.1:8000
   ```
3. ***Observação***: se for exposto publicamente, a URL definida em `--dart-define` deve coincidir com `APP_URL` e com os domínios liberados em `config/cors.php`.

## 6. Como depurar a integração
- Use `php artisan route:list --path=api` para confirmar rotas ativas e métodos retornados.
- Ative logs (`storage/logs/laravel.log`) para inspecionar validações e exceções devolvidas ao app.
- No Flutter, habilite o log do `http.Client` ou rode com `flutter run -v` para acompanhar requests e responses completos.
- Para checar o token atual no app, use `AuthService().session?.token` durante o debug.

Com essas configurações, o app Flutter consegue autenticar usuários, consultar serviços e emitir documentos por meio da API Laravel, garantindo segurança com tokens e interoperabilidade via CORS.
