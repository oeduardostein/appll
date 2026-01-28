<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Teste Planilha Gravame - LL Despachante</title>
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

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
            --brand-primary: #0b4ea2;
            --brand-primary-hover: #093f82;
            --brand-light: #eff4ff;
            --text-default: #475569;
            --surface: #ffffff;
            --surface-muted: #f3f5f9;
            --border: #d0d9e3;
            font-family: 'Instrument Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text-strong);
            font-family: inherit;
        }

        a {
            color: inherit;
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
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .brand-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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

        .content-wide {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 24px 64px;
        }

        .hidden {
            display: none !important;
        }

        .permission-gate {
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }

        .permission-gate.hidden {
            display: none;
        }

        .permission-gate__card {
            background: var(--surface);
            padding: 32px;
            border-radius: 18px;
            text-align: center;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.08);
            max-width: 520px;
        }

        .permission-gate__card h2 {
            margin: 0 0 12px;
            color: var(--text-strong);
        }

        .permission-gate__card p {
            margin: 0 0 18px;
            color: var(--text-muted);
        }

        .permission-gate__card a {
            color: var(--brand-primary);
            font-weight: 600;
            text-decoration: none;
        }

        @media (min-width: 768px) {
            .header {
                padding: 32px 40px 44px;
            }

            .header-inner {
                max-width: 860px;
            }
        }
    </style>

</head>
<body>
    @include('components.home.header')

    <main class="content-wide">
        <section id="planilhaApp">
