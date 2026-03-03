<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cadastre-se - LL Despachante</title>
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
            max-width: 460px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: #2F80ED;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            margin-bottom: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
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

        .form-group input.error,
        .checkbox-group.error {
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

        .checkbox-group {
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 12px;
            margin-top: 8px;
            background: #F8FAFC;
        }

        .checkbox-group label {
            margin: 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13px;
            line-height: 1.5;
            color: #475569;
            cursor: pointer;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-top: 3px;
            accent-color: #0047AB;
        }

        .checkbox-group a {
            color: #2F80ED;
            font-weight: 600;
            text-decoration: none;
        }

        .checkbox-group a:hover {
            text-decoration: underline;
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
            margin-top: 8px;
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

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #64748B;
        }

        .login-link a {
            color: #2F80ED;
            font-weight: 600;
            text-decoration: none;
            margin-left: 4px;
        }

        .login-link a:hover {
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
        <a class="back-link" href="{{ route('login') }}">&larr; Voltar para o login</a>

        <div class="header">
            <h1>Crie sua conta</h1>
            <p>Preencha os dados abaixo para começar a usar o LL Despachante.</p>
        </div>

        <div class="form-container">
            <div class="alert error" id="errorAlert"></div>

            <form id="registerForm">
                <div class="form-group">
                    <label for="username">Nome de usuário</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Digite seu nome de usuário"
                        autocomplete="username"
                        minlength="3"
                        required
                    >
                    <div class="error-message" id="usernameError"></div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Digite seu email"
                        autocomplete="email"
                        required
                    >
                    <div class="error-message" id="emailError"></div>
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Crie uma senha"
                            autocomplete="new-password"
                            minlength="6"
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
                            <svg id="eyeOffIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="error-message" id="passwordError"></div>
                </div>

                <div class="form-group">
                    <label for="passwordConfirmation">Confirme a senha</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="passwordConfirmation"
                            name="password_confirmation"
                            placeholder="Digite a senha novamente"
                            autocomplete="new-password"
                            minlength="6"
                            required
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            id="confirmPasswordToggle"
                            aria-label="Mostrar confirmação de senha"
                        >
                            <svg id="confirmEyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg id="confirmEyeOffIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="error-message" id="passwordConfirmationError"></div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group" id="privacyPolicyGroup">
                        <label for="acceptedPrivacyPolicy">
                            <input
                                type="checkbox"
                                id="acceptedPrivacyPolicy"
                                name="accepted_privacy_policy"
                                required
                            >
                            <span>Li e aceito a <a href="{{ route('privacy-policy') }}" target="_blank" rel="noopener noreferrer">Política de Privacidade</a>.</span>
                        </label>
                    </div>
                    <div class="error-message" id="acceptedPrivacyPolicyError"></div>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    <span id="btnText">Criar conta</span>
                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                    </div>
                </button>
            </form>

            <div class="login-link">
                Já tem conta? <a href="{{ route('login') }}">Entrar</a>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = window.location.origin;
        const TOKEN_KEY = 'auth_token';
        const USER_KEY = 'user';

        function setStoredItem(key, value, remember) {
            if (remember) {
                localStorage.setItem(key, value);
                sessionStorage.removeItem(key);
            } else {
                sessionStorage.setItem(key, value);
                localStorage.removeItem(key);
            }
        }

        function getStoredItem(key) {
            return sessionStorage.getItem(key) || localStorage.getItem(key);
        }

        function togglePasswordVisibility(inputId, eyeIconId, eyeOffIconId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(eyeIconId);
            const eyeOffIcon = document.getElementById(eyeOffIconId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        }

        document.getElementById('passwordToggle').addEventListener('click', function() {
            togglePasswordVisibility('password', 'eyeIcon', 'eyeOffIcon');
        });

        document.getElementById('confirmPasswordToggle').addEventListener('click', function() {
            togglePasswordVisibility('passwordConfirmation', 'confirmEyeIcon', 'confirmEyeOffIcon');
        });

        function validateField(fieldName, isValid, errorMessage) {
            const field = document.getElementById(fieldName);
            const errorDiv = document.getElementById(fieldName + 'Error');

            if (!field || !errorDiv) {
                return;
            }

            if (!isValid) {
                field.classList.add('error');
                errorDiv.textContent = errorMessage;
                errorDiv.classList.add('show');
            } else {
                field.classList.remove('error');
                errorDiv.classList.remove('show');
            }
        }

        function validatePrivacyPolicy(isValid, errorMessage) {
            const group = document.getElementById('privacyPolicyGroup');
            const errorDiv = document.getElementById('acceptedPrivacyPolicyError');

            if (!isValid) {
                group.classList.add('error');
                errorDiv.textContent = errorMessage;
                errorDiv.classList.add('show');
            } else {
                group.classList.remove('error');
                errorDiv.classList.remove('show');
            }
        }

        function clearErrors() {
            document.querySelectorAll('.error-message').forEach((el) => {
                el.classList.remove('show');
            });
            document.querySelectorAll('input').forEach((el) => {
                el.classList.remove('error');
            });
            document.getElementById('privacyPolicyGroup').classList.remove('error');
            document.getElementById('errorAlert').classList.remove('show');
        }

        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.textContent = message;
            alert.classList.add('show');
        }

        function isValidEmail(email) {
            return /^[\w.-]+@[\w-]+\.\w{2,}$/i.test(email);
        }

        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            clearErrors();

            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('passwordConfirmation').value;
            const acceptedPrivacyPolicy = !!document.getElementById('acceptedPrivacyPolicy').checked;

            let hasError = false;

            if (!username) {
                validateField('username', false, 'Informe um nome de usuário');
                hasError = true;
            } else if (username.length < 3) {
                validateField('username', false, 'Use pelo menos 3 caracteres');
                hasError = true;
            }

            if (!email) {
                validateField('email', false, 'Informe um email válido');
                hasError = true;
            } else if (!isValidEmail(email)) {
                validateField('email', false, 'Email inválido');
                hasError = true;
            }

            if (!password) {
                validateField('password', false, 'Informe uma senha');
                hasError = true;
            } else if (password.length < 6) {
                validateField('password', false, 'A senha deve ter pelo menos 6 caracteres');
                hasError = true;
            }

            if (!passwordConfirmation) {
                validateField('passwordConfirmation', false, 'Confirme sua senha');
                hasError = true;
            } else if (password !== passwordConfirmation) {
                validateField('passwordConfirmation', false, 'As senhas não coincidem');
                hasError = true;
            }

            if (!acceptedPrivacyPolicy) {
                validatePrivacyPolicy(false, 'Confirme que leu a Política de Privacidade.');
                hasError = true;
            }

            if (hasError) {
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const loading = document.getElementById('loading');

            submitBtn.disabled = true;
            btnText.style.display = 'none';
            loading.classList.add('show');

            try {
                const response = await fetch(`${API_BASE_URL}/api/auth/register`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        username,
                        email,
                        password,
                        password_confirmation: passwordConfirmation,
                        accepted_privacy_policy: acceptedPrivacyPolicy,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    const errors = data?.errors ?? {};

                    if (errors.username?.[0]) {
                        validateField('username', false, errors.username[0]);
                    }
                    if (errors.email?.[0]) {
                        validateField('email', false, errors.email[0]);
                    }
                    if (errors.password?.[0]) {
                        validateField('password', false, errors.password[0]);
                    }
                    if (errors.password_confirmation?.[0]) {
                        validateField('passwordConfirmation', false, errors.password_confirmation[0]);
                    }
                    if (errors.accepted_privacy_policy?.[0]) {
                        validatePrivacyPolicy(false, errors.accepted_privacy_policy[0]);
                    }

                    throw new Error(data.message || 'Não foi possível criar sua conta.');
                }

                if (data.status === 'success' && data.token) {
                    setStoredItem(TOKEN_KEY, data.token, false);
                    setStoredItem(USER_KEY, JSON.stringify(data.user || {}), false);
                    window.location.href = '/home';
                    return;
                }

                throw new Error('Resposta inválida do servidor.');
            } catch (error) {
                showError(error.message || 'Não foi possível criar sua conta. Tente novamente.');
            } finally {
                submitBtn.disabled = false;
                btnText.style.display = 'block';
                loading.classList.remove('show');
            }
        });

        if (getStoredItem(TOKEN_KEY)) {
            window.location.href = '/home';
        }
    </script>
</body>
</html>
