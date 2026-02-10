<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Andamento do processo e-CRV - LL Despachante</title>
    <style>
        :root {
            color-scheme: light;
            --primary: #0047AB;
            --accent: #2F80ED;
            --bg: #F8FAFC;
            --white: #FFFFFF;
            --text-strong: #1E293B;
            --text-muted: #64748B;
            --text-soft: #94A3B8;
            --card: #FFFFFF;
            --card-shadow: 0 18px 36px rgba(15, 23, 42, 0.15);
            --divider: #E4E7EC;
            --error: #EF4444;
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

        .header {
            background: var(--primary);
            border-radius: 0 0 32px 32px;
            padding: 28px 20px 36px;
            color: #fff;
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
            background: #fff;
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
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .icon-button,
        .btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 18px;
            font-weight: 600;
            cursor: pointer;
        }

        .icon-button {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .btn-outline {
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            padding: 10px 16px;
            font-size: 15px;
        }

        .content {
            max-width: 860px;
            margin: 0 auto;
            padding: 32px 20px 64px;
        }

        .ecrv-result-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .ecrv-result-title {
            font-size: 26px;
            font-weight: 800;
        }

        .ecrv-result-origin {
            font-size: 15px;
            color: var(--text-muted);
        }

        .ecrv-result-actions {
            display: flex;
            gap: 10px;
        }

        .ecrv-result-copy-btn,
        .ecrv-result-back-btn {
            border: none;
            border-radius: 18px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .ecrv-result-copy-btn {
            background: #E7EDFF;
            color: var(--primary);
            box-shadow: 0 10px 18px rgba(0, 71, 171, 0.15);
        }

        .ecrv-result-copy-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        .ecrv-result-pdf-btn {
            border-radius: 18px;
            border: none;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #0047AB;
            color: #fff;
            box-shadow: 0 10px 18px rgba(0, 71, 171, 0.2);
        }

        .ecrv-result-pdf-btn:disabled,
        .ecrv-result-pdf-btn.is-loading {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        .ecrv-result-back-btn {
            background: #fff;
            border: 1px solid #E4E7EC;
            color: var(--text-strong);
        }

        .ecrv-status-message {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid var(--divider);
            background: #fff;
            color: var(--text-muted);
            display: none;
        }

        .ecrv-status-show {
            display: block;
        }

        .ecrv-status-error {
            border-color: #FECACA;
            background: #FEF2F2;
            color: #B91C1C;
        }

        .ecrv-result-stack {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .ecrv-summary-card,
        .ecrv-section-card {
            background: #fff;
            border-radius: 28px;
            padding: 24px;
            box-shadow: var(--card-shadow);
        }

        .ecrv-summary-heading {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .ecrv-summary-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background: rgba(0, 71, 171, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ecrv-summary-icon svg {
            width: 30px;
            height: 30px;
            stroke: #0047AB;
        }

        .ecrv-summary-value {
            font-size: 18px;
            font-weight: 700;
        }

        .ecrv-summary-meta {
            font-size: 13px;
            color: var(--text-soft);
            margin-top: 4px;
        }

        .ecrv-summary-grid {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
        }

        .ecrv-summary-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .ecrv-info-label {
            font-size: 12px;
            color: var(--text-soft);
            text-transform: uppercase;
        }

        .ecrv-info-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .ecrv-section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 14px;
            color: var(--text-strong);
        }

        .ecrv-info-row {
            margin-bottom: 12px;
        }

        .ecrv-section-empty {
            font-size: 14px;
            color: var(--text-muted);
            padding: 12px 0;
            text-align: center;
        }

        @media (max-width: 640px) {
            .ecrv-result-heading {
                flex-direction: column;
                align-items: flex-start;
            }

            .ecrv-result-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    @include('components.home.header')

    <main class="content">
        <div class="ecrv-result-heading">
            <div>
                <div class="ecrv-result-title">Andamento do processo e-CRV</div>
                <div class="ecrv-result-origin" id="ecrvFichaSubtitle">—</div>
            </div>
            <div class="ecrv-result-actions">
                <button class="ecrv-result-copy-btn" type="button" id="ecrvCopyJsonBtn" disabled>Copiar JSON</button>
                <button class="ecrv-result-pdf-btn" type="button" id="ecrvPdfBtn" disabled>Gerar PDF</button>
                <button class="ecrv-result-back-btn" type="button" id="ecrvBackBtn">Voltar</button>
            </div>
        </div>
        <div class="ecrv-status-message" id="ecrvStatusMessage" role="status"></div>
        <div class="ecrv-result-stack" id="ecrvResultStack"></div>
    </main>

    <script>
        const API_BASE_URL = window.location.origin;
        function getStoredItem(key) {
            return sessionStorage.getItem(key) || localStorage.getItem(key);
        }

        function clearStoredAuth() {
            sessionStorage.removeItem('auth_token');
            localStorage.removeItem('auth_token');
            sessionStorage.removeItem('user');
            localStorage.removeItem('user');
        }

        let authToken = getStoredItem('auth_token');
        const userInfoEl = document.getElementById('userInfo');
        const statusMessageEl = document.getElementById('ecrvStatusMessage');
        const resultStackEl = document.getElementById('ecrvResultStack');
        const subtitleEl = document.getElementById('ecrvFichaSubtitle');
        const copyButton = document.getElementById('ecrvCopyJsonBtn');
        const backButton = document.getElementById('ecrvBackBtn');
        const storedResult = sessionStorage.getItem('ecrv_result');
        let ecrvState = null;
        let copyFeedbackTimeout = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const pdfButton = document.getElementById('ecrvPdfBtn');
        let ecrvPdfLoading = false;

        function parseUser() {
            const raw = getStoredItem('user');
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

            if (userInfoEl) {
                userInfoEl.textContent = `Usuário: ${name} • ${creditsLabel}`;
            }
        }

        function handleUnauthorized() {
            authToken = null;
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

        function checkAuth() {
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

        function showStatus(message, isError = false) {
            if (!statusMessageEl) return;
            if (!message) {
                statusMessageEl.classList.remove('ecrv-status-show', 'ecrv-status-error');
                statusMessageEl.textContent = '';
                return;
            }
            statusMessageEl.textContent = message;
            statusMessageEl.classList.add('ecrv-status-show');
            statusMessageEl.classList.toggle('ecrv-status-error', isError);
        }

        function setEcrvPdfLoading(isLoading) {
            if (!pdfButton) return;
            ecrvPdfLoading = isLoading;
            pdfButton.disabled = isLoading;
            pdfButton.classList.toggle('is-loading', isLoading);
        }

        async function emitEcrvPdf() {
            if (!pdfButton || ecrvPdfLoading || !ecrvState) {
                return;
            }

            setEcrvPdfLoading(true);

            try {
                const headers = {
                    'Content-Type': 'application/json',
                    'Accept': 'application/pdf',
                };
                if (authToken) {
                    headers['Authorization'] = `Bearer ${authToken}`;
                }
                if (csrfToken) {
                    headers['X-CSRF-TOKEN'] = csrfToken;
                }

                const response = await fetch('/api/ecrv/pdf', {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({
                        placa: ecrvState.placa,
                        numeroFicha: ecrvState.numeroFicha,
                        anoFicha: ecrvState.anoFicha,
                        fichaPayload: ecrvState.fichaPayload || {},
                        andamentoPayload: ecrvState.andamentoPayload || {},
                    }),
                });

                if (!response.ok) {
                    let message = 'Não foi possível gerar o PDF.';
                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        const data = await response.json().catch(() => ({}));
                        message = data.message || message;
                    } else {
                        const text = await response.text();
                        if (text) {
                            message = text;
                        }
                    }
                    throw new Error(message);
                }

                const blob = await response.blob();
                const seed = `${ecrvState.placa || 'consulta'}_${ecrvState.numeroFicha || ''}_${ecrvState.anoFicha || ''}`;
                const sanitized = seed.replace(/[^A-Za-z0-9]/g, '');
                const filename = `processo_ecrv_${sanitized || 'consulta'}.pdf`;
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            } catch (error) {
                alert(error.message || 'Não foi possível gerar o PDF.');
            } finally {
                setEcrvPdfLoading(false);
            }
        }

        function formatDisplayValue(value) {
            if (value == null) {
                return '—';
            }
            if (typeof value === 'string') {
                const trimmed = value.trim();
                return trimmed === '' ? '—' : trimmed;
            }
            return value.toString();
        }

        function formatLabel(key) {
            return key
                .toString()
                .split(/[_\s]+/)
                .filter(Boolean)
                .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
                .join(' ');
        }

        function createSummaryItem(label, value) {
            return `
                <div class="ecrv-summary-item">
                    <div class="ecrv-info-label">${label}</div>
                    <div class="ecrv-info-value">${value}</div>
                </div>
            `;
        }

        function buildInfoRows(source, overrides = {}) {
            if (!source || typeof source !== 'object') {
                return '';
            }
            const rows = [];
            Object.entries(source).forEach(([key, value]) => {
                const text = formatDisplayValue(value);
                if (text === '—') return;
                rows.push(`
                    <div class="ecrv-info-row">
                        <div class="ecrv-info-label">${overrides[key] || formatLabel(key)}</div>
                        <div class="ecrv-info-value">${text}</div>
                    </div>
                `);
            });
            return rows.join('');
        }

        function buildStructuredRows(source, fields) {
            if (!source || typeof source !== 'object') {
                return '';
            }
            const html = [];
            fields.forEach(([key, label]) => {
                const value = formatDisplayValue(source[key]);
                if (value === '—') return;
                html.push(`
                    <div class="ecrv-info-row">
                        <div class="ecrv-info-label">${label}</div>
                        <div class="ecrv-info-value">${value}</div>
                    </div>
                `);
            });
            return html.join('');
        }

        function renderEcrvResult() {
            if (!storedResult) {
                showStatus('Nenhum resultado disponível. Realize a pesquisa e-CRV no painel.', true);
                resultStackEl.innerHTML = '';
                copyButton.disabled = true;
                if (pdfButton) pdfButton.disabled = true;
                ecrvState = null;
                return;
            }

            let state;
            try {
                state = JSON.parse(storedResult);
            } catch (error) {
                showStatus('Dados da pesquisa corrompidos. Execute uma nova consulta.', true);
                resultStackEl.innerHTML = '';
                copyButton.disabled = true;
                if (pdfButton) pdfButton.disabled = true;
                ecrvState = null;
                return;
            }

            ecrvState = state;

            const placa = formatDisplayValue(state.placa);
            const numeroFicha = formatDisplayValue(state.numeroFicha);
            const anoFicha = formatDisplayValue(state.anoFicha);
            subtitleEl.textContent = `${placa !== '—' ? `Placa ${placa}` : 'Placa não informada'} • Ficha ${numeroFicha} / ${anoFicha}`;

            const fichaPayload = state.fichaPayload || {};
            const normalizedFicha = fichaPayload.payload?.normalized?.dados_da_ficha_cadastral || {};

            const andamentoPayload = state.andamentoPayload || {};
            const andamentoNormalized = andamentoPayload.payload?.normalized || {};
            const andamentoInfo = andamentoNormalized.andamento_do_processo || {};
            const datasInfo = andamentoNormalized.datas || {};
            const anexosInfo = andamentoNormalized.documentos_anexos || {};

            const statusValue = formatDisplayValue(andamentoInfo.status_registro || andamentoInfo.status);
            const retornoValue = formatDisplayValue(andamentoInfo.retorno_consistencia);

            const summaryHtml = `
                <div class="ecrv-summary-card">
                    <div class="ecrv-summary-heading">
                        <div class="ecrv-summary-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7h18M3 12h18M3 17h18"></path>
                                <path d="M14 11v10"></path>
                                <path d="M10 11v10"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="ecrv-summary-value">${statusValue}</div>
                            <div class="ecrv-summary-meta">${retornoValue === '—' ? 'Retorno não informado' : `Retorno: ${retornoValue}`}</div>
                        </div>
                    </div>
                    <div class="ecrv-summary-grid">
                        ${createSummaryItem('Placa', placa)}
                        ${createSummaryItem('Número da ficha', numeroFicha)}
                        ${createSummaryItem('Ano da ficha', anoFicha)}
                        ${createSummaryItem('Status registrado', statusValue)}
                    </div>
                </div>
            `;

            const fichaFields = [
                ['renavam', 'RENAVAM'],
                ['chassi', 'Chassi'],
                ['marca_modelo', 'Marca / modelo'],
                ['categoria', 'Categoria'],
                ['procedencia', 'Procedência'],
                ['combustivel', 'Combustível'],
                ['municipio', 'Município'],
                ['ano_fabricacao', 'Ano fabricação'],
                ['ano_modelo', 'Ano modelo'],
            ];

            const fichaRows = buildStructuredRows(normalizedFicha, fichaFields);
            const fichaSection = `
                <div class="ecrv-section-card">
                    <div class="ecrv-section-title">Ficha cadastral</div>
                    ${fichaRows || '<div class="ecrv-section-empty">Nenhuma informação da ficha disponível.</div>'}
                </div>
            `;

            const andamentoRows = buildInfoRows(andamentoInfo);
            const andamentoSection = `
                <div class="ecrv-section-card">
                    <div class="ecrv-section-title">Andamento do processo</div>
                    ${andamentoRows || '<div class="ecrv-section-empty">Nenhum dado de andamento disponível.</div>'}
                </div>
            `;

            const datasRows = buildInfoRows(datasInfo);
            const datasSection = `
                <div class="ecrv-section-card">
                    <div class="ecrv-section-title">Datas</div>
                    ${datasRows || '<div class="ecrv-section-empty">Nenhuma data registrada.</div>'}
                </div>
            `;

            const anexosRows = buildInfoRows(anexosInfo);
            const anexosSection = `
                <div class="ecrv-section-card">
                    <div class="ecrv-section-title">Documentos anexos</div>
                    ${anexosRows || '<div class="ecrv-section-empty">Nenhum documento anexado.</div>'}
                </div>
            `;

            resultStackEl.innerHTML = summaryHtml + fichaSection + andamentoSection + datasSection + anexosSection;
            showStatus('');
            copyButton.disabled = false;
            if (pdfButton) {
                pdfButton.disabled = false;
                pdfButton.classList.remove('is-loading');
            }
        }

        copyButton.addEventListener('click', async () => {
            if (!storedResult) {
                return;
            }
            try {
                await navigator.clipboard.writeText(storedResult);
                copyButton.disabled = true;
                copyButton.textContent = 'Copiado!';
                if (copyFeedbackTimeout) {
                    clearTimeout(copyFeedbackTimeout);
                }
                copyFeedbackTimeout = setTimeout(() => {
                    copyButton.textContent = 'Copiar JSON';
                    copyButton.disabled = false;
                }, 1800);
            } catch (error) {
                alert('Não foi possível copiar o resultado.');
            }
        });

        if (pdfButton) {
            pdfButton.addEventListener('click', emitEcrvPdf);
        }

        backButton.addEventListener('click', () => {
            window.location.href = '/home';
        });

        document.getElementById('profileBtn').addEventListener('click', () => {
            window.location.href = '/perfil';
        });

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
                authToken = null;
                clearStoredAuth();
                window.location.href = '/login';
            }
        });

        if (!checkAuth()) {
            // checkAuth already redirected
        } else {
            loadMonthlyCredits();
            renderEcrvResult();
        }
    </script>
</body>
</html>