<style>
        .teste-planilha__header {
            margin-bottom: 32px;
        }

        .teste-planilha__header h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .teste-planilha__header p {
            margin: 8px 0 0;
            color: var(--text-muted);
            font-size: 15px;
        }

        .teste-planilha__card {
            background: var(--surface);
            border-radius: 18px;
            box-shadow:
                0 24px 48px rgba(15, 23, 42, 0.08),
                0 1px 0 rgba(255, 255, 255, 0.6);
            padding: 28px 32px;
            margin-bottom: 24px;
        }

        .teste-planilha__card h2 {
            margin: 0 0 20px;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .teste-planilha__form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }

        .teste-planilha__form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1 1 auto;
            min-width: 200px;
        }

        .teste-planilha__form-group label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .teste-planilha__input {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            color: var(--text-default);
            background: #fff;
            outline: none;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .teste-planilha__input:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(11, 78, 162, 0.12);
        }

        .teste-planilha__file-input {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: border-color 160ms ease, background-color 160ms ease;
        }

        .teste-planilha__file-input:hover {
            border-color: var(--brand-primary);
            background: rgba(11, 78, 162, 0.02);
        }

        .teste-planilha__file-input.has-file {
            border-color: #16a34a;
            background: rgba(22, 163, 74, 0.04);
        }

        .teste-planilha__file-input input {
            display: none;
        }

        .teste-planilha__file-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .teste-planilha__file-label svg {
            opacity: 0.6;
        }

        .teste-planilha__file-name {
            font-weight: 600;
            color: var(--text-strong);
        }

        .teste-planilha__btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 12px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }

        .teste-planilha__btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .teste-planilha__btn--primary {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(11, 78, 162, 0.2);
        }

        .teste-planilha__btn--primary:hover:not(:disabled) {
            background: var(--brand-primary-hover);
            transform: translateY(-1px);
        }

        .teste-planilha__btn--secondary {
            background: #f3f5f9;
            color: var(--text-default);
            border: 1px solid var(--border);
        }

        .teste-planilha__btn--secondary:hover:not(:disabled) {
            background: #e8ecf3;
        }

        .teste-planilha__btn--warning {
            background: #f97316;
            color: #fff;
            box-shadow: 0 12px 24px rgba(249, 115, 22, 0.2);
        }

        .teste-planilha__btn--warning:hover:not(:disabled) {
            background: #ea580c;
            transform: translateY(-1px);
        }

        .teste-planilha__btn--success {
            background: #16a34a;
            color: #fff;
            box-shadow: 0 12px 24px rgba(22, 163, 74, 0.2);
        }

        .teste-planilha__btn--success:hover:not(:disabled) {
            background: #15803d;
            transform: translateY(-1px);
        }

        .teste-planilha__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .teste-planilha__progress {
            display: none;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: #f0f9ff;
            border-radius: 12px;
            margin-top: 20px;
        }

        .teste-planilha__progress.is-visible {
            display: flex;
        }

        .teste-planilha__progress-bar {
            flex: 1;
            height: 8px;
            background: #e0e7ef;
            border-radius: 999px;
            overflow: hidden;
        }

        .teste-planilha__progress-fill {
            height: 100%;
            width: 0%;
            background: var(--brand-primary);
            border-radius: 999px;
            transition: width 300ms ease;
        }

        .teste-planilha__progress-text {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-default);
            min-width: 80px;
            text-align: right;
        }

        .teste-planilha__table-wrapper {
            overflow-x: auto;
            border-radius: 16px;
            background: var(--surface);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        }

        .teste-planilha__table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .teste-planilha__table thead {
            background: #f7f9fc;
        }

        .teste-planilha__table th {
            text-align: left;
            padding: 18px 20px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .teste-planilha__table td {
            padding: 14px 20px;
            font-size: 14px;
            border-top: 1px solid #ecf1f8;
            vertical-align: middle;
        }

        .teste-planilha__table tr[data-status="pending"] td {
            color: var(--text-muted);
        }

        .teste-planilha__table tr[data-status="loading"] td {
            background: #fffbeb;
        }

        .teste-planilha__table tr[data-status="success"] td {
            background: #f0fdf4;
        }

        .teste-planilha__table tr[data-status="error"] td {
            background: #fef2f2;
        }

        .teste-planilha__status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .teste-planilha__status--pending {
            background: #f3f4f6;
            color: #6b7280;
        }

        .teste-planilha__status--loading {
            background: #fef3c7;
            color: #b45309;
        }

        .teste-planilha__status--success {
            background: #dcfce7;
            color: #16a34a;
        }

        .teste-planilha__status--error {
            background: #fee2e2;
            color: #dc2626;
        }

        .teste-planilha__status--warning {
            background: #fef3c7;
            color: #b45309;
        }

        .teste-planilha__result {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .teste-planilha__result-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
            width: fit-content;
        }

        .teste-planilha__result-badge--liberado {
            background: #dcfce7;
            color: #16a34a;
        }

        .teste-planilha__result-badge--nao-liberado {
            background: #fee2e2;
            color: #dc2626;
        }

        .teste-planilha__result-badge--erro {
            background: #f3f4f6;
            color: #6b7280;
        }

        .teste-planilha__result-detail {
            font-size: 12px;
            color: var(--text-muted);
        }

        .teste-planilha__spinner {
            width: 14px;
            height: 14px;
            border: 2px solid #fbbf24;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 800ms linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .teste-planilha__empty {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-muted);
        }

        .teste-planilha__empty svg {
            margin-bottom: 16px;
            opacity: 0.4;
        }

        .teste-planilha__obs-warning {
            font-weight: 600;
            color: #dc2626;
        }
    </style>

    <header class="teste-planilha__header">
        <h1>Teste de planilha Gravame</h1>
        <p>Faça upload de uma planilha XLSX com placas/renavams para consultar gravame ativo e intenção de gravame.</p>
    </header>

    <div class="teste-planilha__card">
        <h2>1. Upload da Planilha</h2>

        <div class="teste-planilha__file-input" id="fileDropZone">
            <input type="file" id="fileInput" accept=".xlsx,.xls">
            <label for="fileInput" class="teste-planilha__file-label">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Clique para selecionar ou arraste um arquivo XLSX</span>
                <span class="teste-planilha__file-name" id="fileName" style="display: none;"></span>
            </label>
        </div>

        <p style="margin-top: 12px; font-size: 13px; color: var(--text-muted);">
            A planilha deve conter a coluna <strong>PLACA</strong>. As demais colunas serão mantidas no resultado.
        </p>
    </div>

    <div class="teste-planilha__card" id="verificationCard" style="display: none;">
        <h2>2. Consulta de Gravame</h2>

        <p style="margin-top: 12px; font-size: 13px; color: var(--text-muted);">
            A automação irá consultar gravame ativo e intenção de gravame para cada placa da planilha.
        </p>

        <div class="teste-planilha__actions">
            <button type="button" class="teste-planilha__btn teste-planilha__btn--primary" id="btnIniciar" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M5 3l14 9-14 9V3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Iniciar Consulta
            </button>

            <button type="button" class="teste-planilha__btn teste-planilha__btn--secondary" id="btnLimpar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Limpar
            </button>

            <button type="button" class="teste-planilha__btn teste-planilha__btn--warning" id="btnPause" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M8 5h3v14H8zM13 5h3v14h-3z" fill="currentColor"/>
                </svg>
                Pausar
            </button>
        </div>

        <div class="teste-planilha__progress" id="progressBar">
            <div class="teste-planilha__progress-bar">
                <div class="teste-planilha__progress-fill" id="progressFill"></div>
            </div>
            <span class="teste-planilha__progress-text" id="progressText">0%</span>
        </div>
    </div>

    <div class="teste-planilha__card" id="resultsCard" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <h2 style="margin: 0;">3. Resultados</h2>

            <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                <button type="button" class="teste-planilha__btn teste-planilha__btn--success" id="btnDownloadLiberados" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Baixar Liberados
                </button>

                <button type="button" class="teste-planilha__btn teste-planilha__btn--warning" id="btnDownloadGravame" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Baixar Com Gravame
                </button>
            </div>
        </div>

        <div class="teste-planilha__table-wrapper" style="margin-top: 24px;">
            <table class="teste-planilha__table" id="resultsTable">
                <thead>
                    <tr>
                        <th>#</th>
                <th>Placa</th>
                <th>Nome</th>
                        <th>Resultado</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="resultsBody">
                </tbody>
            </table>
        </div>
    </div>

    <div class="teste-planilha__card" id="emptyState">
        <div class="teste-planilha__empty">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <p>Nenhuma planilha carregada.<br>Faça upload de um arquivo XLSX para começar.</p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>

        const API_BASE_URL = window.location.origin;
        const REQUIRED_PERMISSION = 'teste_planilha_gravame';
        const planilhaApp = document.getElementById('planilhaApp');
        const permissionGate = document.getElementById('permissionGate');
        const userInfoEl = document.getElementById('userInfo');
        let authToken = null;

        function parseUser() {
            const raw = localStorage.getItem('user');
            if (!raw) return null;
            try {
                return JSON.parse(raw);
            } catch (_) {
                return null;
            }
        }

        function updateHeaderCredits({ status, count }) {
            if (!userInfoEl) return;
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

        async function loadMonthlyCredits() {
            try {
                const response = await fetchWithAuth(`${API_BASE_URL}/api/pesquisas/ultimo-mes`);
                if (!response.ok) {
                    throw new Error('Falha ao carregar créditos.');
                }
                const data = await response.json();
                const count = Array.isArray(data.data) ? data.data.length : 0;
                updateHeaderCredits({ status: 'loaded', count });
            } catch (_) {
                updateHeaderCredits({ status: 'error', count: 0 });
            }
        }

        function setupHeaderActions() {
            const logoutBtn = document.getElementById('logoutBtn');
            const profileBtn = document.getElementById('profileBtn');

            logoutBtn?.addEventListener('click', async () => {
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
                } catch (_) {
                    // ignore
                } finally {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user');
                    window.location.href = '/login';
                }
            });

            profileBtn?.addEventListener('click', () => {
                window.location.href = '/perfil';
            });
        }

        function initAuth() {
            authToken = localStorage.getItem('auth_token');
            if (!authToken) {
                window.location.href = '/login';
                return false;
            }
            updateHeaderCredits({ status: 'loading', count: 0 });
            return true;
        }

        async function ensurePermission() {
            let response;
            try {
                response = await fetchWithAuth(`${API_BASE_URL}/api/user/permissions`);
            } catch (_) {
                return false;
            }

            if (!response.ok) {
                return false;
            }

            const data = await response.json().catch(() => ({}));
            const slugs = Array.isArray(data.slugs) ? data.slugs : [];
            const allowed = slugs.includes(REQUIRED_PERMISSION);
            if (!allowed) {
                planilhaApp.style.display = 'none';
                permissionGate.classList.remove('hidden');
            }
            return allowed;
        }

        (async () => {
            if (!initAuth()) return;
            setupHeaderActions();
            loadMonthlyCredits();

            const allowed = await ensurePermission();
            if (!allowed) return;

        (function() {
            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const CONSULTAR_URL = '{{ route("teste-planilha-gravame.consultar") }}';
            const EXPORTAR_URL = '{{ route("teste-planilha-gravame.exportar") }}';
            const CAPTCHA_SOLVE_URL = "{{ url('api/captcha/solve') }}";
            const OMIT_EXPORT_COLUMNS = new Set(['RENAVAM']);

            let planilhaData = [];
            let planilhaColumns = [];
            let isProcessing = false;
            let isPaused = false;
            let pauseResolvers = [];

            // Elements
            const fileInput = document.getElementById('fileInput');
            const fileDropZone = document.getElementById('fileDropZone');
            const fileName = document.getElementById('fileName');
            const btnIniciar = document.getElementById('btnIniciar');
            const btnLimpar = document.getElementById('btnLimpar');
            const btnPause = document.getElementById('btnPause');
            const btnDownloadLiberados = document.getElementById('btnDownloadLiberados');
            const btnDownloadGravame = document.getElementById('btnDownloadGravame');
            const progressBar = document.getElementById('progressBar');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            const resultsBody = document.getElementById('resultsBody');
            const verificationCard = document.getElementById('verificationCard');
            const resultsCard = document.getElementById('resultsCard');
            const emptyState = document.getElementById('emptyState');

            // Drag and drop
            fileDropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileDropZone.style.borderColor = 'var(--brand-primary)';
                fileDropZone.style.background = 'rgba(11, 78, 162, 0.04)';
            });

            fileDropZone.addEventListener('dragleave', () => {
                fileDropZone.style.borderColor = '';
                fileDropZone.style.background = '';
            });

            fileDropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                fileDropZone.style.borderColor = '';
                fileDropZone.style.background = '';
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    handleFileSelect(files[0]);
                }
            });

            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleFileSelect(e.target.files[0]);
                }
            });

            function handleFileSelect(file) {
                if (!file.name.match(/\.(xlsx|xls)$/i)) {
                    alert('Por favor, selecione um arquivo Excel (.xlsx ou .xls)');
                    return;
                }

                fileName.textContent = file.name;
                fileName.style.display = 'block';
                fileDropZone.classList.add('has-file');

                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                        const rows = XLSX.utils.sheet_to_json(firstSheet, { header: 1, defval: '' });

                        if (rows.length === 0) {
                            alert('A planilha está vazia.');
                            return;
                        }

                        const headerRowIndex = findHeaderRowIndex(rows);
                        if (headerRowIndex === -1) {
                            alert('Não foi possível localizar a coluna PLACA na planilha.');
                            return;
                        }

                        const headerRow = rows[headerRowIndex].map(cell => String(cell || '').trim());
                        const normalizedHeaders = headerRow.map(value => normalizeHeaderLabel(value));

                        planilhaColumns = [];
                        const columnIndexes = [];
                        headerRow.forEach((label, idx) => {
                            const trimmed = String(label || '').trim();
                            if (!trimmed) {
                                return;
                            }

                            let uniqueLabel = trimmed;
                            let suffix = 2;
                            while (planilhaColumns.includes(uniqueLabel)) {
                                uniqueLabel = `${trimmed} (${suffix})`;
                                suffix += 1;
                            }

                        if (OMIT_EXPORT_COLUMNS.has(normalizedHeaders[idx] ?? '')) {
                            return;
                        }

                        planilhaColumns.push(uniqueLabel);
                        columnIndexes.push(idx);
                        });

                        const placaIndex = findColumnIndex(normalizedHeaders, ['PLACA']);
                        const renavamIndex = findColumnIndex(normalizedHeaders, ['RENAVAM']);
                        const nomeIndex = findColumnIndex(normalizedHeaders, ['NOME', 'ESTABELECIMENTO', 'LOJA']);

                        planilhaData = [];
                        for (let i = headerRowIndex + 1; i < rows.length; i++) {
                            const row = rows[i];
                            if (!row || row.length === 0) {
                                continue;
                            }

                            const normalizedRow = row.map(value => normalizeHeaderLabel(value));
                            if (isRepeatedHeaderRow(normalizedRow, normalizedHeaders, placaIndex)) {
                                continue;
                            }

                            const raw = {};
                            planilhaColumns.forEach((column, colIdx) => {
                                raw[column] = normalizeCellValue(row[columnIndexes[colIdx]]);
                            });

                            const placaValue = normalizeCellValue(row[placaIndex]).toUpperCase();
                            const renavamValue = renavamIndex >= 0 ? normalizeCellValue(row[renavamIndex]) : '';
                            const nomeValue = nomeIndex >= 0 ? normalizeCellValue(row[nomeIndex]) : '';

                            if (Object.values(raw).every(value => value === '')) {
                                continue;
                            }

                            planilhaData.push({
                                index: planilhaData.length + 1,
                                placa: placaValue,
                                renavam: renavamValue,
                                nome: nomeValue,
                                raw,
                                resultado: '',
                                resultado_detalhe: '',
                                resultado_status: 'pending',
                                status: 'pending',
                                error: ''
                            });
                        }

                        renderTable();
                        showUI();
                    } catch (error) {
                        console.error('Erro ao ler planilha:', error);
                        alert('Erro ao ler o arquivo. Verifique se é um arquivo Excel válido.');
                    }
                };
                reader.readAsArrayBuffer(file);
            }

            function showUI() {
                verificationCard.style.display = 'block';
                resultsCard.style.display = 'block';
                emptyState.style.display = 'none';
                btnIniciar.disabled = false;
                updateDownloadButtons();
            }

            function renderTable() {
                resultsBody.innerHTML = planilhaData.map(row => `
                    <tr data-index="${row.index}" data-status="${row.status}">
                        <td>${row.index}</td>
                        <td><strong>${escapeHtml(row.placa)}</strong></td>
                        <td>${escapeHtml(row.nome) || '—'}</td>
                        <td>${renderResultado(row)}</td>
                        <td>${renderStatus(row)}</td>
                    </tr>
                `).join('');
            }

            function renderResultado(row) {
                if (!row.resultado && !row.resultado_detalhe) {
                    return '—';
                }

                let badgeClass = 'teste-planilha__result-badge--erro';
                if (row.resultado_status === 'liberado') {
                    badgeClass = 'teste-planilha__result-badge--liberado';
                } else if (row.resultado_status === 'nao_liberado') {
                    badgeClass = 'teste-planilha__result-badge--nao-liberado';
                }

                const badgeText = escapeHtml(row.resultado || 'Resultado');
                const detailText = escapeHtml(row.resultado_detalhe || '');

                return `
                    <div class="teste-planilha__result">
                        <span class="teste-planilha__result-badge ${badgeClass}">${badgeText}</span>
                        ${detailText ? `<div class="teste-planilha__result-detail">${detailText}</div>` : ''}
                    </div>
                `;
            }

            function renderStatus(row) {
                switch (row.status) {
                    case 'pending':
                        return '<span class="teste-planilha__status teste-planilha__status--pending">Aguardando</span>';
                    case 'loading':
                        return '<span class="teste-planilha__status teste-planilha__status--loading"><span class="teste-planilha__spinner"></span> Consultando...</span>';
                    case 'success':
                        return '<span class="teste-planilha__status teste-planilha__status--success">Concluído</span>';
                    case 'error':
                        return `<span class="teste-planilha__status teste-planilha__status--error" title="${escapeHtml(row.error)}">Erro</span>`;
                    default:
                        return '';
                }
            }

            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function normalizeCellValue(value) {
                if (value === null || value === undefined) {
                    return '';
                }
                return String(value).trim();
            }

            function normalizeHeaderLabel(value) {
                if (value === null || value === undefined) {
                    return '';
                }
                return String(value)
                    .trim()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-zA-Z0-9]+/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toUpperCase();
            }

            function findHeaderRowIndex(rows) {
                for (let i = 0; i < rows.length; i++) {
                    const normalized = rows[i].map(value => normalizeHeaderLabel(value));
                    if (normalized.includes('PLACA')) {
                        return i;
                    }
                }
                return -1;
            }

            function findColumnIndex(headers, candidates) {
                for (const candidate of candidates) {
                    const index = headers.findIndex(header => header === candidate || header.includes(candidate));
                    if (index >= 0) {
                        return index;
                    }
                }
                return -1;
            }

            function isRepeatedHeaderRow(row, header, placaIndex) {
                if (placaIndex < 0) {
                    return false;
                }

                if (row[placaIndex] !== 'PLACA') {
                    return false;
                }

                let matches = 0;
                for (let i = 0; i < Math.min(row.length, header.length); i++) {
                    if (row[i] && header[i] && row[i] === header[i]) {
                        matches += 1;
                    }
                }

                return matches >= 2;
            }

            function waitForResume() {
                if (!isPaused) {
                    return Promise.resolve();
                }

                return new Promise((resolve) => {
                    pauseResolvers.push(resolve);
                });
            }

            async function fetchCaptchaSolution() {
                const response = await fetch(CAPTCHA_SOLVE_URL, {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    let message;
                    try {
                        const data = await response.json();
                        message = data.message ?? `Erro ao resolver captcha (HTTP ${response.status})`;
                    } catch {
                        message = `Erro ao resolver captcha (HTTP ${response.status})`;
                    }
                    throw new Error(message);
                }

                const payload = await response.json().catch(() => ({}));
                const solution = String(payload.solution || payload.Solution || '').trim().toUpperCase();

                if (!solution) {
                    throw new Error('Captcha automático retornou valor inválido.');
                }

                return solution;
            }

            btnPause.addEventListener('click', () => {
                if (!isProcessing) return;

                isPaused = !isPaused;
                btnPause.textContent = isPaused ? 'Retomar' : 'Pausar';

                if (!isPaused && pauseResolvers.length > 0) {
                    pauseResolvers.forEach(resolve => resolve());
                    pauseResolvers = [];
                }
            });

            // Iniciar consulta
            btnIniciar.addEventListener('click', async () => {
                if (isProcessing) return;

                if (planilhaData.length === 0) {
                    alert('Por favor, carregue uma planilha para iniciar.');
                    return;
                }

                isProcessing = true;
                btnIniciar.disabled = true;
                btnDownloadLiberados.disabled = true;
                btnDownloadGravame.disabled = true;
                progressBar.classList.add('is-visible');
                btnPause.disabled = false;
                isPaused = false;
                btnPause.textContent = 'Pausar';
                pauseResolvers = [];

                const total = planilhaData.length;
                let completed = 0;

                for (let i = 0; i < planilhaData.length; i++) {
                    await waitForResume();
                    const row = planilhaData[i];

                    if (!row.placa) {
                        row.status = 'error';
                        row.error = 'Placa não informada';
                        row.resultado = 'ERRO NA CONSULTA';
                        row.resultado_detalhe = 'PLACA VAZIA';
                        row.resultado_status = 'erro';
                        completed++;
                        updateProgress(completed, total);
                        renderTable();
                        updateDownloadButtons();
                        continue;
                    }

                    // Resolve captcha before sending a consulta request
                    let captchaSolution = '';
                    try {
                        captchaSolution = await fetchCaptchaSolution();
                    } catch (error) {
                        console.error('Erro ao resolver captcha:', error);
                        row.status = 'error';
                        row.error = error.message || 'Erro ao resolver captcha';
                        row.resultado = 'ERRO NA CONSULTA';
                        row.resultado_detalhe = row.error;
                        row.resultado_status = 'erro';
                        completed++;
                        updateProgress(completed, total);
                        renderTable();
                        updateDownloadButtons();
                        continue;
                    }

                    // Update status to loading
                    row.status = 'loading';
                    renderTable();

                    try {
                        const response = await fetch(CONSULTAR_URL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF_TOKEN,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                placa: row.placa,
                                renavam: row.renavam,
                                captcha_response: captchaSolution,
                                debug: true
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            row.status = 'success';
                            row.resultado = result.resultado || '';
                            row.resultado_detalhe = result.resultado_detalhe || '';
                            row.resultado_status = result.resultado_status || 'erro';
                        } else {
                            row.status = 'error';
                            row.error = result.error || 'Erro desconhecido';
                            row.resultado = 'ERRO NA CONSULTA';
                            row.resultado_detalhe = row.error;
                            row.resultado_status = 'erro';
                        }
                    } catch (error) {
                        console.error('Erro na consulta:', error);
                        row.status = 'error';
                        row.error = 'Falha na comunicação';
                        row.resultado = 'ERRO NA CONSULTA';
                        row.resultado_detalhe = row.error;
                        row.resultado_status = 'erro';
                    }

                    completed++;
                    updateProgress(completed, total);
                    renderTable();
                    updateDownloadButtons();

                    // Delay between requests to avoid overload
                    if (i < planilhaData.length - 1) {
                        await sleep(500);
                    }
                }

                isProcessing = false;
                btnIniciar.disabled = false;
                btnPause.disabled = true;
                isPaused = false;
                pauseResolvers = [];
                btnPause.textContent = 'Pausar';
                updateDownloadButtons();
            });

            function updateProgress(completed, total) {
                const percent = Math.round((completed / total) * 100);
                progressFill.style.width = percent + '%';
                progressText.textContent = `${completed}/${total} (${percent}%)`;
            }

            function sleep(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            // Limpar
            btnLimpar.addEventListener('click', () => {
                if (isProcessing) {
                    if (!confirm('Uma consulta está em andamento. Deseja realmente limpar?')) {
                        return;
                    }
                }

                planilhaData = [];
                planilhaColumns = [];
                isProcessing = false;
                fileInput.value = '';
                fileName.style.display = 'none';
                fileName.textContent = '';
                fileDropZone.classList.remove('has-file');
                progressBar.classList.remove('is-visible');
                progressFill.style.width = '0%';
                progressText.textContent = '0%';
                resultsBody.innerHTML = '';
                verificationCard.style.display = 'none';
                resultsCard.style.display = 'none';
                emptyState.style.display = 'block';
                btnIniciar.disabled = true;
                btnDownloadLiberados.disabled = true;
                btnDownloadGravame.disabled = true;
                btnPause.disabled = true;
                isPaused = false;
                pauseResolvers = [];
                btnPause.textContent = 'Pausar';
            });

            function updateDownloadButtons() {
                const liberados = planilhaData.filter(row => row.resultado_status === 'liberado');
                const comGravame = planilhaData.filter(row => row.resultado_status === 'nao_liberado');
                btnDownloadLiberados.disabled = liberados.length === 0;
                btnDownloadGravame.disabled = comGravame.length === 0;
            }

            function resolveDownloadFilename(response, fallbackName) {
                const header = response.headers.get('Content-Disposition') || response.headers.get('content-disposition') || '';
                if (!header) {
                    return fallbackName;
                }

                const utfMatch = header.match(/filename\*=UTF-8''([^;]+)/i);
                if (utfMatch && utfMatch[1]) {
                    try {
                        return decodeURIComponent(utfMatch[1]);
                    } catch {
                        return utfMatch[1];
                    }
                }

                const asciiMatch = header.match(/filename=\"?([^\";]+)\"?/i);
                if (asciiMatch && asciiMatch[1]) {
                    return asciiMatch[1];
                }

                return fallbackName;
            }

            async function downloadPlanilha(tipo, dados) {
                if (dados.length === 0) return;

                try {
                    const response = await fetch(EXPORTAR_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN
                        },
                        body: JSON.stringify({
                            dados,
                            tipo,
                            colunas: planilhaColumns
                        })
                    });

                    if (!response.ok) {
                        throw new Error('Erro ao gerar arquivo');
                    }

                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    const prefix = tipo === 'liberados' ? 'gravame_liberados' : 'gravame_com_gravame';
                    const fallbackName = prefix + '_' + new Date().toISOString().slice(0, 10) + '.xlsx';
                    a.download = resolveDownloadFilename(response, fallbackName);
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                } catch (error) {
                    console.error('Erro ao baixar:', error);
                    alert('Erro ao gerar o arquivo. Tente novamente.');
                }
            }

            btnDownloadLiberados.addEventListener('click', async () => {
                const liberados = planilhaData.filter(row => row.resultado_status === 'liberado');
                const dados = liberados.map(row => ({
                    values: planilhaColumns.map(column => row.raw?.[column] ?? ''),
                    resultado: row.resultado,
                    resultado_detalhe: row.resultado_detalhe
                }));

                await downloadPlanilha('liberados', dados);
            });

            btnDownloadGravame.addEventListener('click', async () => {
                const comGravame = planilhaData.filter(row => row.resultado_status === 'nao_liberado');
                const dados = comGravame.map(row => ({
                    values: planilhaColumns.map(column => row.raw?.[column] ?? ''),
                    resultado: row.resultado,
                    resultado_detalhe: row.resultado_detalhe
                }));

                await downloadPlanilha('com_gravame', dados);
            });
        })();
    
        })();
</script>
        </section>
        <section class="permission-gate hidden" id="permissionGate">
            <div class="permission-gate__card">
                <h2>Acesso não liberado</h2>
                <p>Fale com o administrador para liberar a permissão "Teste Planilha Gravame".</p>
                <a href="/home">Voltar para a Home</a>
            </div>
        </section>
    </main>
</body>
</html>
