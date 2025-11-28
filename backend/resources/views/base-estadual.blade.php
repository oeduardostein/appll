<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Consulta Base Estadual - LL Despachante</title>
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
        }

        .header {
            background: #0047AB;
            border-radius: 0 0 32px 32px;
            padding: 28px 20px 36px;
            color: white;
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .logo {
            width: 56px;
            height: 56px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .header-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-icon {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s;
        }

        .btn-icon:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.24);
            color: white;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .header-title {
            font-size: 16px;
            font-weight: 600;
            color: white;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 18px rgba(16, 24, 40, 0.05);
            padding: 24px;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1E293B;
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
            text-transform: uppercase;
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

        .error-message {
            color: #EF4444;
            font-size: 13px;
            margin-top: 6px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .captcha-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        @media (min-width: 640px) {
            .captcha-container {
                grid-template-columns: 1fr 1fr;
            }
        }

        .captcha-image {
            border: 1px solid #E2E8F0;
            border-radius: 20px;
            padding: 12px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .captcha-image:hover {
            border-color: #0047AB;
        }

        .captcha-image img {
            max-width: 100%;
            height: auto;
        }

        .captcha-image.loading {
            color: #64748B;
            font-size: 14px;
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
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        .loading {
            display: none;
            text-align: center;
            padding: 24px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0047AB;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 12px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .result-card {
            display: none;
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 18px rgba(16, 24, 40, 0.05);
            padding: 24px;
            margin-top: 24px;
        }

        .result-card.show {
            display: block;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #E2E8F0;
        }

        .result-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1E293B;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
            min-height: auto;
            width: auto;
            text-transform: none;
        }

        .result-content {
            max-height: 500px;
            overflow-y: auto;
        }

        .result-content pre {
            background: #F8FAFC;
            padding: 16px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.6;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #1E293B;
            border: 1px solid #E2E8F0;
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

        .alert.success {
            background: #E8F5E9;
            border: 1px solid #4CAF50;
            color: #2E7D32;
            font-size: 14px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        @media (min-width: 640px) {
            .info-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .info-item {
            background: #F8FAFC;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
        }

        .info-item label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            display: block;
            margin-bottom: 4px;
        }

        .info-item value {
            font-size: 14px;
            font-weight: 600;
            color: #1E293B;
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div class="logo">
                <img src="{{ asset('images/logoLL.png') }}" alt="LL Despachante" onerror="this.style.display='none'">
            </div>
            <div class="header-actions">
                <button class="btn-icon" id="profileBtn" title="Meu perfil" style="display: none;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </button>
                <button class="btn-outline" id="logoutBtn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Sair
                </button>
            </div>
        </div>
        <div class="header-title" id="userInfo">Consulta Base Estadual</div>
    </div>

    <div class="container">
        <div class="form-card">
            <div class="alert error" id="errorAlert"></div>
            <div class="alert success" id="successAlert"></div>

            <form id="searchForm">
                <div class="form-group">
                    <label for="placa">Placa</label>
                    <input 
                        type="text" 
                        id="placa" 
                        name="placa" 
                        placeholder="ABC1234" 
                        maxlength="7"
                        autocomplete="off"
                        required
                    >
                    <div class="error-message" id="placaError"></div>
                </div>

                <div class="form-group">
                    <label for="renavam">RENAVAM</label>
                    <input 
                        type="text" 
                        id="renavam" 
                        name="renavam" 
                        placeholder="12345678901" 
                        maxlength="11"
                        autocomplete="off"
                    >
                    <div class="error-message" id="renavamError"></div>
                </div>

                <div class="form-group">
                    <label>Captcha</label>
                    <div class="captcha-container">
                        <div class="captcha-image" id="captchaImage">
                            <span class="loading">Clique para carregar captcha</span>
                        </div>
                        <input 
                            type="text" 
                            id="captcha" 
                            name="captcha" 
                            placeholder="Digite o captcha" 
                            maxlength="10"
                            autocomplete="off"
                            required
                            style="text-transform: uppercase;"
                        >
                    </div>
                    <div class="error-message" id="captchaError"></div>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    Consultar
                </button>
            </form>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Consultando base estadual...</p>
            </div>
        </div>

        <div class="result-card" id="resultCard">
            <div class="result-header">
                <h2>Resultado da Consulta</h2>
                <button class="btn btn-small" onclick="copyResult()">
                    Copiar
                </button>
            </div>
            <div class="result-content" id="resultContent"></div>
        </div>
    </div>

    <script>
        const API_BASE_URL = window.location.origin;
        let captchaBase64 = null;
        let authToken = null;

        // Verificar autenticação
        function checkAuth() {
            authToken = localStorage.getItem('auth_token');
            if (!authToken) {
                window.location.href = '/login';
                return false;
            }
            
            // Atualizar informações do usuário
            const userStr = localStorage.getItem('user');
            if (userStr) {
                try {
                    const user = JSON.parse(userStr);
                    const userInfo = document.getElementById('userInfo');
                    if (user.name) {
                        userInfo.textContent = `Usuário: ${user.name} • Consulta Base Estadual`;
                    }
                } catch (e) {
                    console.error('Erro ao parsear usuário:', e);
                }
            }
            
            return true;
        }

        // Logout
        document.getElementById('logoutBtn').addEventListener('click', async function() {
            if (confirm('Deseja realmente sair?')) {
                try {
                    if (authToken) {
                        await fetch(`${API_BASE_URL}/api/auth/logout`, {
                            method: 'POST',
                            headers: {
                                'Authorization': `Bearer ${authToken}`,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            }
                        });
                    }
                } catch (e) {
                    console.error('Erro ao fazer logout:', e);
                } finally {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user');
                    window.location.href = '/login';
                }
            }
        });

        // Inicialização
        if (!checkAuth()) {
            // Redirecionamento já foi feito
        } else {
            // Carregar captcha ao carregar a página
            loadCaptcha();
            
            // Auto-resolver captcha ao clicar na imagem
            document.getElementById('captchaImage').addEventListener('click', function() {
                loadCaptcha();
            });

            // Formatação automática para maiúsculas
            document.getElementById('placa').addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });

            document.getElementById('renavam').addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });

            document.getElementById('captcha').addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase();
            });

            // Validação onBlur
            document.getElementById('placa').addEventListener('blur', function() {
                validateField('placa', this.value.trim() !== '', 'Este campo é obrigatório');
            });

            document.getElementById('captcha').addEventListener('blur', function() {
                validateField('captcha', this.value.trim() !== '', 'Este campo é obrigatório');
            });

            // Submissão do formulário
            document.getElementById('searchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                performSearch();
            });
        }

        async function loadCaptcha() {
            const captchaImage = document.getElementById('captchaImage');
            captchaImage.innerHTML = '<span class="loading">Carregando captcha...</span>';
            captchaImage.classList.add('loading');

            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha`);
                
                if (!response.ok) {
                    throw new Error('Falha ao carregar captcha');
                }

                const contentType = response.headers.get('content-type');
                
                if (contentType && contentType.includes('image')) {
                    const blob = await response.blob();
                    const reader = new FileReader();
                    reader.onloadend = function() {
                        captchaBase64 = reader.result;
                        captchaImage.innerHTML = `<img src="${reader.result}" alt="Captcha">`;
                        captchaImage.classList.remove('loading');
                    };
                    reader.readAsDataURL(blob);
                } else {
                    const text = await response.text();
                    try {
                        const json = JSON.parse(text);
                        captchaBase64 = json.captcha || json.data || text;
                    } catch {
                        captchaBase64 = text;
                    }
                    
                    if (captchaBase64.startsWith('data:image')) {
                        captchaImage.innerHTML = `<img src="${captchaBase64}" alt="Captcha">`;
                    } else {
                        captchaImage.innerHTML = `<span style="font-size: 24px; font-weight: bold; letter-spacing: 4px;">${captchaBase64}</span>`;
                    }
                    captchaImage.classList.remove('loading');
                }
            } catch (error) {
                captchaImage.innerHTML = '<span style="color: #EF4444;">Erro ao carregar captcha. Clique para tentar novamente.</span>';
                captchaImage.classList.remove('loading');
                showError('Não foi possível carregar o captcha. Tente novamente.');
            }
        }

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
        }

        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.textContent = message;
            alert.classList.add('show');
            setTimeout(() => alert.classList.remove('show'), 5000);
        }

        function showSuccess(message) {
            const alert = document.getElementById('successAlert');
            alert.textContent = message;
            alert.classList.add('show');
            setTimeout(() => alert.classList.remove('show'), 3000);
        }

        async function performSearch() {
            clearErrors();

            const placa = document.getElementById('placa').value.trim().toUpperCase();
            const renavam = document.getElementById('renavam').value.trim();
            const captcha = document.getElementById('captcha').value.trim().toUpperCase();

            // Validação
            let hasError = false;

            if (!placa) {
                validateField('placa', false, 'Este campo é obrigatório');
                hasError = true;
            }

            if (!captcha) {
                validateField('captcha', false, 'Este campo é obrigatório');
                hasError = true;
            }

            if (hasError) {
                return;
            }

            // Mostrar loading
            document.getElementById('loading').classList.add('show');
            document.getElementById('resultCard').classList.remove('show');
            document.getElementById('submitBtn').disabled = true;

            try {
                // Tentar resolver captcha automaticamente primeiro
                let captchaToUse = captcha;
                
                try {
                    const solveResponse = await fetch(`${API_BASE_URL}/api/captcha/solve`);
                    if (solveResponse.ok) {
                        const solveData = await solveResponse.json();
                        if (solveData.solution) {
                            captchaToUse = solveData.solution.trim().toUpperCase();
                            document.getElementById('captcha').value = captchaToUse;
                        }
                    }
                } catch (e) {
                    // Se falhar, usar o captcha manual
                }

                // Fazer a consulta
                const params = new URLSearchParams({
                    placa: placa,
                    renavam: renavam || '',
                    captcha: captchaToUse
                });

                const response = await fetch(`${API_BASE_URL}/api/base-estadual?${params}`);

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'Erro ao consultar base estadual' }));
                    throw new Error(errorData.message || `Erro HTTP ${response.status}`);
                }

                const result = await response.json();
                
                // Exibir resultado
                displayResult(result, placa, renavam);

            } catch (error) {
                showError(error.message || 'Não foi possível consultar a base estadual.');
            } finally {
                document.getElementById('loading').classList.remove('show');
                document.getElementById('submitBtn').disabled = false;
                // Recarregar captcha após consulta
                loadCaptcha();
                document.getElementById('captcha').value = '';
            }
        }

        function displayResult(data, placa, renavam) {
            const card = document.getElementById('resultCard');
            const content = document.getElementById('resultContent');

            // Verificar se há dados estruturados
            if (data.veiculo || data.fonte) {
                let html = '<div class="info-grid">';
                
                if (data.veiculo) {
                    html += `
                        <div class="info-item">
                            <label>Placa</label>
                            <value>${data.veiculo.placa || '—'}</value>
                        </div>
                        <div class="info-item">
                            <label>RENAVAM</label>
                            <value>${data.veiculo.renavam || '—'}</value>
                        </div>
                    `;
                    
                    if (data.veiculo.marca) {
                        html += `
                            <div class="info-item">
                                <label>Marca/Modelo</label>
                                <value>${data.veiculo.marca || '—'}</value>
                            </div>
                        `;
                    }
                    
                    if (data.veiculo.ano_modelo || data.veiculo.ano_fabricacao) {
                        html += `
                            <div class="info-item">
                                <label>Ano</label>
                                <value>${data.veiculo.ano_modelo || '—'} / ${data.veiculo.ano_fabricacao || '—'}</value>
                            </div>
                        `;
                    }
                }

                if (data.proprietario && data.proprietario.nome) {
                    html += `
                        <div class="info-item">
                            <label>Proprietário</label>
                            <value>${data.proprietario.nome}</value>
                        </div>
                    `;
                }

                html += '</div>';
                
                // Adicionar JSON completo
                html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                content.innerHTML = html;
            } else if (data.message) {
                content.innerHTML = `<div style="padding: 16px; background: #F8FAFC; border-radius: 12px; border: 1px solid #E2E8F0;"><p style="font-size: 16px; color: #1E293B;">${data.message}</p></div>`;
            } else {
                content.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            }

            card.classList.add('show');
            showSuccess('Consulta realizada com sucesso!');
        }

        function copyResult() {
            const content = document.getElementById('resultContent');
            const text = content.innerText || content.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                showSuccess('Resultado copiado para a área de transferência!');
            }).catch(() => {
                showError('Não foi possível copiar o resultado.');
            });
        }
    </script>
</body>
</html>
