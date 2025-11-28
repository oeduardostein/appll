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
            background: linear-gradient(135deg, #0047AB 0%, #003d99 100%);
            border-radius: 0 0 32px 32px;
            padding: 24px 20px;
            color: white;
            box-shadow: 0 4px 20px rgba(0, 71, 171, 0.2);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
        }

        .logo {
            width: 64px;
            height: 64px;
            background: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: #0047AB;
            display: none;
        }

        .logo.no-image .logo-text {
            display: block;
        }

        .header-info {
            flex: 1;
            min-width: 0;
        }

        .header-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .header-subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.4;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-shrink: 0;
        }

        .btn-icon {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            cursor: pointer;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: all 0.3s;
            width: 44px;
            height: 44px;
        }

        .btn-icon:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-family: inherit;
            white-space: nowrap;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-outline svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        @media (min-width: 768px) {
            .header {
                padding: 32px 40px;
            }

            .header-top {
                margin-bottom: 24px;
            }

            .logo {
                width: 72px;
                height: 72px;
                border-radius: 18px;
            }

            .header-title {
                font-size: 24px;
            }

            .header-subtitle {
                font-size: 15px;
            }

            .header-actions {
                gap: 16px;
            }

            .btn-outline {
                padding: 12px 24px;
                font-size: 15px;
            }
        }

        @media (min-width: 1024px) {
            .header {
                padding: 40px 60px;
            }

            .header-left {
                gap: 24px;
            }

            .logo {
                width: 80px;
                height: 80px;
                border-radius: 20px;
            }

            .header-title {
                font-size: 28px;
            }

            .header-subtitle {
                font-size: 16px;
            }
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

        .result-container {
            display: none;
            margin-top: 24px;
        }

        .result-container.show {
            display: block;
        }

        .result-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 18px rgba(16, 24, 40, 0.05);
            padding: 24px;
            margin-bottom: 16px;
        }

        .vehicle-summary-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.06);
            padding: 20px;
            margin-bottom: 16px;
        }

        .vehicle-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .vehicle-icon {
            width: 64px;
            height: 64px;
            background: rgba(0, 71, 171, 0.18);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .vehicle-icon svg {
            width: 32px;
            height: 32px;
            stroke: #0047AB;
            fill: none;
        }

        .vehicle-info {
            flex: 1;
            min-width: 0;
        }

        .vehicle-placa {
            font-size: 24px;
            font-weight: 800;
            color: #1E293B;
            margin-bottom: 4px;
        }

        .vehicle-marca {
            font-size: 18px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 2px;
        }

        .vehicle-ano {
            font-size: 14px;
            color: #64748B;
        }

        .vehicle-tiles {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        @media (min-width: 640px) {
            .vehicle-tiles {
                grid-template-columns: 1fr 1fr;
            }
        }

        .vehicle-tile {
            background: #F8FAFC;
            padding: 16px;
            border-radius: 18px;
            border: 1px solid #E2E8F0;
        }

        .vehicle-tile-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 6px;
        }

        .vehicle-tile-value {
            font-size: 16px;
            font-weight: 700;
            color: #1E293B;
        }

        .vehicle-tile-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 8px;
        }

        .vehicle-proprietario {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #E2E8F0;
        }

        .vehicle-proprietario-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 4px;
        }

        .vehicle-proprietario-value {
            font-size: 14px;
            font-weight: 700;
            color: #1E293B;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .btn-text-link {
            background: none;
            border: none;
            color: #0047AB;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            padding: 8px 0;
            font-family: inherit;
        }

        .btn-text-link:hover {
            text-decoration: underline;
        }

        .action-menu-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            padding: 0;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .action-menu-item {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            cursor: pointer;
            transition: background 0.2s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-family: inherit;
        }

        .action-menu-item:hover {
            background: #F8FAFC;
        }

        .action-menu-item:not(:last-child) {
            border-bottom: 1px solid rgba(226, 232, 240, 0.35);
        }

        .action-menu-icon {
            width: 24px;
            height: 24px;
            color: #0047AB;
            margin-right: 16px;
            flex-shrink: 0;
        }

        .action-menu-label {
            flex: 1;
            font-size: 16px;
            font-weight: 600;
            color: #1E293B;
        }

        .action-menu-arrow {
            width: 20px;
            height: 20px;
            color: #64748B;
            flex-shrink: 0;
        }

        .section-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 18px rgba(16, 24, 40, 0.05);
            padding: 18px 20px;
            margin-bottom: 16px;
            border: 1px solid rgba(226, 232, 240, 0.12);
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 16px;
        }

        .info-row {
            margin-bottom: 12px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 700;
            color: #1E293B;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
            padding: 20px;
            backdrop-filter: blur(4px);
        }

        .modal.show {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 24px;
            max-width: 800px;
            width: 100%;
            margin: 40px auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 80px);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1px solid #E2E8F0;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: #1E293B;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #64748B;
            cursor: pointer;
            padding: 4px;
            line-height: 1;
        }

        .modal-close:hover {
            color: #1E293B;
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }

        @media (max-width: 768px) {
            .modal {
                padding: 10px;
            }

            .modal-content {
                margin: 20px auto;
                max-height: calc(100vh - 40px);
            }

            .modal-header,
            .modal-body {
                padding: 16px;
            }
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
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="header-top">
                <div class="header-left">
                    <div class="logo">
                        <img src="{{ asset('images/logoLL.png') }}" alt="LL Despachante" onerror="this.style.display='none'; this.parentElement.classList.add('no-image');">
                        <span class="logo-text">LL</span>
                    </div>
                    <div class="header-info">
                        <div class="header-title" id="userInfo">Consulta Base Estadual</div>
                        <div class="header-subtitle" id="consultasInfo">Carregando...</div>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn-outline" onclick="window.location.href='/home'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span>Home</span>
                    </button>
                    <button class="btn-icon" id="profileBtn" title="Meu perfil" style="display: none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </button>
                    <button class="btn-outline" id="logoutBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>Sair</span>
                    </button>
                </div>
            </div>
        </div>
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



                <button type="submit" class="btn" id="submitBtn">
                    Consultar
                </button>
            </form>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Consultando base estadual...</p>
            </div>
        </div>

        <div class="result-container" id="resultContainer">
            <div id="resultContent"></div>
        </div>

        <!-- Modal para detalhes -->
        <div class="modal" id="detailModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="modalTitle">Detalhes</h2>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body" id="modalBody"></div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = window.location.origin;
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
                        userInfo.textContent = `Usuário: ${user.name}`;
                    }
                } catch (e) {
                    console.error('Erro ao parsear usuário:', e);
                }
            }
            
            // Carregar estatísticas de consultas
            loadConsultasStats();
            
            return true;
        }

        async function loadConsultasStats() {
            try {
                const response = await fetch(`${API_BASE_URL}/api/pesquisas/ultimo-mes`, {
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json',
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    const monthlyCount = data.data ? data.data.length : 0;
                    const consultasInfo = document.getElementById('consultasInfo');
                    consultasInfo.textContent = `${monthlyCount} consulta${monthlyCount !== 1 ? 's' : ''} realizada${monthlyCount !== 1 ? 's' : ''} este mês`;
                }
            } catch (error) {
                console.error('Erro ao carregar estatísticas:', error);
            }
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
            // Formatação automática para maiúsculas
            document.getElementById('placa').addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });


            // Validação onBlur
            document.getElementById('placa').addEventListener('blur', function() {
                validateField('placa', this.value.trim() !== '', 'Este campo é obrigatório');
            });

            // Submissão do formulário
            document.getElementById('searchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                performSearch();
            });
        }

        async function solveCaptcha() {
            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha/solve`);
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'Falha ao resolver captcha' }));
                    throw new Error(errorData.message || 'Falha ao resolver captcha automaticamente');
                }

                const data = await response.json();
                
                if (data.solution && data.solution.trim()) {
                    return data.solution.trim().toUpperCase();
                }
                
                throw new Error('Resposta inválida do servidor de captcha');
            } catch (error) {
                throw error;
            }
        }

        function validateField(fieldName, isValid, errorMessage) {
            const field = document.getElementById(fieldName);
            if (!field) return;
            
            const errorDiv = document.getElementById(fieldName + 'Error');
            if (!errorDiv) return;
            
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
            const renavam = ''; // Sempre enviar em branco

            // Validação
            if (!placa) {
                validateField('placa', false, 'Este campo é obrigatório');
                return;
            }

            // Mostrar loading
            const loadingEl = document.getElementById('loading');
            const submitBtn = document.getElementById('submitBtn');
            const resultContainer = document.getElementById('resultContainer');
            
            loadingEl.classList.add('show');
            resultContainer.classList.remove('show');
            submitBtn.disabled = true;

            try {
                // Resolver captcha automaticamente usando 2Captcha
                let captchaSolution;
                try {
                    loadingEl.querySelector('p').textContent = 'Resolvendo captcha automaticamente...';
                    captchaSolution = await solveCaptcha();
                } catch (captchaError) {
                    throw new Error('Não foi possível resolver o captcha automaticamente. Tente novamente em alguns instantes.');
                }

                // Fazer a consulta
                loadingEl.querySelector('p').textContent = 'Consultando base estadual...';
                const params = new URLSearchParams({
                    placa: placa,
                    renavam: '', // Sempre enviar em branco
                    captcha: captchaSolution
                });

                const response = await fetch(`${API_BASE_URL}/api/base-estadual?${params}`);

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'Erro ao consultar base estadual' }));
                    throw new Error(errorData.message || `Erro HTTP ${response.status}`);
                }

                const result = await response.json();
                
                // Registrar pesquisa
                await registerPesquisa(placa, renavam);
                
                // Exibir resultado
                displayResult(result, placa, renavam);
                
                // Atualizar estatísticas
                loadConsultasStats();

            } catch (error) {
                showError(error.message || 'Não foi possível consultar a base estadual.');
            } finally {
                loadingEl.classList.remove('show');
                submitBtn.disabled = false;
                loadingEl.querySelector('p').textContent = 'Consultando base estadual...';
            }
        }

        function formatDisplayValue(value) {
            if (value == null || value === '') return '—';
            if (typeof value === 'string') {
                const trimmed = value.trim();
                return trimmed === '' ? '—' : trimmed;
            }
            return value.toString();
        }

        function parseMarca(value) {
            const text = formatDisplayValue(value);
            if (text === '—') return 'Marca não informada';
            const parts = text.split(' - ');
            if (parts.length >= 2) {
                const joined = parts.slice(1).join(' - ').trim();
                return joined === '' ? text : joined;
            }
            return text;
        }

        function buildAnoModelo(modelo, fabricacao) {
            if (modelo === '—' && fabricacao === '—') {
                return 'Ano não informado';
            }
            if (modelo !== '—' && fabricacao !== '—') {
                return `${modelo} / ${fabricacao}`;
            }
            return modelo !== '—' ? modelo : fabricacao;
        }

        function buildInfoRows(source, labels) {
            if (!source || typeof source !== 'object') return '';
            let html = '';
            for (const [key, label] of Object.entries(labels)) {
                const value = source[key];
                if (value !== null && value !== undefined && value !== '') {
                    const formatted = formatDisplayValue(value);
                    if (formatted !== '—') {
                        html += `
                            <div class="info-row">
                                <div class="info-label">${label}</div>
                                <div class="info-value">${formatted}</div>
                            </div>
                        `;
                    }
                }
            }
            return html;
        }

        function displayResult(data, placa, renavam) {
            const container = document.getElementById('resultContainer');
            const content = document.getElementById('resultContent');

            // Verificar se há dados estruturados
            if (data.veiculo || data.fonte) {
                const veiculo = data.veiculo || {};
                const proprietario = data.proprietario || {};
                const crvCrlv = data.crv_crlv_atualizacao || {};
                
                const placaValue = formatDisplayValue(veiculo.placa);
                const marca = parseMarca(veiculo.marca);
                const anoModelo = formatDisplayValue(veiculo.ano_modelo);
                const anoFab = formatDisplayValue(veiculo.ano_fabricacao);
                const anoDisplay = buildAnoModelo(anoModelo, anoFab);
                const municipio = formatDisplayValue(veiculo.municipio);
                const proprietarioNome = formatDisplayValue(proprietario.nome);
                const licenciamentoEx = formatDisplayValue(crvCrlv.exercicio_licenciamento);
                const licenciamentoData = formatDisplayValue(crvCrlv.data_licenciamento);
                const licStatus = licenciamentoData !== '—' ? 'em dia' : 'Não informado';

                let html = `
                    <div class="vehicle-summary-card">
                        <div class="vehicle-header">
                            <div class="vehicle-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1M5 17l-1 4h18l-1-4M5 17h14M9 9h6m-6 4h6"></path>
                                </svg>
                            </div>
                            <div class="vehicle-info">
                                <div class="vehicle-placa">${placaValue}</div>
                                <div class="vehicle-marca">${marca}</div>
                                <div class="vehicle-ano">${anoDisplay}</div>
                            </div>
                        </div>
                        <div class="vehicle-tiles">
                            <div class="vehicle-tile">
                                <div class="vehicle-tile-label">Licenciamento</div>
                                <div class="vehicle-tile-value">${licenciamentoEx}</div>
                                <span class="vehicle-tile-badge" style="background: rgba(76, 175, 80, 0.15); color: #4CAF50;">${licStatus}</span>
                            </div>
                            <div class="vehicle-tile">
                                <div class="vehicle-tile-label">Município</div>
                                <div class="vehicle-tile-value">${municipio}</div>
                            </div>
                        </div>
                        <div class="vehicle-proprietario">
                            <div>
                                <div class="vehicle-proprietario-label">Proprietário</div>
                                <div class="vehicle-proprietario-value">${proprietarioNome}</div>
                            </div>
                            <button class="btn-text-link" onclick="showVehicleDetails()">Ver completo</button>
                        </div>
                    </div>

                    <div class="action-menu-card">
                        <button class="action-menu-item" onclick="showVehicleDetails()">
                            <svg class="action-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1M5 17l-1 4h18l-1-4M5 17h14M9 9h6m-6 4h6"></path>
                            </svg>
                            <span class="action-menu-label">Informações do veículo</span>
                            <svg class="action-menu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        <button class="action-menu-item" onclick="showGravameDetails()">
                            <svg class="action-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            <span class="action-menu-label">Gravame</span>
                            <svg class="action-menu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        <button class="action-menu-item" onclick="showDebitosDetails()">
                            <svg class="action-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            <span class="action-menu-label">Multas e débitos</span>
                            <svg class="action-menu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        <button class="action-menu-item" onclick="showRestricoesDetails()">
                            <svg class="action-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <span class="action-menu-label">Restrições</span>
                            <svg class="action-menu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        <button class="action-menu-item" onclick="showComunicacaoDetails()">
                            <svg class="action-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <span class="action-menu-label">Comunicações de venda</span>
                            <svg class="action-menu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                `;

                // Adicionar seção de fonte se disponível
                if (data.fonte) {
                    const fonteRows = buildInfoRows(data.fonte, {
                        'titulo': 'Título',
                        'gerado_em': 'Gerado em'
                    });
                    if (fonteRows) {
                        html += `
                            <div class="section-card">
                                <div class="section-title">Fonte</div>
                                ${fonteRows}
                            </div>
                        `;
                    }
                }

                content.innerHTML = html;
                
                // Armazenar dados para os modais
                window.resultData = data;
            } else if (data.message) {
                content.innerHTML = `
                    <div class="result-card">
                        <div style="padding: 16px; text-align: center;">
                            <p style="font-size: 16px; color: #1E293B;">${data.message}</p>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="result-card">
                        <pre style="background: #F8FAFC; padding: 16px; border-radius: 12px; border: 1px solid #E2E8F0; font-size: 13px; overflow-x: auto;">${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            }

            container.classList.add('show');
            showSuccess('Consulta realizada com sucesso!');
        }

        function showVehicleDetails() {
            if (!window.resultData || !window.resultData.veiculo) return;
            
            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = 'Informações do veículo';
            
            let html = '';
            
            // Seção Veículo
            const veiculoRows = buildInfoRows(window.resultData.veiculo, {
                'placa': 'Placa',
                'renavam': 'RENAVAM',
                'chassi': 'Chassi',
                'tipo': 'Tipo',
                'procedencia': 'Procedência',
                'combustivel': 'Combustível',
                'cor': 'Cor',
                'marca': 'Marca',
                'categoria': 'Categoria',
                'ano_fabricacao': 'Ano fabricação',
                'ano_modelo': 'Ano modelo',
                'municipio': 'Município'
            });
            if (veiculoRows) {
                html += `<div class="section-card"><div class="section-title">Veículo</div>${veiculoRows}</div>`;
            }
            
            // Seção Proprietário
            if (window.resultData.proprietario) {
                const proprietarioRows = buildInfoRows(window.resultData.proprietario, {
                    'nome': 'Nome'
                });
                if (proprietarioRows) {
                    html += `<div class="section-card"><div class="section-title">Proprietário</div>${proprietarioRows}</div>`;
                }
            }
            
            // Seção CRV/CRLV
            if (window.resultData.crv_crlv_atualizacao) {
                const crvRows = buildInfoRows(window.resultData.crv_crlv_atualizacao, {
                    'exercicio_licenciamento': 'Exercício licenciamento',
                    'data_licenciamento': 'Data licenciamento'
                });
                if (crvRows) {
                    html += `<div class="section-card"><div class="section-title">CRV / CRLV</div>${crvRows}</div>`;
                }
            }
            
            modalBody.innerHTML = html || '<p style="text-align: center; color: #64748B;">Nenhuma informação disponível.</p>';
            modal.classList.add('show');
        }

        function showGravameDetails() {
            if (!window.resultData) return;
            
            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = 'Gravame';
            
            let html = '';
            
            if (window.resultData.gravames) {
                const gravamesRows = buildInfoRows(window.resultData.gravames, {
                    'restricao_financeira': 'Restrição financeira',
                    'nome_agente': 'Nome do agente',
                    'arrendatario': 'Arrendatário',
                    'cnpj_cpf_financiado': 'CNPJ/CPF financiado'
                });
                if (gravamesRows) {
                    html += `<div class="section-card"><div class="section-title">Gravame atual</div>${gravamesRows}</div>`;
                }
            }
            
            if (window.resultData.intencao_gravame) {
                const intencaoRows = buildInfoRows(window.resultData.intencao_gravame, {
                    'restricao_financeira': 'Restrição financeira',
                    'agente_financeiro': 'Agente financeiro',
                    'nome_financiado': 'Nome financiado',
                    'cnpj_cpf': 'CNPJ/CPF',
                    'data_inclusao': 'Data inclusão'
                });
                if (intencaoRows) {
                    html += `<div class="section-card"><div class="section-title">Intenção de gravame</div>${intencaoRows}</div>`;
                }
            }
            
            modalBody.innerHTML = html || '<p style="text-align: center; color: #64748B;">Nenhuma informação de gravame encontrada.</p>';
            modal.classList.add('show');
        }

        function showDebitosDetails() {
            if (!window.resultData || !window.resultData.debitos_multas) {
                const modal = document.getElementById('detailModal');
                const modalTitle = document.getElementById('modalTitle');
                const modalBody = document.getElementById('modalBody');
                modalTitle.textContent = 'Multas e débitos';
                modalBody.innerHTML = '<p style="text-align: center; color: #64748B;">Nenhum débito informado.</p>';
                modal.classList.add('show');
                return;
            }
            
            const debitos = window.resultData.debitos_multas;
            const labels = {
                'dersa': 'DERSA',
                'der': 'DER',
                'detran': 'DETRAN',
                'cetesb': 'CETESB',
                'renainf': 'RENAINF',
                'municipais': 'Municipais',
                'prf': 'Polícia Rodoviária Federal',
                'ipva': 'IPVA'
            };
            
            let total = 0;
            let html = '';
            
            for (const [key, label] of Object.entries(labels)) {
                const value = debitos[key];
                if (value != null) {
                    const numValue = parseFloat(String(value).replace(/[^\d,.-]/g, '').replace(',', '.')) || 0;
                    total += numValue;
                    const formatted = typeof value === 'number' ? value.toFixed(2).replace('.', ',') : value;
                    html += `
                        <div class="info-row">
                            <div class="info-label">${label}</div>
                            <div class="info-value">R$ ${formatted}</div>
                        </div>
                    `;
                }
            }
            
            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = 'Multas e débitos';
            modalBody.innerHTML = `
                <div class="section-card">
                    <div class="info-row">
                        <div class="info-label">Total em aberto</div>
                        <div class="info-value" style="font-size: 24px; color: ${total > 0 ? '#EF4444' : '#4CAF50'};">R$ ${total.toFixed(2).replace('.', ',')}</div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title">Detalhamento</div>
                    ${html || '<p style="text-align: center; color: #64748B;">Nenhum débito informado.</p>'}
                </div>
            `;
            modal.classList.add('show');
        }

        function showRestricoesDetails() {
            if (!window.resultData || !window.resultData.restricoes) {
                const modal = document.getElementById('detailModal');
                const modalTitle = document.getElementById('modalTitle');
                const modalBody = document.getElementById('modalBody');
                modalTitle.textContent = 'Restrições';
                modalBody.innerHTML = '<p style="text-align: center; color: #64748B;">Nenhuma restrição informada.</p>';
                modal.classList.add('show');
                return;
            }
            
            const restricoesRows = buildInfoRows(window.resultData.restricoes, {
                'furto': 'Furto',
                'bloqueio_guincho': 'Bloqueio de guincho',
                'administrativas': 'Administrativas',
                'judicial': 'Judicial',
                'tributaria': 'Tributária',
                'renajud': 'RENAJUD',
                'inspecao_ambiental': 'Inspeção ambiental'
            });
            
            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = 'Restrições';
            modalBody.innerHTML = restricoesRows 
                ? `<div class="section-card">${restricoesRows}</div>`
                : '<p style="text-align: center; color: #64748B;">Nenhuma restrição informada.</p>';
            modal.classList.add('show');
        }

        function showComunicacaoDetails() {
            if (!window.resultData) return;
            
            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = 'Comunicações de venda';
            
            let html = '';
            
            if (window.resultData.comunicacao_vendas) {
                const comunicacaoRows = buildInfoRows(window.resultData.comunicacao_vendas, {
                    'status': 'Status',
                    'inclusao': 'Inclusão',
                    'tipo_doc_comprador': 'Tipo documento comprador',
                    'cnpj_cpf_comprador': 'CNPJ/CPF comprador',
                    'origem': 'Origem'
                });
                if (comunicacaoRows) {
                    html += `<div class="section-card"><div class="section-title">Comunicação</div>${comunicacaoRows}</div>`;
                }
            }
            
            if (window.resultData.comunicacao_vendas && window.resultData.comunicacao_vendas.datas) {
                const datasRows = buildInfoRows(window.resultData.comunicacao_vendas.datas, {
                    'venda': 'Venda',
                    'nota_fiscal': 'Nota fiscal',
                    'protocolo_detran': 'Protocolo DETRAN'
                });
                if (datasRows) {
                    html += `<div class="section-card"><div class="section-title">Datas</div>${datasRows}</div>`;
                }
            }
            
            modalBody.innerHTML = html || '<p style="text-align: center; color: #64748B;">Nenhuma comunicação de venda registrada.</p>';
            modal.classList.add('show');
        }

        function closeModal() {
            document.getElementById('detailModal').classList.remove('show');
        }

        // Fechar modal ao clicar fora
        document.getElementById('detailModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        async function registerPesquisa(placa, renavam) {
            try {
                await fetch(`${API_BASE_URL}/api/pesquisas`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nome: 'Base estadual',
                        placa: placa,
                        renavam: renavam || null,
                    })
                });
            } catch (error) {
                console.error('Erro ao registrar pesquisa:', error);
                // Não mostrar erro ao usuário, é apenas um registro
            }
        }

    </script>
</body>
</html>

