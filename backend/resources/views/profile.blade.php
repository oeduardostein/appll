<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Meu perfil - LL Despachante</title>
    <style>
        :root {
            color-scheme: light;
            --primary: #0047AB;
            --primary-dark: #0B3E98;
            --primary-soft: #6C8EDC;
            --primary-ghost: rgba(255, 255, 255, 0.2);
            --bg: #F8FAFC;
            --text-strong: #1D2939;
            --text-muted: #667085;
            --card: #FFFFFF;
            --card-shadow: 0 10px 18px rgba(16, 24, 40, 0.08);
            --danger: #EF4444;
            --danger-bg: #FDECEC;
            --danger-border: #F7B2B2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text-strong);
            min-height: 100vh;
        }

        button {
            font-family: inherit;
        }

        .topbar {
            background: var(--primary);
            color: #fff;
            border-radius: 0 0 32px 32px;
            padding: 24px 20px 32px;
        }

        .topbar-inner {
            max-width: 720px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .icon-button {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .icon-button:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .icon-button:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .icon-button svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        .icon-button.is-loading svg {
            animation: spin 0.8s linear infinite;
        }

        .topbar-title {
            flex: 1;
        }

        .topbar-title h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .topbar-title p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
        }

        .content {
            max-width: 720px;
            margin: 0 auto;
            padding: 20px 20px 40px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .alert {
            display: none;
            padding: 14px 16px;
            border-radius: 12px;
            background: #FFE5E5;
            color: #C62828;
            font-size: 14px;
        }

        .alert.show {
            display: block;
        }

        .profile-hero {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #A6B8F3 100%);
            border-radius: 28px;
            padding: 24px;
            color: #fff;
            box-shadow: 0 16px 28px rgba(14, 59, 145, 0.18);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .profile-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--primary-ghost);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
        }

        .profile-info h2 {
            font-size: 26px;
            font-weight: 700;
        }

        .profile-info p {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.85);
            margin-top: 6px;
        }

        .profile-id {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            padding: 10px 14px;
            font-size: 15px;
            font-weight: 600;
        }

        .profile-id svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        .card {
            background: var(--card);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        .card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .tips-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .tips-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.5;
        }

        .tips-item svg {
            width: 22px;
            height: 22px;
            color: var(--primary);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .danger-card {
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            border-radius: 20px;
            padding: 20px;
            color: var(--text-muted);
        }

        .danger-card h3 {
            color: var(--danger);
            margin-bottom: 12px;
            font-size: 18px;
            font-weight: 600;
        }

        .danger-card p {
            line-height: 1.5;
            font-size: 15px;
        }

        .danger-button {
            margin-top: 16px;
            width: 100%;
            border: none;
            border-radius: 16px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            background: var(--danger);
            color: #fff;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .danger-button:hover:not(:disabled) {
            background: #E53935;
            transform: translateY(-1px);
        }

        .danger-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (min-width: 768px) {
            .topbar {
                padding: 28px 40px 36px;
            }

            .topbar-inner,
            .content {
                max-width: 860px;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-inner">
            <button class="icon-button" id="backBtn" title="Voltar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <div class="topbar-title">
                <h1>Meu perfil</h1>
                <p>Gerencie suas informações</p>
            </div>
            <button class="icon-button" id="refreshBtn" title="Atualizar dados">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0 1 14.13-3.36L23 10"></path>
                    <path d="M1 14l5.37 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
            </button>
        </div>
    </div>

    <main class="content">
        <div class="alert" id="errorAlert"></div>

        <section class="profile-hero">
            <div class="profile-header">
                <div class="profile-avatar" id="userAvatar">--</div>
                <div class="profile-info">
                    <h2 id="userName">Carregando...</h2>
                    <p id="userEmail">Carregando...</p>
                </div>
            </div>
            <div class="profile-id" id="userId">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                    <path d="M8 10h8"></path>
                    <path d="M8 14h5"></path>
                    <circle cx="8" cy="7" r="1"></circle>
                </svg>
                <span>ID do usuário: --</span>
            </div>
        </section>

        <section class="card">
            <h3>Boas práticas de segurança</h3>
            <ul class="tips-list">
                <li class="tips-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    Mantenha seu email atualizado para receber avisos importantes.
                </li>
                <li class="tips-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    Use senhas fortes e altere-as regularmente.
                </li>
                <li class="tips-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    Finalize a sessão ao utilizar dispositivos compartilhados.
                </li>
            </ul>
        </section>

        <section class="danger-card">
            <h3>Zona vermelha</h3>
            <p>
                Excluir sua conta remove definitivamente seus dados e acessos.
                Esta ação não pode ser desfeita.
            </p>
            <button class="danger-button" id="deleteAccountBtn">Excluir conta</button>
        </section>
    </main>

    <script>
        const API_BASE_URL = window.location.origin;
        let authToken = null;
        let isLoadingUser = false;

        const errorAlert = document.getElementById('errorAlert');
        const userAvatar = document.getElementById('userAvatar');
        const userName = document.getElementById('userName');
        const userEmail = document.getElementById('userEmail');
        const userId = document.getElementById('userId');
        const refreshBtn = document.getElementById('refreshBtn');
        const deleteBtn = document.getElementById('deleteAccountBtn');

        function getStoredItem(key) {
            return sessionStorage.getItem(key) || localStorage.getItem(key);
        }

        function isRememberedAuth() {
            return !!localStorage.getItem('auth_token');
        }

        function setStoredItem(key, value) {
            if (isRememberedAuth()) {
                localStorage.setItem(key, value);
                sessionStorage.removeItem(key);
            } else {
                sessionStorage.setItem(key, value);
                localStorage.removeItem(key);
            }
        }

        function clearStoredAuth() {
            sessionStorage.removeItem('auth_token');
            localStorage.removeItem('auth_token');
            sessionStorage.removeItem('user');
            localStorage.removeItem('user');
        }

        function showError(message) {
            errorAlert.textContent = message;
            errorAlert.classList.add('show');
            setTimeout(() => errorAlert.classList.remove('show'), 5000);
        }

        function parseUser() {
            const raw = getStoredItem('user');
            if (!raw) return null;
            try {
                return JSON.parse(raw);
            } catch (error) {
                return null;
            }
        }

        function updateUserInfo(user) {
            const name = user?.username || user?.name || 'Usuário';
            const email = user?.email || 'Email não informado';
            const idValue = user?.id ? `ID do usuário: ${user.id}` : 'ID do usuário: --';

            userName.textContent = name;
            userEmail.textContent = email;
            userId.querySelector('span').textContent = idValue;

            const initial = name.trim().charAt(0).toUpperCase() || '--';
            userAvatar.textContent = initial;
        }

        function handleUnauthorized() {
            clearStoredAuth();
            window.location.href = '/login';
        }

        function setLoading(loading) {
            isLoadingUser = loading;
            refreshBtn.disabled = loading;
            refreshBtn.classList.toggle('is-loading', loading);
        }

        async function fetchWithAuth(url, options = {}) {
            const headers = {
                'Accept': 'application/json',
                ...(options.headers || {}),
                'Authorization': `Bearer ${authToken}`,
            };
            const response = await fetch(url, { ...options, headers });
            if (response.status === 401) {
                handleUnauthorized();
                throw new Error('Não autenticado.');
            }
            return response;
        }

        async function loadUser() {
            if (isLoadingUser) return;
            setLoading(true);
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/api/auth/user`);
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Não foi possível carregar o perfil.');
                }
                if (data.user) {
                    setStoredItem('user', JSON.stringify(data.user));
                    updateUserInfo(data.user);
                }
            } catch (error) {
                showError(error.message || 'Não foi possível carregar o perfil.');
            } finally {
                setLoading(false);
            }
        }

        async function deleteAccount() {
            if (!confirm('Tem certeza que deseja excluir sua conta? Essa ação é definitiva.')) {
                return;
            }

            const originalLabel = deleteBtn.textContent;
            deleteBtn.disabled = true;
            deleteBtn.textContent = 'Excluindo...';

            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/api/auth/delete-account`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.message || 'Não foi possível excluir a conta.');
                }
                clearStoredAuth();
                window.location.href = '/login';
            } catch (error) {
                showError(error.message || 'Não foi possível excluir a conta.');
            } finally {
                deleteBtn.disabled = false;
                deleteBtn.textContent = originalLabel;
            }
        }

        document.getElementById('backBtn').addEventListener('click', () => {
            window.location.href = '/home';
        });

        refreshBtn.addEventListener('click', loadUser);
        deleteBtn.addEventListener('click', deleteAccount);

        authToken = getStoredItem('auth_token');
        if (!authToken) {
            handleUnauthorized();
        } else {
            const cached = parseUser();
            if (cached) {
                updateUserInfo(cached);
            }
            loadUser();
        }
    </script>
</body>
</html>
