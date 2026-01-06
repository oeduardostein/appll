<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Home - LL Despachante</title>
    <style>
        :root {
            color-scheme: light;
            --primary: #0047AB;
            --primary-dark: #0B3E98;
            --accent: #2F80ED;
            --bg: #F8FAFC;
            --card: #E7EDFF;
            --card-shadow: 0 6px 12px rgba(14, 59, 145, 0.08);
            --white: #FFFFFF;
            --text-strong: #1E293B;
            --text-muted: #64748B;
            --text-soft: #667085;
            --divider: #E4E7EC;
            --disclaimer: #F0F4FF;
            --error: #EF4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--bg);
            color: var(--text-strong);
            min-height: 100vh;
        }

        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }

        .header {
            background: var(--primary);
            border-radius: 0 0 32px 32px;
            padding: 28px 20px 36px;
            color: var(--white);
        }

        .header-inner {
            max-width: 720px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand-avatar {
            width: 56px;
            height: 56px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            flex-shrink: 0;
        }

        .brand-avatar img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .icon-button {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.12);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .icon-button svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        .icon-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .btn-outline {
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.12);
            color: var(--white);
            padding: 10px 16px;
            font-size: 15px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .btn-outline svg {
            width: 18px;
            height: 18px;
            display: block;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .header-info {
            font-size: 16px;
            line-height: 1.5;
            font-weight: 600;
            color: var(--white);
        }

        .content {
            max-width: 720px;
            margin: 0 auto;
            padding: 24px 20px 40px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .hidden {
            display: none !important;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 14px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert.error {
            background: #FFE5E5;
            border: 1px solid var(--error);
            color: #C62828;
        }

        .actions-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .actions-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .action-card {
            background: var(--card);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .action-card__main {
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
            padding: 18px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            cursor: pointer;
        }

        .action-card__icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            flex-shrink: 0;
        }

        .action-card__icon svg {
            width: 26px;
            height: 26px;
            display: block;
        }

        .action-card__title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .action-card__description {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-top: 4px;
        }

        .action-card__chevron {
            margin-left: auto;
            color: #6377B8;
            transition: transform 0.2s ease;
        }

        .action-card__chevron svg {
            width: 18px;
            height: 18px;
            display: block;
        }

        .action-card--expanded .action-card__chevron {
            transform: rotate(90deg);
        }

        .action-card__sublist {
            background: var(--white);
            padding: 12px 18px 16px;
            display: none;
        }

        .action-card--expanded .action-card__sublist {
            display: block;
        }

        .action-subitem {
            width: 100%;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 10px 6px;
            cursor: pointer;
            text-align: left;
        }

        .action-subitem + .action-subitem {
            border-top: 1px solid var(--divider);
            margin-top: 8px;
            padding-top: 16px;
        }

        .action-subitem__icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: var(--card);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            flex-shrink: 0;
        }

        .action-subitem__icon svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        .action-subitem__label {
            font-size: 15px;
            font-weight: 500;
            color: #1D2939;
            flex: 1;
        }

        .action-subitem__chevron {
            color: #7D8FBD;
        }

        .action-subitem__chevron svg {
            width: 18px;
            height: 18px;
            display: block;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .disclaimer-card {
            background: var(--disclaimer);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            color: #1D1B20;
        }

        .disclaimer-card h3 {
            font-size: 16px;
            font-weight: 600;
        }

        .disclaimer-card p {
            font-size: 14px;
            line-height: 1.5;
        }

        .text-link-button {
            border: none;
            background: transparent;
            color: var(--primary);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            padding: 0;
        }

        .text-link-button svg {
            width: 18px;
            height: 18px;
            display: block;
        }

        .recent-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .recent-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .recent-card {
            background: var(--white);
            border-radius: 24px;
            padding: 18px 20px;
            box-shadow: 0 8px 12px rgba(16, 24, 40, 0.05);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .recent-card__plate {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-strong);
        }

        .recent-card__summary {
            font-size: 15px;
            color: #475467;
            line-height: 1.4;
            white-space: pre-line;
        }

        .recent-card__date {
            text-align: right;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-soft);
        }

        .empty-state,
        .status-message {
            font-size: 14px;
            color: var(--text-soft);
        }

        .status-message.error {
            color: var(--error);
        }

        .loading {
            display: none;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: var(--text-soft);
        }

        .loading.show {
            display: flex;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #E2E8F0;
            border-top-color: var(--primary);
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (min-width: 768px) {
            .header {
                padding: 32px 40px 44px;
            }

            .header-inner,
            .content {
                max-width: 860px;
            }

            .action-card__title {
                font-size: 19px;
            }

            .action-card__description {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-inner">
            <div class="header-top">
                <div class="brand-avatar">
                    <img src="{{ asset('images/logoll.png') }}" alt="LL Despachante">
                </div>
                <div class="header-actions">
                    <button class="icon-button" id="profileBtn" title="Meu perfil">
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
            <div class="header-info" id="userInfo">Usuário: -- • Créditos usados este mês: --</div>
        </div>
    </div>

    <main class="content">
        <section class="actions-section">
            <div class="loading" id="permissionsLoading">
                <div class="spinner"></div>
                <span>Carregando permissões...</span>
            </div>
            <div class="status-message error hidden" id="permissionsError">
                <span id="permissionsErrorText">Não foi possível carregar as permissões.</span>
                <button class="text-link-button" id="permissionsRetry" type="button">Tentar novamente</button>
            </div>
            <div class="status-message hidden" id="noActionsMessage">
                Nenhuma funcionalidade liberada para este usuário.
            </div>
            <div class="actions-list" id="actionsList">
                <div class="action-card" data-action="pesquisas">
                    <button class="action-card__main" type="button" data-toggle="pesquisas" aria-expanded="false">
                        <div class="action-card__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="action-card__title">Pesquisas</div>
                            <div class="action-card__description">
                                Base estadual, BIN, outros Estados, RENAINF, Gravame e bloqueios ativos.
                            </div>
                        </div>
                        <div class="action-card__chevron">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </button>
                    <div class="action-card__sublist">
                        <button class="action-subitem" type="button" data-permission="pesquisa_base_estadual" data-href="/base-estadual">
                            <div class="action-subitem__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 6-9 12-9 12S3 16 3 10a9 9 0 1 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </div>
                            <div class="action-subitem__label">Base estadual</div>
                            <div class="action-subitem__chevron">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>
                        </button>
                        <button class="action-subitem" type="button" data-permission="pesquisa_base_outros_estados" data-disabled="true">
                            <div class="action-subitem__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M2 12h20"></path>
                                    <path d="M12 2a15 15 0 0 1 0 20"></path>
                                </svg>
                            </div>
                            <div class="action-subitem__label">Base Outros Estados</div>
                            <div class="action-subitem__chevron">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>
                        </button>
                        <button class="action-subitem" type="button" data-permission="pesquisa_bin" data-disabled="true">
                            <div class="action-subitem__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                    <line x1="2" y1="10" x2="22" y2="10"></line>
                                </svg>
                            </div>
                            <div class="action-subitem__label">BIN</div>
                            <div class="action-subitem__chevron">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>
                        </button>
                        <button class="action-subitem" type="button" data-permission="pesquisa_gravame" data-disabled="true">
                            <div class="action-subitem__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="14" rx="2"></rect>
                                    <path d="M8 20h8"></path>
                                </svg>
                            </div>
                            <div class="action-subitem__label">Gravame</div>
                            <div class="action-subitem__chevron">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>
                        </button>
                        <button class="action-subitem" type="button" data-permission="pesquisa_renainf" data-disabled="true">
                            <div class="action-subitem__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path>
                                    <path d="M7 17l-1 4h12l-1-4"></path>
                                    <path d="M8 11h8"></path>
                                </svg>
                            </div>
                            <div class="action-subitem__label">Renainf</div>
                            <div class="action-subitem__chevron">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>
                        </button>
                        <button class="action-subitem" type="button" data-permission="pesquisa_bloqueios_ativos" data-disabled="true">
                            <div class="action-subitem__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>
                            <div class="action-subitem__label">Bloqueios Ativos</div>
                            <div class="action-subitem__chevron">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>
                        </button>
                        <button class="action-subitem" type="button" data-permission="pesquisa_andamento_processo" data-disabled="true">
                            <div class="action-subitem__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3v18h18"></path>
                                    <path d="m19 9-5 5-4-4-4 4"></path>
                                </svg>
                            </div>
                            <div class="action-subitem__label">Andamento do processo e-CRV</div>
                            <div class="action-subitem__chevron">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>

                <div class="action-card" data-permission="crlv">
                    <button class="action-card__main" type="button" data-disabled="true">
                        <div class="action-card__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <path d="M14 2v6h6"></path>
                                <line x1="8" y1="13" x2="16" y2="13"></line>
                            </svg>
                        </div>
                        <div>
                            <div class="action-card__title">CRLV-e</div>
                            <div class="action-card__description">Emissão do CRLV digital</div>
                        </div>
                    </button>
                </div>

                <div class="action-card" data-permission="atpv">
                    <button class="action-card__main" type="button" data-disabled="true">
                        <div class="action-card__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <path d="M9 2h6"></path>
                                <path d="m9 14 2 2 4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="action-card__title">Emissão da ATPV-e</div>
                            <div class="action-card__description">Preencher a autorização para transferência</div>
                        </div>
                    </button>
                </div>
            </div>
        </section>

        <section class="disclaimer-card">
            <h3>Aviso importante</h3>
            <p>Este aplicativo não é afiliado nem representa qualquer órgão governamental.</p>
            <p>As consultas exibidas aqui acessam diretamente as informações do portal oficial e-CRV SP (www.e-crvsp.sp.gov.br).</p>
            <button class="text-link-button" type="button" id="openPortalBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 3h7v7"></path>
                    <path d="M10 14 21 3"></path>
                    <path d="M21 14v7h-7"></path>
                    <path d="M3 10v11h11"></path>
                </svg>
                <span>Acessar e-CRV SP</span>
            </button>
        </section>

        <section class="recent-section">
            <div class="section-title">Últimos veículos pesquisados</div>
            <div class="loading" id="recentLoading">
                <div class="spinner"></div>
                <span>Carregando pesquisas...</span>
            </div>
            <div class="status-message error hidden" id="recentError"></div>
            <div class="status-message hidden" id="recentEmpty">Nenhuma pesquisa recente encontrada.</div>
            <div class="recent-list" id="recentList"></div>
            <button class="text-link-button hidden" id="recentRetry" type="button">Tentar novamente</button>
        </section>
    </main>

    <script>
        const API_BASE_URL = window.location.origin;
        let authToken = null;
        let permissionSlugs = null;

        const userInfoEl = document.getElementById('userInfo');
        const permissionsLoading = document.getElementById('permissionsLoading');
        const permissionsError = document.getElementById('permissionsError');
        const permissionsRetry = document.getElementById('permissionsRetry');
        const permissionsErrorText = document.getElementById('permissionsErrorText');
        const noActionsMessage = document.getElementById('noActionsMessage');
        const actionsList = document.getElementById('actionsList');

        const recentLoading = document.getElementById('recentLoading');
        const recentError = document.getElementById('recentError');
        const recentEmpty = document.getElementById('recentEmpty');
        const recentList = document.getElementById('recentList');
        const recentRetry = document.getElementById('recentRetry');

        function parseUser() {
            const raw = localStorage.getItem('user');
            if (!raw) return null;
            try {
                return JSON.parse(raw);
            } catch (error) {
                return null;
            }
        }

        function updateHeaderCredits({ status, count }) {
            const user = parseUser();
            const name = user?.username || user?.name || 'Usuário';
            let creditsLabel = 'Créditos usados este mês: --';

            if (status === 'loading') {
                creditsLabel = 'Créditos usados este mês: carregando...';
            } else if (status === 'error') {
                creditsLabel = 'Créditos usados este mês: indisponível';
            } else if (status === 'loaded') {
                creditsLabel = `Créditos usados este mês: ${count}`;
            }

            userInfoEl.textContent = `Usuário: ${name} • ${creditsLabel}`;
        }

        function handleUnauthorized() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = '/login';
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

        function checkAuth() {
            authToken = localStorage.getItem('auth_token');
            if (!authToken) {
                window.location.href = '/login';
                return false;
            }
            updateHeaderCredits({ status: 'loading', count: 0 });
            return true;
        }

        async function loadMonthlyCredits() {
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/api/pesquisas/ultimo-mes`);
                if (!response.ok) {
                    throw new Error('Falha ao carregar créditos.');
                }
                const data = await response.json();
                const count = Array.isArray(data.data) ? data.data.length : 0;
                updateHeaderCredits({ status: 'loaded', count });
            } catch (error) {
                updateHeaderCredits({ status: 'error', count: 0 });
            }
        }

        function setPermissionsLoading(isLoading) {
            permissionsLoading.classList.toggle('show', isLoading);
            permissionsError.classList.toggle('hidden', true);
            if (isLoading) {
                actionsList.classList.add('hidden');
                noActionsMessage.classList.add('hidden');
            }
        }

        function applyPermissions() {
            if (!permissionSlugs) return;

            const allowed = new Set(permissionSlugs);
            const actionCards = Array.from(actionsList.querySelectorAll('.action-card'));

            actionCards.forEach((card) => {
                const required = card.dataset.permission;
                if (required) {
                    card.classList.toggle('hidden', !allowed.has(required));
                    return;
                }

                if (card.dataset.action === 'pesquisas') {
                    const subItems = Array.from(card.querySelectorAll('.action-subitem'));
                    let hasVisibleSub = false;

                    subItems.forEach((item) => {
                        const permission = item.dataset.permission;
                        const isAllowed = !permission || allowed.has(permission);
                        item.classList.toggle('hidden', !isAllowed);
                        if (isAllowed) {
                            hasVisibleSub = true;
                        }
                    });

                    card.classList.toggle('hidden', !hasVisibleSub);
                }
            });

            const visibleCards = actionCards.filter((card) => !card.classList.contains('hidden'));
            const hasVisible = visibleCards.length !== 0;
            noActionsMessage.classList.toggle('hidden', hasVisible);
            actionsList.classList.toggle('hidden', !hasVisible);
        }

        async function loadPermissions() {
            setPermissionsLoading(true);
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/api/user/permissions`);
                if (!response.ok) {
                    throw new Error('Falha ao carregar permissões.');
                }
                const data = await response.json();
                permissionSlugs = Array.isArray(data.slugs)
                    ? data.slugs.map((slug) => String(slug))
                    : [];

                setPermissionsLoading(false);
                actionsList.classList.remove('hidden');
                permissionsError.classList.add('hidden');
                applyPermissions();
            } catch (error) {
                permissionSlugs = null;
                permissionsLoading.classList.remove('show');
                permissionsError.classList.remove('hidden');
                permissionsErrorText.textContent = 'Não foi possível carregar as permissões.';
                actionsList.classList.add('hidden');
                noActionsMessage.classList.add('hidden');
            }
        }

        function formatDate(value) {
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return '';
            }
            const day = date.toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            });
            const time = date.toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit',
            });
            return `${day} – ${time}`;
        }

        function createRecentCard(item) {
            const plate = (item.placa || '').trim().toUpperCase();
            const chassi = (item.chassi || '').trim().toUpperCase();
            const leading = plate || chassi || item.nome || 'Consulta';

            const details = [item.nome || ''];
            if (item.renavam) {
                details.push(`Renavam: ${item.renavam}`);
            }
            if (item.chassi) {
                details.push(`Chassi: ${item.chassi}`);
            }
            if (item.opcao_pesquisa) {
                details.push(`Opção: ${item.opcao_pesquisa}`);
            }

            const card = document.createElement('div');
            card.className = 'recent-card';

            const plateEl = document.createElement('div');
            plateEl.className = 'recent-card__plate';
            plateEl.textContent = leading;

            const summaryEl = document.createElement('div');
            summaryEl.className = 'recent-card__summary';
            summaryEl.textContent = details.filter(Boolean).join('\n');

            const dateEl = document.createElement('div');
            dateEl.className = 'recent-card__date';
            dateEl.textContent = formatDate(item.created_at);

            card.appendChild(plateEl);
            card.appendChild(summaryEl);
            card.appendChild(dateEl);

            return card;
        }

        async function loadRecentVehicles() {
            recentLoading.classList.add('show');
            recentError.classList.add('hidden');
            recentEmpty.classList.add('hidden');
            recentRetry.classList.add('hidden');
            recentList.innerHTML = '';

            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/api/pesquisas`);
                if (!response.ok) {
                    throw new Error('Falha ao carregar pesquisas.');
                }
                const data = await response.json();
                const items = Array.isArray(data.data) ? data.data : [];

                if (items.length === 0) {
                    recentEmpty.classList.remove('hidden');
                } else {
                    items.forEach((item) => {
                        recentList.appendChild(createRecentCard(item));
                    });
                }
            } catch (error) {
                recentError.textContent = 'Não foi possível carregar as pesquisas recentes.';
                recentError.classList.remove('hidden');
                recentRetry.classList.remove('hidden');
            } finally {
                recentLoading.classList.remove('show');
            }
        }

        function setupActionToggles() {
            document.querySelectorAll('[data-toggle]').forEach((toggle) => {
                toggle.addEventListener('click', () => {
                    const card = toggle.closest('.action-card');
                    if (!card) return;
                    const expanded = card.classList.toggle('action-card--expanded');
                    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                });
            });

            document.querySelectorAll('[data-href]').forEach((item) => {
                item.addEventListener('click', () => {
                    const href = item.dataset.href;
                    if (href) {
                        window.location.href = href;
                    }
                });
            });

            document.querySelectorAll('[data-disabled="true"]').forEach((item) => {
                item.addEventListener('click', () => {
                    alert('Funcionalidade em desenvolvimento.');
                });
            });
        }

        document.getElementById('logoutBtn').addEventListener('click', async function() {
            if (!confirm('Deseja realmente sair?')) {
                return;
            }

            try {
                if (authToken) {
                    await fetch(`${API_BASE_URL}/api/auth/logout`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${authToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                    });
                }
            } catch (error) {
                console.error('Erro ao fazer logout:', error);
            } finally {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                window.location.href = '/login';
            }
        });

        document.getElementById('profileBtn').addEventListener('click', function() {
            alert('Funcionalidade em desenvolvimento.');
        });

        document.getElementById('openPortalBtn').addEventListener('click', function() {
            window.open('https://www.e-crvsp.sp.gov.br/', '_blank', 'noopener');
        });

        permissionsRetry.addEventListener('click', loadPermissions);
        recentRetry.addEventListener('click', loadRecentVehicles);

        if (checkAuth()) {
            setupActionToggles();
            loadMonthlyCredits();
            loadPermissions();
            loadRecentVehicles();
        }
    </script>
</body>
</html>
