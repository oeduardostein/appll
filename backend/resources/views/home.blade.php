<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Home - LL Despachante</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .stats-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 18px rgba(16, 24, 40, 0.05);
            padding: 24px;
            margin-bottom: 24px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        @media (min-width: 640px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .stat-item {
            background: #F8FAFC;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
        }

        .stat-label {
            font-size: 14px;
            color: #64748B;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #0047AB;
        }

        .action-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 18px rgba(16, 24, 40, 0.05);
            padding: 24px;
            margin-bottom: 24px;
            text-align: center;
        }

        .action-card h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 16px;
        }

        .btn-primary {
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

        .btn-primary:hover:not(:disabled) {
            background: #003d99;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 71, 171, 0.3);
        }

        .history-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 18px rgba(16, 24, 40, 0.05);
            padding: 24px;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .history-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1E293B;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .history-item {
            background: #F8FAFC;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            transition: all 0.3s;
        }

        .history-item:hover {
            border-color: #0047AB;
            box-shadow: 0 2px 8px rgba(0, 71, 171, 0.1);
        }

        .history-item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }

        .history-item-title {
            font-size: 16px;
            font-weight: 600;
            color: #1E293B;
        }

        .history-item-date {
            font-size: 12px;
            color: #64748B;
        }

        .history-item-details {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 8px;
        }

        .history-badge {
            background: white;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            border: 1px solid #E2E8F0;
        }

        .history-badge strong {
            color: #1E293B;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #64748B;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            opacity: 0.5;
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
                        <div class="header-title" id="userInfo">Bem-vindo</div>
                        <div class="header-subtitle" id="consultasInfo">Carregando...</div>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn-outline" onclick="window.location.href='/base-estadual'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <span>Nova Consulta</span>
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
        <div class="alert error" id="errorAlert"></div>

        <div class="stats-card">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-label">Consultas este mês</div>
                    <div class="stat-value" id="monthlyCount">0</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Últimas 5 consultas</div>
                    <div class="stat-value" id="recentCount">0</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Total de consultas</div>
                    <div class="stat-value" id="totalCount">0</div>
                </div>
            </div>
        </div>

        <div class="action-card">
            <h2>Realizar Nova Consulta</h2>
            <button class="btn-primary" onclick="window.location.href='/base-estadual'">
                Consultar Base Estadual
            </button>
        </div>

        <div class="history-card">
            <div class="history-header">
                <h2>Histórico de Consultas</h2>
            </div>
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Carregando histórico...</p>
            </div>
            <div class="history-list" id="historyList"></div>
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

        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.textContent = message;
            alert.classList.add('show');
            setTimeout(() => alert.classList.remove('show'), 5000);
        }

        async function loadStats() {
            try {
                // Carregar pesquisas do último mês
                const lastMonthResponse = await fetch(`${API_BASE_URL}/api/pesquisas/ultimo-mes`, {
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json',
                    }
                });

                if (lastMonthResponse.ok) {
                    const lastMonthData = await lastMonthResponse.json();
                    const monthlyCount = lastMonthData.data ? lastMonthData.data.length : 0;
                    document.getElementById('monthlyCount').textContent = monthlyCount;
                    
                    // Atualizar subtítulo do header
                    const consultasInfo = document.getElementById('consultasInfo');
                    consultasInfo.textContent = `${monthlyCount} consulta${monthlyCount !== 1 ? 's' : ''} realizada${monthlyCount !== 1 ? 's' : ''} este mês`;
                }

                // Carregar últimas 5 pesquisas
                const recentResponse = await fetch(`${API_BASE_URL}/api/pesquisas`, {
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json',
                    }
                });

                if (recentResponse.ok) {
                    const recentData = await recentResponse.json();
                    const recentCount = recentData.data ? recentData.data.length : 0;
                    document.getElementById('recentCount').textContent = recentCount;
                    document.getElementById('totalCount').textContent = recentCount; // Por enquanto, usar o mesmo valor
                }
            } catch (error) {
                console.error('Erro ao carregar estatísticas:', error);
            }
        }

        async function loadHistory() {
            const loading = document.getElementById('loading');
            const historyList = document.getElementById('historyList');
            
            loading.classList.add('show');
            historyList.innerHTML = '';

            try {
                const response = await fetch(`${API_BASE_URL}/api/pesquisas/ultimo-mes`, {
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Falha ao carregar histórico');
                }

                const data = await response.json();
                const pesquisas = data.data || [];

                if (pesquisas.length === 0) {
                    historyList.innerHTML = `
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"></path>
                            </svg>
                            <p>Nenhuma consulta realizada ainda.</p>
                        </div>
                    `;
                } else {
                    pesquisas.forEach(pesquisa => {
                        const item = createHistoryItem(pesquisa);
                        historyList.appendChild(item);
                    });
                }

            } catch (error) {
                showError('Não foi possível carregar o histórico de consultas.');
                historyList.innerHTML = `
                    <div class="empty-state">
                        <p style="color: #EF4444;">Erro ao carregar histórico</p>
                    </div>
                `;
            } finally {
                loading.classList.remove('show');
            }
        }

        function createHistoryItem(pesquisa) {
            const item = document.createElement('div');
            item.className = 'history-item';

            const date = new Date(pesquisa.created_at);
            const formattedDate = date.toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            const badges = [];
            if (pesquisa.placa) {
                badges.push(`<span class="history-badge"><strong>Placa:</strong> ${pesquisa.placa}</span>`);
            }
            if (pesquisa.renavam) {
                badges.push(`<span class="history-badge"><strong>RENAVAM:</strong> ${pesquisa.renavam}</span>`);
            }
            if (pesquisa.chassi) {
                badges.push(`<span class="history-badge"><strong>Chassi:</strong> ${pesquisa.chassi}</span>`);
            }

            item.innerHTML = `
                <div class="history-item-header">
                    <div class="history-item-title">${pesquisa.nome}</div>
                    <div class="history-item-date">${formattedDate}</div>
                </div>
                ${badges.length > 0 ? `<div class="history-item-details">${badges.join('')}</div>` : ''}
            `;

            return item;
        }

        // Inicialização
        if (checkAuth()) {
            loadStats();
            loadHistory();
        }
    </script>
</body>
</html>

