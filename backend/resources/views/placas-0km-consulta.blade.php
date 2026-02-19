<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Consulta Placas 0KM - LL Despachante</title>
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
            --error: #EF4444;
            --brand-primary: #0b4ea2;
            --brand-primary-hover: #093f82;
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

        .header {
            background: var(--primary);
            border-radius: 0 0 32px 32px;
            padding: 28px 20px 36px;
            color: var(--white);
        }

        .header-inner {
            max-width: 860px;
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
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 20px 64px;
        }

        .hidden {
            display: none !important;
        }

        .permission-gate {
            display: flex;
            justify-content: center;
            margin-top: 40px;
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

        .placas-page {
            display: grid;
            gap: 22px;
        }

        .placas-page__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .placas-page__header h1 {
            margin: 0;
            font-size: 34px;
            line-height: 1.1;
            color: #11284a;
        }

        .placas-page__header p {
            margin: 8px 0 0;
            color: var(--text-muted);
        }

        .placas-page__back {
            border: 1px solid var(--border);
            border-radius: 12px;
            text-decoration: none;
            color: var(--text-strong);
            background: #fff;
            padding: 10px 14px;
            font-weight: 600;
            font-size: 14px;
        }

        .placas-page__grid {
            display: grid;
            gap: 20px;
        }

        @media (min-width: 980px) {
            .placas-page__grid {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            }
        }

        .placas-card {
            background: var(--surface);
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid #e7edf7;
        }

        .placas-form {
            display: grid;
            gap: 16px;
        }

        .placas-form__field {
            display: grid;
            gap: 6px;
        }

        .placas-form__field label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .placas-form__field input {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 12px 14px;
            font-size: 15px;
            color: var(--text-strong);
            background: #fff;
        }

        .placas-form__field input:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(11, 78, 162, 0.12);
        }

        .placas-form__actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .placas-form__submit {
            border: none;
            border-radius: 12px;
            padding: 12px 22px;
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            background: var(--brand-primary);
            cursor: pointer;
            transition: background 150ms ease, transform 150ms ease;
        }

        .placas-form__submit:hover:not(:disabled) {
            background: var(--brand-primary-hover);
            transform: translateY(-1px);
        }

        .placas-form__submit:disabled {
            opacity: 0.68;
            cursor: not-allowed;
            transform: none;
        }

        .placas-form__status {
            font-size: 14px;
            color: var(--text-muted);
        }

        .placas-form__error {
            display: none;
            border-radius: 12px;
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 14px;
            font-size: 14px;
            white-space: pre-wrap;
        }

        .placas-result {
            display: grid;
            gap: 16px;
        }

        .placas-result[hidden] {
            display: none !important;
        }

        .placas-result__label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .placas-result__section {
            display: grid;
            gap: 8px;
        }

        .placas-result__section strong {
            font-size: 17px;
            color: #23395d;
        }

        .placas-result__text {
            font-size: 15px;
            color: var(--text-muted);
            white-space: pre-wrap;
        }

        .placas-loader {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .placas-loader[hidden] {
            display: none !important;
        }

        .placas-loader__spinner {
            width: 16px;
            height: 16px;
            border-radius: 999px;
            border: 2px solid #d3e0ef;
            border-top-color: var(--brand-primary);
            animation: placas-spin 800ms linear infinite;
        }

        @keyframes placas-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .placas-list {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
        }

        .placas-list__item {
            border-radius: 12px;
            background: var(--surface-muted);
            padding: 10px 12px;
            font-weight: 700;
            color: var(--text-strong);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .placas-list__item::before {
            content: '';
            width: 14px;
            height: 14px;
            border-radius: 999px;
            border: 2px solid #bfd0e6;
            background: #fff;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
@include('components.home.header')

<main class="content-wide">
    <section id="placas0kmApp" class="placas-page hidden">
        <header class="placas-page__header">
            <div>
                <h1>Consulta de Placas 0KM</h1>
                <p>Consulta com acompanhamento em tempo real e resultado no mesmo painel.</p>
            </div>
            <a href="/home" class="placas-page__back">Voltar para a Home</a>
        </header>

        <div class="placas-page__grid">
            <section class="placas-card">
                <form id="placas0kmForm" class="placas-form">
                    <div class="placas-form__field">
                        <label for="cpfCgc">CPF/CNPJ do proprietário</label>
                        <input id="cpfCgc" name="cpf_cgc" type="text" inputmode="numeric" placeholder="Somente números" required>
                    </div>

                    <div class="placas-form__field">
                        <label for="chassi">Chassi</label>
                        <input id="chassi" name="chassi" type="text" placeholder="Ex.: 94DFAAP16TB015294" required>
                    </div>

                    <div class="placas-form__actions">
                        <button id="consultarButton" class="placas-form__submit" type="submit">Consultar</button>
                        <span id="statusText" class="placas-form__status">Preencha CPF/CNPJ e chassi para consultar.</span>
                    </div>

                    <div id="errorBox" class="placas-form__error"></div>
                </form>
            </section>

            <section id="resultCard" class="placas-card placas-result" hidden>
                <span class="placas-result__label">Resultado</span>

                <div class="placas-result__section">
                    <strong>Status da consulta</strong>
                    <div id="queueStatusText" class="placas-result__text">Aguardando envio.</div>
                </div>

                <div class="placas-result__section">
                    <strong>Tentativas</strong>
                    <div id="attemptsText" class="placas-result__text">Aguardando início.</div>
                </div>

                <div class="placas-result__section">
                    <strong>Placas disponíveis</strong>
                    <div id="processingLoader" class="placas-loader" hidden>
                        <span class="placas-loader__spinner" aria-hidden="true"></span>
                        <span id="processingText">Processando consulta...</span>
                    </div>
                    <div id="platesList" class="placas-list"></div>
                    <div id="resultText" class="placas-result__text"></div>
                </div>
            </section>
        </div>
    </section>

    <section id="permissionGate" class="permission-gate hidden">
        <div class="permission-gate__card">
            <h2>Acesso não liberado</h2>
            <p>Fale com o administrador para liberar a permissão "Consulta Placas 0KM".</p>
            <a href="/home">Voltar para a Home</a>
        </div>
    </section>
</main>

<script>
    (function () {
        const API_BASE_URL = window.location.origin;
        const REQUIRED_PERMISSION = 'consulta_placas_0km';
        const ENQUEUE_URL = '{{ url('/api/public/placas-0km/batches') }}';
        const QUEUE_BATCH_SHOW_BASE_URL = '{{ url('/api/public/placas-0km/batches') }}';
        const PUBLIC_API_KEY = @json((string) config('services.public_placas0km.key', ''));
        const POLL_INTERVAL_MS = 2000;
        const SLOW_NOTICE_AFTER_MS = 180000;

        const placas0kmApp = document.getElementById('placas0kmApp');
        const permissionGate = document.getElementById('permissionGate');
        const userInfoEl = document.getElementById('userInfo');
        const form = document.getElementById('placas0kmForm');
        const button = document.getElementById('consultarButton');
        const statusText = document.getElementById('statusText');
        const errorBox = document.getElementById('errorBox');
        const resultCard = document.getElementById('resultCard');
        const queueStatusText = document.getElementById('queueStatusText');
        const attemptsText = document.getElementById('attemptsText');
        const processingLoader = document.getElementById('processingLoader');
        const processingText = document.getElementById('processingText');
        const platesList = document.getElementById('platesList');
        const resultText = document.getElementById('resultText');

        let authToken = null;
        let activeBatchId = null;
        let activeRequestId = null;
        let pollingTimer = null;
        let pollingStartedAt = 0;
        let pollingInFlight = false;

        function getStoredItem(key) {
            return sessionStorage.getItem(key) || localStorage.getItem(key);
        }

        function clearStoredAuth() {
            sessionStorage.removeItem('auth_token');
            localStorage.removeItem('auth_token');
            sessionStorage.removeItem('user');
            localStorage.removeItem('user');
        }

        function parseUser() {
            const raw = getStoredItem('user');
            if (!raw) return null;
            try {
                return JSON.parse(raw);
            } catch (_) {
                return null;
            }
        }

        function normalizeDigits(value) {
            return String(value || '').replace(/\D/g, '');
        }

        function normalizeUpper(value) {
            return String(value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        }

        function toSafeNumber(value) {
            const parsed = Number(value);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function normalizePlate(value) {
            const compact = normalizeUpper(value);
            if (compact.length !== 7) return compact;
            return `${compact.slice(0, 3)}-${compact.slice(3)}`;
        }

        function mergeUniquePlates(...groups) {
            const unique = new Set();
            for (const group of groups) {
                if (!Array.isArray(group)) continue;
                for (const item of group) {
                    const plate = normalizePlate(item);
                    if (plate) unique.add(plate);
                }
            }
            return Array.from(unique);
        }

        function extractRequestPlates(requestRow) {
            const payload = requestRow?.response_payload;
            const ocrPlates = payload?.data?.ocr?.plates;
            const parsedPlates = payload?.data?.placas;
            return mergeUniquePlates(ocrPlates, parsedPlates);
        }

        function extractModalErrorText(requestRow) {
            const payload = requestRow?.response_payload;
            const ocrText = payload?.data?.ocr?.text;
            const responseError = requestRow?.response_error;
            const normalizedOcrText = typeof ocrText === 'string' ? ocrText.trim() : '';
            const normalizedResponseError = typeof responseError === 'string' ? responseError.trim() : '';
            return normalizedResponseError || normalizedOcrText || '';
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
            clearStoredAuth();
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

        function withPublicApiHeaders(baseHeaders = {}) {
            const headers = { ...baseHeaders };
            if (PUBLIC_API_KEY) {
                headers['X-Public-Api-Key'] = PUBLIC_API_KEY;
            }
            return headers;
        }

        function setError(message) {
            if (!message) {
                errorBox.style.display = 'none';
                errorBox.textContent = '';
                return;
            }
            errorBox.textContent = message;
            errorBox.style.display = 'block';
        }

        function setSubmitLoading(loading, text) {
            button.disabled = loading;
            if (typeof text === 'string') {
                statusText.textContent = text;
                return;
            }
            statusText.textContent = loading
                ? 'Enviando consulta, aguarde...'
                : 'Preencha CPF/CNPJ e chassi para consultar.';
        }

        function setProcessingState(loading, text) {
            processingLoader.hidden = !loading;
            if (typeof text === 'string' && text) {
                processingText.textContent = text;
            }
        }

        function renderPlateItems(plates) {
            platesList.innerHTML = '';
            for (const plate of plates) {
                const item = document.createElement('div');
                item.className = 'placas-list__item';
                item.textContent = plate;
                platesList.appendChild(item);
            }
        }

        function stopPolling() {
            if (pollingTimer) {
                clearInterval(pollingTimer);
            }
            pollingTimer = null;
            pollingInFlight = false;
        }

        async function fetchBatchStatus(batchId) {
            const response = await fetch(`${QUEUE_BATCH_SHOW_BASE_URL}/${batchId}`, {
                headers: withPublicApiHeaders({
                    'Accept': 'application/json',
                }),
            });
            const data = await response.json().catch(() => null);
            if (!response.ok || !data?.success) {
                throw new Error(data?.error || data?.message || 'Erro ao consultar status da consulta.');
            }
            return data.data;
        }

        function computeAttemptsLabel(requestRow) {
            const retryInfo = requestRow?.response_payload?.data?.transient_retry || null;
            const hasRetry = Boolean(retryInfo?.triggered);
            if (hasRetry) {
                const attempts = toSafeNumber(retryInfo?.attempts);
                const maxRetries = toSafeNumber(retryInfo?.max_retries);
                return maxRetries > 0 ? `${attempts}/${maxRetries}` : String(attempts);
            }
            const requestAttempts = toSafeNumber(requestRow?.attempts);
            return requestAttempts > 0 ? `${requestAttempts} tentativa(s) de execução` : 'Aguardando início.';
        }

        function updateFromBatchStatus(data) {
            const batch = data?.batch ?? null;
            const requests = Array.isArray(data?.requests) ? data.requests : [];
            const requestRow =
                requests.find((item) => toSafeNumber(item?.id) === activeRequestId) ||
                requests[0] ||
                null;

            if (!activeRequestId && requestRow?.id) {
                activeRequestId = toSafeNumber(requestRow.id);
            }

            const requestStatus = String(requestRow?.status || '');
            const batchStatus = String(batch?.status || '');
            const retryInfo = requestRow?.response_payload?.data?.transient_retry || null;
            const transientMessage = requestRow?.response_payload?.data?.ocr?.transient_message || '';
            const elapsedMs = Date.now() - pollingStartedAt;

            attemptsText.textContent = computeAttemptsLabel(requestRow);

            if (requestStatus === 'running' || requestStatus === 'pending') {
                if (retryInfo?.triggered) {
                    const attempts = toSafeNumber(retryInfo?.attempts);
                    const maxRetries = toSafeNumber(retryInfo?.max_retries);
                    const waitMs = toSafeNumber(retryInfo?.wait_ms);
                    const waitSeconds = waitMs > 0 ? Math.max(1, Math.round(waitMs / 1000)) : 2;
                    const progressLabel = maxRetries > 0 ? `${attempts}/${maxRetries}` : `${attempts}`;

                    queueStatusText.textContent = 'Servidor mais lento que o normal. Fazendo nova tentativa.';
                    setProcessingState(true, `Nova tentativa ${progressLabel}. Aguardando ${waitSeconds}s para reprocessar...`);
                    resultText.textContent = transientMessage
                        ? `Mensagem detectada na tela: ${transientMessage}`
                        : 'Aguardando resposta do sistema...';
                } else if (elapsedMs > SLOW_NOTICE_AFTER_MS) {
                    queueStatusText.textContent = 'A consulta está demorando mais que o normal. Aguarde.';
                    setProcessingState(true, 'Seguimos consultando automaticamente até retornar placas ou erro.');
                    resultText.textContent = 'Não feche esta tela. O processamento continua em andamento.';
                } else {
                    queueStatusText.textContent = `Processando requisição #${activeRequestId || '-'}...`;
                    setProcessingState(true, 'Executando script e analisando resultado...');
                    resultText.textContent = '';
                }

                renderPlateItems([]);
                return false;
            }

            const done = requestStatus === 'succeeded' || requestStatus === 'failed' || batchStatus === 'completed';
            if (!done) {
                queueStatusText.textContent = `Consulta #${activeBatchId || '-'} em processamento...`;
                setProcessingState(true, 'Aguardando processamento...');
                renderPlateItems([]);
                return false;
            }

            setProcessingState(false, '');

            if (requestStatus === 'failed') {
                queueStatusText.textContent = `Falha na requisição #${activeRequestId || '-'}.`;
                resultText.textContent = extractModalErrorText(requestRow) || 'Consulta finalizada com falha.';
                renderPlateItems([]);
                return true;
            }

            const plates = extractRequestPlates(requestRow);
            queueStatusText.textContent = `Requisição #${activeRequestId || '-'} concluída com sucesso.`;
            renderPlateItems(plates);
            resultText.textContent = plates.length
                ? `${plates.length} placa(s) encontrada(s).`
                : 'Nenhuma placa listada para esta consulta.';
            return true;
        }

        async function pollBatchStatus() {
            if (!activeBatchId || pollingInFlight) return;
            pollingInFlight = true;
            try {
                const data = await fetchBatchStatus(activeBatchId);
                setError('');
                const finished = updateFromBatchStatus(data);
                if (finished) {
                    stopPolling();
                    setSubmitLoading(false, 'Consulta finalizada.');
                }
            } catch (error) {
                setError(error?.message || 'Falha ao buscar status. Tentando novamente...');
                queueStatusText.textContent = 'Falha temporária ao atualizar. Vamos tentar novamente automaticamente.';
                setProcessingState(true, 'Reconectando para continuar acompanhamento...');
            } finally {
                pollingInFlight = false;
            }
        }

        function startPolling(batchId, requestId) {
            stopPolling();
            activeBatchId = toSafeNumber(batchId);
            activeRequestId = toSafeNumber(requestId);
            pollingStartedAt = Date.now();
            pollBatchStatus();
            pollingTimer = setInterval(pollBatchStatus, POLL_INTERVAL_MS);
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
                    // noop
                } finally {
                    clearStoredAuth();
                    window.location.href = '/login';
                }
            });

            profileBtn?.addEventListener('click', () => {
                window.location.href = '/perfil';
            });
        }

        function initAuth() {
            authToken = getStoredItem('auth_token');
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
            } catch (_) {
                updateHeaderCredits({ status: 'error', count: 0 });
            }
        }

        async function ensurePermission() {
            let response;
            try {
                response = await fetchWithAuth(`${API_BASE_URL}/api/user/permissions`);
            } catch (_) {
                permissionGate.classList.remove('hidden');
                return false;
            }

            if (!response.ok) {
                permissionGate.classList.remove('hidden');
                return false;
            }

            const data = await response.json().catch(() => ({}));
            const slugs = Array.isArray(data.slugs) ? data.slugs : [];
            const allowed = slugs.includes(REQUIRED_PERMISSION);
            if (!allowed) {
                placas0kmApp.classList.add('hidden');
                permissionGate.classList.remove('hidden');
            }
            return allowed;
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            stopPolling();
            setError('');
            setSubmitLoading(true, 'Enviando consulta, aguarde...');

            const cpfCgc = normalizeDigits(document.getElementById('cpfCgc').value);
            const chassi = normalizeUpper(document.getElementById('chassi').value);
            if (!cpfCgc || ![11, 14].includes(cpfCgc.length)) {
                setSubmitLoading(false);
                setError('Informe um CPF/CNPJ válido.');
                return;
            }
            if (!chassi || chassi.length < 17) {
                setSubmitLoading(false);
                setError('Informe um chassi válido.');
                return;
            }

            const payload = {
                items: [{
                    cpf_cgc: cpfCgc,
                    chassi,
                    numeros: '',
                }],
            };

            try {
                const response = await fetch(ENQUEUE_URL, {
                    method: 'POST',
                    headers: withPublicApiHeaders({
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    }),
                    body: JSON.stringify(payload),
                });
                const data = await response.json().catch(() => null);
                if (!response.ok || !data?.success) {
                    throw new Error(data?.error || data?.message || 'Erro ao enfileirar consulta.');
                }

                const batchId = toSafeNumber(data?.data?.batch_id);
                const requestId = toSafeNumber(data?.data?.request_id);
                if (!batchId) {
                    throw new Error('Resposta inválida ao iniciar consulta.');
                }

                resultCard.hidden = false;
                queueStatusText.textContent = requestId
                    ? `Item enfileirado no batch #${batchId} (req #${requestId}).`
                    : `Item enfileirado no batch #${batchId}.`;
                attemptsText.textContent = 'Aguardando início.';
                resultText.textContent = '';
                renderPlateItems([]);
                setProcessingState(true, 'Aguardando início da execução...');
                setSubmitLoading(false, 'Consulta enviada. Aguardando conclusão...');
                startPolling(batchId, requestId);
            } catch (error) {
                setSubmitLoading(false);
                setError(error?.message || 'Não foi possível iniciar a consulta.');
            }
        });

        (async () => {
            if (!initAuth()) return;
            setupHeaderActions();
            loadMonthlyCredits();

            const allowed = await ensurePermission();
            if (!allowed) return;

            permissionGate.classList.add('hidden');
            placas0kmApp.classList.remove('hidden');
        })();
    })();
</script>
</body>
</html>
