<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - LL Despachante</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #F8FAFC;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 420px;
        }

        .header {
            margin-bottom: 24px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #0047AB;
            margin-bottom: 8px;
        }

        .header p {
            font-size: 14px;
            line-height: 1.5;
            color: #64748B;
        }

        .form-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 18px rgba(16, 24, 40, 0.05);
            padding: 24px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group:last-of-type {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #64748B;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            font-size: 14px;
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 20px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #0047AB;
            border-width: 1.5px;
        }

        .form-group input.error {
            background-color: #FFE5E5;
            border-color: #EF4444;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #64748B;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: #0047AB;
        }

        .error-message {
            color: #EF4444;
            font-size: 13px;
            margin-top: 6px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 24px;
        }

        .forgot-password a {
            color: #2F80ED;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            padding: 14px;
            font-size: 15px;
            font-weight: 600;
            color: white;
            background: #0047AB;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s;
            min-height: 50px;
            font-family: inherit;
        }

        .btn:hover:not(:disabled) {
            background: #003d99;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 71, 171, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn:active:not(:disabled) {
            transform: translateY(0);
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #64748B;
        }

        .register-link a {
            color: #2F80ED;
            font-weight: 600;
            text-decoration: none;
            margin-left: 4px;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert.error {
            background: #FFE5E5;
            border: 1px solid #EF4444;
            color: #C62828;
            font-size: 14px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 8px 0;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #0047AB;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bem-vindo de volta</h1>
            <p>Acesse sua conta com seus dados de login.</p>
        </div>

        <div class="form-container">
            <div class="alert error" id="errorAlert"></div>

            <form id="loginForm">
                <div class="form-group">
                    <label for="identifier">Usuário ou email</label>
                    <input 
                        type="text" 
                        id="identifier" 
                        name="identifier" 
                        placeholder="Digite seu usuário ou email"
                        autocomplete="username"
                        required
                    >
                    <div class="error-message" id="identifierError"></div>
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            required
                        >
                        <button 
                            type="button" 
                            class="password-toggle" 
                            id="passwordToggle"
                            aria-label="Mostrar senha"
                        >
                            <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg id="eyeOffIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="error-message" id="passwordError"></div>
                </div>

                <div class="forgot-password">
                    <a href="#" onclick="alert('Funcionalidade em desenvolvimento'); return false;">Esqueci minha senha</a>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    <span id="btnText">Entrar</span>
                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                    </div>
                </button>
            </form>

            <div class="register-link">
                Não tem conta? <a href="#" onclick="alert('Funcionalidade em desenvolvimento'); return false;">Cadastre-se</a>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = window.location.origin;

        // Toggle senha
        document.getElementById('passwordToggle').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        });

        // Validação
        function validateField(fieldName, isValid, errorMessage) {
            const field = document.getElementById(fieldName);
            const errorDiv = document.getElementById(fieldName + 'Error');
            
            if (!isValid) {
                field.classList.add('error');
                errorDiv.textContent = errorMessage;
                errorDiv.classList.add('show');
            } else {
                field.classList.remove('error');
                errorDiv.classList.remove('show');
            }
        }

        function clearErrors() {
            document.querySelectorAll('.error-message').forEach(el => {
                el.classList.remove('show');
            });
            document.querySelectorAll('input').forEach(el => {
                el.classList.remove('error');
            });
            document.getElementById('errorAlert').classList.remove('show');
        }

        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.textContent = message;
            alert.classList.add('show');
        }

        // Login
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            clearErrors();

            const identifier = document.getElementById('identifier').value.trim();
            const password = document.getElementById('password').value;

            // Validação
            let hasError = false;

            if (!identifier) {
                validateField('identifier', false, 'Informe usuário ou email');
                hasError = true;
            }

            if (!password) {
                validateField('password', false, 'Informe sua senha');
                hasError = true;
            } else if (password.length < 6) {
                validateField('password', false, 'A senha deve ter pelo menos 6 caracteres');
                hasError = true;
            }

            if (hasError) {
                return;
            }

            // Mostrar loading
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const loading = document.getElementById('loading');
            
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            loading.classList.add('show');

            try {
                const response = await fetch(`${API_BASE_URL}/api/auth/login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        identifier: identifier,
                        password: password
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Credenciais inválidas');
                }

                if (data.status === 'success' && data.token) {
                    // Salvar token no localStorage
                    localStorage.setItem('auth_token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user || {}));
                    
                    // Redirecionar para home
                    window.location.href = '/home';
                } else {
                    throw new Error('Resposta inválida do servidor');
                }

            } catch (error) {
                showError(error.message || 'Não foi possível entrar. Tente novamente.');
                
                // Se houver erros específicos de campo
                if (error.errors) {
                    if (error.errors.identifier) {
                        validateField('identifier', false, error.errors.identifier[0]);
                    }
                    if (error.errors.password) {
                        validateField('password', false, error.errors.password[0]);
                    }
                }
            } finally {
                submitBtn.disabled = false;
                btnText.style.display = 'block';
                loading.classList.remove('show');
            }
        });

        // Verificar se já está autenticado
        if (localStorage.getItem('auth_token')) {
            window.location.href = '/home';
        }
    </script>
</body>
</html>
