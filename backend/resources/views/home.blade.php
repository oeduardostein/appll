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

        .be-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 900;
        }

        .be-overlay.show {
            display: flex;
        }

        .be-dialog {
            width: min(420px, 92vw);
            background: #ECECF4;
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.2);
        }

        .be-dialog-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .be-dialog-title {
            font-size: 20px;
            font-weight: 700;
            color: #1E293B;
        }

        .be-dialog-close {
            width: 32px;
            height: 32px;
            border: none;
            background: none;
            font-size: 24px;
            line-height: 1;
            color: #64748B;
            cursor: pointer;
        }

        .be-dialog-body {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .be-captcha-box {
            background: #FFFFFF;
            border-radius: 18px;
            border: 1px solid #E2E8F0;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .be-captcha-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 600;
            color: #1E293B;
        }

        .be-captcha-refresh {
            border: none;
            background: none;
            color: var(--accent);
            font-weight: 600;
            cursor: pointer;
        }

        .be-captcha-image {
            position: relative;
            min-height: 88px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #F8FAFC;
            border-radius: 14px;
            border: 1px dashed #CBD5F5;
            overflow: hidden;
        }

        .be-captcha-image img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .be-captcha-loading {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--text-soft);
        }

        .be-input {
            width: 100%;
            border-radius: 20px;
            border: 1px solid #E2E8F0;
            padding: 14px 16px;
            font-size: 16px;
            background: #FFFFFF;
            color: #1E293B;
        }

        .be-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(0, 71, 171, 0.15);
        }

        .be-dialog-error {
            color: var(--error);
            font-size: 13px;
            min-height: 16px;
        }

        .be-dialog-submit {
            width: 100%;
            border: none;
            border-radius: 18px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            background: var(--primary);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .be-dialog-submit:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .be-dialog-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .be-dialog-submit .be-btn-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }

        .be-dialog-submit.loading .be-btn-text {
            display: none;
        }

        .be-dialog-submit.loading .be-btn-spinner {
            display: inline-block;
        }

        .be-dialog-cancel {
            border: none;
            background: none;
            font-size: 16px;
            font-weight: 600;
            color: var(--accent);
            cursor: pointer;
            align-self: center;
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
    @include('components.home.header')

    <main class="content">
        @include('components.home.actions')
        @include('components.home.disclaimer')
        @include('components.home.recent')
    </main>

    @include('components.home.base-estadual-modal')
    @include('components.home.base-estadual-captcha-modal')

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

        const baseQueryOverlay = document.getElementById('baseQueryOverlay');
        const baseQueryClose = document.getElementById('baseQueryClose');
        const baseCancelBtn = document.getElementById('baseCancelBtn');
        const basePlateInput = document.getElementById('basePlateInput');
        const basePlateError = document.getElementById('basePlateError');
        const baseConsultBtn = document.getElementById('baseConsultBtn');

        const baseCaptchaOverlay = document.getElementById('baseCaptchaOverlay');
        const baseCaptchaClose = document.getElementById('baseCaptchaClose');
        const baseCaptchaCancel = document.getElementById('baseCaptchaCancel');
        const baseCaptchaRefresh = document.getElementById('baseCaptchaRefresh');
        const baseCaptchaPlate = document.getElementById('baseCaptchaPlate');
        const baseCaptchaInput = document.getElementById('baseCaptchaInput');
        const baseCaptchaImage = document.getElementById('baseCaptchaImage');
        const baseCaptchaLoading = document.getElementById('baseCaptchaLoading');
        const baseCaptchaError = document.getElementById('baseCaptchaError');
        const baseCaptchaSubmit = document.getElementById('baseCaptchaSubmit');

        const oldPlatePattern = /^[A-Z]{3}[0-9]{4}$/;
        const mercosurPlatePattern = /^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/;

        function normalizePlate(value) {
            return value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        }

        function isValidPlate(value) {
            const normalized = normalizePlate(value);
            if (normalized.length !== 7) {
                return false;
            }
            return oldPlatePattern.test(normalized) || mercosurPlatePattern.test(normalized);
        }

        function openBaseEstadualModal() {
            basePlateInput.value = '';
            basePlateError.textContent = '';
            baseQueryOverlay.classList.remove('hidden');
            baseQueryOverlay.classList.add('show');
            baseQueryOverlay.setAttribute('aria-hidden', 'false');
            setTimeout(() => basePlateInput.focus(), 0);
        }

        function closeBaseEstadualModal() {
            baseQueryOverlay.classList.remove('show');
            baseQueryOverlay.classList.add('hidden');
            baseQueryOverlay.setAttribute('aria-hidden', 'true');
        }

        function openBaseCaptchaModal(placa, message = '') {
            baseCaptchaPlate.value = placa;
            baseCaptchaInput.value = '';
            baseCaptchaError.textContent = message;
            baseCaptchaOverlay.classList.remove('hidden');
            baseCaptchaOverlay.classList.add('show');
            baseCaptchaOverlay.setAttribute('aria-hidden', 'false');
            loadBaseCaptchaImage();
            setTimeout(() => baseCaptchaInput.focus(), 0);
        }

        function closeBaseCaptchaModal() {
            baseCaptchaOverlay.classList.remove('show');
            baseCaptchaOverlay.classList.add('hidden');
            baseCaptchaOverlay.setAttribute('aria-hidden', 'true');
            clearCaptchaImage();
        }

        function setBaseConsultLoading(isLoading) {
            baseConsultBtn.disabled = isLoading;
            baseConsultBtn.classList.toggle('loading', isLoading);
        }

        function setBaseCaptchaLoading(isLoading) {
            baseCaptchaSubmit.disabled = isLoading;
            baseCaptchaSubmit.classList.toggle('loading', isLoading);
        }

        async function solveBaseCaptcha() {
            const response = await fetch(`${API_BASE_URL}/api/captcha/solve`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Não foi possível resolver o captcha automaticamente.');
            }
            const data = await response.json();
            const solution = data.solution ? String(data.solution).trim() : '';
            if (!solution) {
                throw new Error('Resposta inválida ao resolver o captcha.');
            }
            return solution.toUpperCase();
        }

        function clearCaptchaImage() {
            const currentUrl = baseCaptchaImage.dataset.objectUrl;
            if (currentUrl) {
                URL.revokeObjectURL(currentUrl);
                delete baseCaptchaImage.dataset.objectUrl;
            }
            baseCaptchaImage.src = '';
        }

        async function loadBaseCaptchaImage() {
            baseCaptchaError.textContent = '';
            baseCaptchaLoading.classList.remove('hidden');
            baseCaptchaImage.classList.add('hidden');
            clearCaptchaImage();

            let hasImage = false;

            try {
                const response = await fetch(`${API_BASE_URL}/api/captcha`, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Não foi possível carregar o captcha.');
                }
                const blob = await response.blob();
                const objectUrl = URL.createObjectURL(blob);
                baseCaptchaImage.src = objectUrl;
                baseCaptchaImage.dataset.objectUrl = objectUrl;
                hasImage = true;
            } catch (error) {
                baseCaptchaError.textContent = error.message || 'Não foi possível carregar o captcha.';
            } finally {
                baseCaptchaLoading.classList.add('hidden');
                baseCaptchaImage.classList.toggle('hidden', !hasImage);
            }
        }

        async function fetchBaseEstadual(placa, captcha) {
            const params = new URLSearchParams({
                placa: placa,
                renavam: '',
                captcha: captcha,
            });

            const response = await fetch(`${API_BASE_URL}/api/base-estadual?${params}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Erro ao consultar base estadual.' }));
                throw new Error(errorData.message || 'Erro ao consultar base estadual.');
            }

            return await response.json();
        }

        function redirectToBaseEstadualResult(result) {
            sessionStorage.setItem('base_estadual_result', JSON.stringify(result));
            window.location.href = '/resultado-base-estadual';
        }

        async function registerBaseEstadualPesquisa(placa) {
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
                        renavam: null,
                    }),
                });
            } catch (error) {
                console.error('Erro ao registrar pesquisa:', error);
            }
        }

        async function performBaseEstadualSearch() {
            const placa = normalizePlate(basePlateInput.value);
            if (!placa) {
                basePlateError.textContent = 'Informe a placa do veículo.';
                return;
            }
            if (!isValidPlate(placa)) {
                basePlateError.textContent = 'Placa inválida.';
                return;
            }

            basePlateError.textContent = '';
            setBaseConsultLoading(true);

            try {
                let captcha;
                try {
                    captcha = await solveBaseCaptcha();
                } catch (captchaError) {
                    closeBaseEstadualModal();
                    openBaseCaptchaModal(placa, 'Captcha automático indisponível. Digite o captcha manualmente.');
                    return;
                }

                const result = await fetchBaseEstadual(placa, captcha);
                await registerBaseEstadualPesquisa(placa);
                closeBaseEstadualModal();
                redirectToBaseEstadualResult(result);
            } catch (error) {
                const message = error.message || 'Não foi possível consultar a base estadual.';
                if (message.toLowerCase().includes('captcha')) {
                    closeBaseEstadualModal();
                    openBaseCaptchaModal(placa, 'Captcha automático falhou. Digite o captcha manualmente.');
                    return;
                }
                basePlateError.textContent = message;
            } finally {
                setBaseConsultLoading(false);
            }
        }

        async function performBaseCaptchaSearch() {
            const placa = normalizePlate(baseCaptchaPlate.value);
            const captcha = baseCaptchaInput.value.trim().toUpperCase();

            if (!captcha) {
                baseCaptchaError.textContent = 'Informe o captcha.';
                return;
            }

            baseCaptchaError.textContent = '';
            setBaseCaptchaLoading(true);

            try {
                const result = await fetchBaseEstadual(placa, captcha);
                await registerBaseEstadualPesquisa(placa);
                closeBaseCaptchaModal();
                redirectToBaseEstadualResult(result);
            } catch (error) {
                baseCaptchaError.textContent = error.message || 'Não foi possível consultar a base estadual.';
                loadBaseCaptchaImage();
            } finally {
                setBaseCaptchaLoading(false);
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

            document.querySelectorAll('[data-action="base-estadual"]').forEach((item) => {
                item.addEventListener('click', () => {
                    openBaseEstadualModal();
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
            window.location.href = '/perfil';
        });

        document.getElementById('openPortalBtn').addEventListener('click', function() {
            window.open('https://www.e-crvsp.sp.gov.br/', '_blank', 'noopener');
        });

        baseQueryClose.addEventListener('click', closeBaseEstadualModal);
        baseCancelBtn.addEventListener('click', closeBaseEstadualModal);
        baseQueryOverlay.addEventListener('click', (event) => {
            if (event.target === baseQueryOverlay) {
                closeBaseEstadualModal();
            }
        });
        basePlateInput.addEventListener('input', () => {
            basePlateInput.value = normalizePlate(basePlateInput.value);
            basePlateError.textContent = '';
        });
        basePlateInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                performBaseEstadualSearch();
            }
        });
        baseConsultBtn.addEventListener('click', performBaseEstadualSearch);

        baseCaptchaClose.addEventListener('click', closeBaseCaptchaModal);
        baseCaptchaCancel.addEventListener('click', closeBaseCaptchaModal);
        baseCaptchaRefresh.addEventListener('click', loadBaseCaptchaImage);
        baseCaptchaOverlay.addEventListener('click', (event) => {
            if (event.target === baseCaptchaOverlay) {
                closeBaseCaptchaModal();
            }
        });
        baseCaptchaInput.addEventListener('input', () => {
            baseCaptchaInput.value = baseCaptchaInput.value.replace(/\s/g, '').toUpperCase();
            baseCaptchaError.textContent = '';
        });
        baseCaptchaInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                performBaseCaptchaSearch();
            }
        });
        baseCaptchaSubmit.addEventListener('click', performBaseCaptchaSearch);

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }
            if (!baseCaptchaOverlay.classList.contains('hidden')) {
                closeBaseCaptchaModal();
                return;
            }
            if (!baseQueryOverlay.classList.contains('hidden')) {
                closeBaseEstadualModal();
            }
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
