<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Resultado Bloqueios Ativos - LL Despachante</title>
    <style>
        :root {
            color-scheme: light;
            --primary: #0047AB;
            --primary-dark: #0B3E98;
            --bg: #F8FAFC;
            --card: #E7EDFF;
            --divider: #E4E7EC;
            --text-strong: #1E293B;
            --text-muted: #64748B;
            --text-soft: #667085;
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

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
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
            color: #fff;
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
            color: #fff;
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
            color: #fff;
        }

        .bloqueios-result {
            max-width: 860px;
            margin: 0 auto;
            padding: 32px 20px 64px;
        }

        .bloqueios-result-inner {
            max-width: 720px;
            margin: 0 auto;
        }

        .bloqueios-result-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .bloqueios-result-title {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-strong);
        }

        .bloqueios-result-origin {
            font-size: 15px;
            color: var(--text-muted);
            font-weight: 600;
            margin-top: 4px;
        }

        .bloqueios-result-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .bloqueios-result-copy-btn,
        .bloqueios-result-back-btn {
            border-radius: 18px;
            border: none;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .bloqueios-result-copy-btn {
            background: #E7EDFF;
            color: var(--primary);
            box-shadow: 0 10px 16px rgba(0, 71, 171, 0.12);
        }

        .bloqueios-result-copy-btn:hover:not(:disabled) {
            transform: translateY(-1px);
        }

        .bloqueios-result-copy-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        .bloqueios-result-pdf-btn {
            border-radius: 18px;
            border: none;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #0047AB;
            color: #fff;
            box-shadow: 0 10px 16px rgba(0, 71, 171, 0.2);
        }

        .bloqueios-result-pdf-btn:disabled,
        .bloqueios-result-pdf-btn.is-loading {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        .bloqueios-result-back-btn {
            background: #fff;
            border: 1px solid #E4E7EC;
            color: var(--text-strong);
        }

        .bloqueios-result-back-btn:hover {
            transform: translateY(-1px);
        }

        .bloqueios-status-message {
            border: 1px solid var(--divider);
            background: #fff;
            border-radius: 16px;
            padding: 14px 16px;
            margin-bottom: 24px;
            text-align: center;
            color: var(--text-muted);
            display: none;
        }

        .bloqueios-status-message.bloqueios-status-show {
            display: block;
        }

        .bloqueios-status-message.bloqueios-status-error {
            border-color: #FECACA;
            background: #FEF2F2;
            color: #B91C1C;
        }

        .bloqueios-result-stack {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .bloqueios-summary-card {
            background: #fff;
            border-radius: 28px;
            padding: 24px;
            box-shadow: 0 20px 38px rgba(15, 23, 42, 0.15);
        }

        .bloqueios-summary-heading {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .bloqueios-summary-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background: rgba(0, 71, 171, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bloqueios-summary-icon svg {
            width: 32px;
            height: 32px;
            fill: none;
            stroke: #0047AB;
            stroke-width: 1.5;
        }

        .bloqueios-summary-title {
            font-size: 20px;
            font-weight: 800;
        }

        .bloqueios-summary-subtitle {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .bloqueios-summary-meta {
            font-size: 13px;
            color: var(--text-soft);
            margin-top: 2px;
        }

        .bloqueios-summary-grid {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px 18px;
        }

        .bloqueios-summary-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .bloqueios-summary-item .bloqueios-info-label {
            font-size: 12px;
            color: var(--text-soft);
            text-transform: uppercase;
        }

        .bloqueios-summary-item .bloqueios-info-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-strong);
        }

        .bloqueios-section-card {
            background: #fff;
            border-radius: 24px;
            padding: 20px;
            border: 1px solid rgba(14, 23, 42, 0.08);
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
        }

        .bloqueios-section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-strong);
            margin-bottom: 12px;
        }

        .bloqueios-section-subtitle {
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            margin-top: 12px;
            margin-bottom: 6px;
        }

        .bloqueios-info-row {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 12px;
        }

        .bloqueios-info-row:last-child {
            margin-bottom: 0;
        }

        .bloqueios-info-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-soft);
            text-transform: uppercase;
        }

        .bloqueios-info-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .bloqueios-renajud-card {
            background: #F6F7FB;
            border-radius: 18px;
            padding: 16px;
            border: 1px solid rgba(0, 71, 171, 0.12);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .bloqueios-renajud-badge {
            font-size: 12px;
            font-weight: 700;
            color: #0047AB;
            letter-spacing: 0.02em;
        }

        .bloqueios-renajud-title {
            font-size: 16px;
            font-weight: 700;
        }

        .bloqueios-renajud-detail {
            display: flex;
            flex-direction: column;
            gap: 4px;
            background: #fff;
            border-radius: 12px;
            padding: 12px;
        }

        .bloqueios-renajud-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
        }

        .bloqueios-renajud-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .bloqueios-renajud-meta {
            font-size: 13px;
            color: var(--text-muted);
        }

        .bloqueios-renajud-info {
            font-size: 13px;
            color: var(--text-muted);
        }

        .bloqueios-section-empty {
            font-size: 14px;
            color: var(--text-muted);
            text-align: center;
            padding: 12px 0;
        }

        @media (max-width: 640px) {
            .bloqueios-result-heading {
                flex-direction: column;
                align-items: flex-start;
            }

            .bloqueios-result-actions {
                width: 100%;
                justify-content: space-between;
            }

            .bloqueios-result-copy-btn,
            .bloqueios-result-back-btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    @include('components.home.header')

    <main class="bloqueios-result">
        <div class="bloqueios-result-inner">
            <header class="bloqueios-result-heading">
                <div>
                    <div class="bloqueios-result-title">Bloqueios ativos</div>
                    <div class="bloqueios-result-origin" id="bloqueiosOriginText">Origem: —</div>
                </div>
                <div class="bloqueios-result-actions">
                <button class="bloqueios-result-copy-btn" type="button" id="bloqueiosCopyJsonBtn" disabled>Copiar JSON</button>
                <button class="bloqueios-result-pdf-btn" type="button" id="bloqueiosPdfBtn" disabled>Gerar PDF</button>
                <button class="bloqueios-result-back-btn" type="button" id="bloqueiosBackBtn">Voltar</button>
            </div>
        </header>
            <div class="bloqueios-status-message" id="bloqueiosStatusMessage" role="status"></div>
            <div class="bloqueios-result-stack" id="bloqueiosResultStack"></div>
        </div>
    </main>

    <script>
        const API_BASE_URL = window.location.origin;
        let authToken = localStorage.getItem('auth_token');
        const userInfoEl = document.getElementById('userInfo');
        const statusMessageEl = document.getElementById('bloqueiosStatusMessage');
        const resultStackEl = document.getElementById('bloqueiosResultStack');
        const originTextEl = document.getElementById('bloqueiosOriginText');
        const copyButton = document.getElementById('bloqueiosCopyJsonBtn');
        const backButton = document.getElementById('bloqueiosBackBtn');
        const storedResult = sessionStorage.getItem('bloqueios_ativos_result');
        let bloqueiosState = null;
        let copyFeedbackTimeout = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const pdfButton = document.getElementById('bloqueiosPdfBtn');
        let bloqueiosPdfLoading = false;

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

            if (userInfoEl) {
                userInfoEl.textContent = `Usuário: ${name} • ${creditsLabel}`;
            }
        }

        function handleUnauthorized() {
            authToken = null;
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
                statusMessageEl.classList.remove('bloqueios-status-show', 'bloqueios-status-error');
                statusMessageEl.textContent = '';
                return;
            }
            statusMessageEl.textContent = message;
            statusMessageEl.classList.add('bloqueios-status-show');
            statusMessageEl.classList.toggle('bloqueios-status-error', isError);
        }

        function setBloqueiosPdfLoading(isLoading) {
            if (!pdfButton) return;
            bloqueiosPdfLoading = isLoading;
            pdfButton.disabled = isLoading;
            pdfButton.classList.toggle('is-loading', isLoading);
        }

        async function emitBloqueiosPdf() {
            if (!pdfButton || bloqueiosPdfLoading || !bloqueiosState || !bloqueiosState.payload) {
                return;
            }

            setBloqueiosPdfLoading(true);

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

                const response = await fetch('/api/bloqueios/pdf', {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({
                        payload: bloqueiosState.payload,
                        origin: bloqueiosState.origin,
                        chassi: bloqueiosState.chassi,
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
                const placa = (bloqueiosState.payload?.consulta?.placa || bloqueiosState.chassi || bloqueiosState.origin || 'consulta')
                    .toString()
                    .replace(/[^A-Za-z0-9]/g, '');
                const filename = `pesquisa_bloqueios_${placa || 'consulta'}.pdf`;
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
                setBloqueiosPdfLoading(false);
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

        function createSummaryItem(label, value) {
            return `
                <div class="bloqueios-summary-item">
                    <div class="bloqueios-info-label">${label}</div>
                    <div class="bloqueios-info-value">${value}</div>
                </div>
            `;
        }

        function combineNumberYear(numero, ano) {
            const formattedNumero = formatDisplayValue(numero);
            const formattedAno = formatDisplayValue(ano);
            if (formattedNumero === '—' && formattedAno === '—') {
                return null;
            }
            if (formattedNumero !== '—' && formattedAno !== '—') {
                return `${formattedNumero}/${formattedAno}`;
            }
            return formattedNumero !== '—' ? formattedNumero : formattedAno;
        }

        function renderRenajudEntry(entry) {
            const dataInclusao = formatDisplayValue(entry.data_inclusao);
            const horaInclusao = formatDisplayValue(entry.hora_inclusao);
            const hasData = dataInclusao !== '—';
            const hasHora = horaInclusao !== '—';
            const dataHora = hasData ? (hasHora ? `${dataInclusao} às ${horaInclusao}` : dataInclusao) : null;
            const tipoRestricao = formatDisplayValue(entry.tipo_bloqueio || entry.tipo_restricao_judicial);
            const motivoBloqueio = formatDisplayValue(entry.motivo_bloqueio);
            const codigoTribunal = formatDisplayValue(entry.codigo_tribunal);
            const codigoOrgao = formatDisplayValue(entry.codigo_orgao_judicial);
            const nomeOrgao = formatDisplayValue(entry.nome_orgao_judicial);
            const municipioBloqueio = formatDisplayValue(entry.municipio_bloqueio);
            const protocolo = combineNumberYear(entry.numero_protocolo, entry.ano_protocolo);
            const oficio = combineNumberYear(entry.numero_oficio, entry.ano_oficio);
            const processo = combineNumberYear(entry.numero_processo, entry.ano_processo);

            const badge = codigoTribunal !== '—' ? `RENAJUD • ${codigoTribunal}` : 'RENAJUD';

            let html = `
                <div class="bloqueios-renajud-card">
                    <div class="bloqueios-renajud-badge">${badge}</div>
                    <div class="bloqueios-renajud-title">${tipoRestricao}</div>
            `;

            if (motivoBloqueio !== '—') {
                html += `
                    <div class="bloqueios-renajud-detail">
                        <div class="bloqueios-renajud-label">Motivo do bloqueio</div>
                        <div class="bloqueios-renajud-value">${motivoBloqueio}</div>
                    </div>
                `;
            }

            if (dataHora) {
                html += `<div class="bloqueios-renajud-meta">${dataHora}</div>`;
            }

            if (nomeOrgao !== '—') {
                html += `<div class="bloqueios-renajud-info">${nomeOrgao}</div>`;
            }
            if (codigoOrgao !== '—') {
                html += `<div class="bloqueios-renajud-info">Órgão judicial: ${codigoOrgao}</div>`;
            }
            if (municipioBloqueio !== '—') {
                html += `<div class="bloqueios-renajud-info">Município do bloqueio: ${municipioBloqueio}</div>`;
            }
            if (protocolo) {
                html += `<div class="bloqueios-renajud-info">Protocolo: ${protocolo}</div>`;
            }
            if (oficio) {
                html += `<div class="bloqueios-renajud-info">Ofício: ${oficio}</div>`;
            }
            if (processo) {
                html += `<div class="bloqueios-renajud-info">Número do processo: ${processo}</div>`;
            }

            html += '</div>';
            return html;
        }

        function renderBloqueios(state) {
            bloqueiosState = state;
            const payload = state.payload;
            if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
                showStatus('Resposta inválida da pesquisa.', true);
                resultStackEl.innerHTML = '';
                copyButton.disabled = true;
                if (pdfButton) pdfButton.disabled = true;
                return;
            }

            if (!payload.consulta || !payload.fonte) {
                const message = payload.message || 'Não foi possível processar o resultado.';
                showStatus(message, true);
                resultStackEl.innerHTML = '';
                copyButton.disabled = true;
                if (pdfButton) pdfButton.disabled = true;
                return;
            }

            const consulta = payload.consulta || {};
            const quantidade = consulta.quantidade || {};
            const fonte = payload.fonte || {};
            const renajud = Array.isArray(payload.renajud) ? payload.renajud : [];

            const placa = formatDisplayValue(consulta.placa);
            const municipioPlaca = formatDisplayValue(consulta.municipio_placa);
            const chassi = formatDisplayValue(consulta.chassi);
            const ocorrenciasEncontradas = formatDisplayValue(quantidade.ocorrencias_encontradas);
            const ocorrenciasExibidas = formatDisplayValue(quantidade.ocorrencias_exibidas);
            const hasOcorrencias =
                quantidade.ocorrencias_encontradas != null ||
                quantidade.ocorrencias_exibidas != null;

            const fonteTitulo = formatDisplayValue(fonte.titulo);
            const fonteGerado = formatDisplayValue(fonte.gerado_em);

            const originLabel = state.origin || 'DETRAN';
            originTextEl.textContent = `Origem: ${originLabel}`;

            const summaryHtml = `
                <div class="bloqueios-summary-card">
                    <div class="bloqueios-summary-heading">
                        <div class="bloqueios-summary-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="bloqueios-summary-title">Bloqueios ${originLabel}</div>
                            <div class="bloqueios-summary-subtitle">
                                ${fonteTitulo === '—' ? 'Fonte não informada' : fonteTitulo}
                            </div>
                            <div class="bloqueios-summary-meta">
                                Gerado em ${fonteGerado === '—' ? '—' : fonteGerado}
                            </div>
                        </div>
                    </div>
                    <div class="bloqueios-summary-grid">
                        ${createSummaryItem('Placa', placa)}
                        ${createSummaryItem('Município da placa', municipioPlaca)}
                        ${createSummaryItem('Chassi consultado', chassi)}
                        ${createSummaryItem('Ocorrências encontradas', ocorrenciasEncontradas)}
                        ${
                            quantidade.ocorrencias_exibidas != null
                                ? createSummaryItem('Ocorrências exibidas', ocorrenciasExibidas)
                                : ''
                        }
                    </div>
                </div>
            `;

            const consultaHtml = `
                <div class="bloqueios-section-card">
                    <div class="bloqueios-section-title">Consulta</div>
                    <div class="bloqueios-info-row">
                        <div class="bloqueios-info-label">Placa</div>
                        <div class="bloqueios-info-value">${placa}</div>
                    </div>
                    <div class="bloqueios-info-row">
                        <div class="bloqueios-info-label">Município da placa</div>
                        <div class="bloqueios-info-value">${municipioPlaca}</div>
                    </div>
                    <div class="bloqueios-info-row">
                        <div class="bloqueios-info-label">Chassi</div>
                        <div class="bloqueios-info-value">${chassi}</div>
                    </div>
                    ${
                        hasOcorrencias
                            ? `
                                <div class="bloqueios-section-subtitle">Ocorrências</div>
                                <div class="bloqueios-info-row">
                                    <div class="bloqueios-info-label">Encontradas</div>
                                    <div class="bloqueios-info-value">${ocorrenciasEncontradas}</div>
                                </div>
                                ${
                                    quantidade.ocorrencias_exibidas != null
                                        ? `
                                            <div class="bloqueios-info-row">
                                                <div class="bloqueios-info-label">Exibidas</div>
                                                <div class="bloqueios-info-value">${ocorrenciasExibidas}</div>
                                            </div>
                                        `
                                        : ''
                                }
                            `
                            : ''
                    }
                </div>
            `;

            const fonteHtml = `
                <div class="bloqueios-section-card">
                    <div class="bloqueios-section-title">Fonte</div>
                    <div class="bloqueios-info-row">
                        <div class="bloqueios-info-label">Título</div>
                        <div class="bloqueios-info-value">
                            ${fonteTitulo === '—' ? 'Fonte não informada' : fonteTitulo}
                        </div>
                    </div>
                    <div class="bloqueios-info-row">
                        <div class="bloqueios-info-label">Gerado em</div>
                        <div class="bloqueios-info-value">${fonteGerado === '—' ? '—' : fonteGerado}</div>
                    </div>
                </div>
            `;

            const renajudHtml = `
                <div class="bloqueios-section-card">
                    <div class="bloqueios-section-title">Bloqueios RENAJUD</div>
                    ${
                        renajud.length === 0
                            ? '<div class="bloqueios-section-empty">Nenhum bloqueio RENAJUD encontrado.</div>'
                            : renajud.map(renderRenajudEntry).join('')
                    }
                </div>
            `;

            resultStackEl.innerHTML = summaryHtml + consultaHtml + fonteHtml + renajudHtml;
            showStatus('');
            copyButton.disabled = false;
            if (pdfButton) {
                pdfButton.disabled = false;
                pdfButton.classList.remove('is-loading');
            }
        }

        function renderStoredResult() {
            if (!storedResult) {
                showStatus('Nenhum resultado disponível. Realize a pesquisa de bloqueios ativos no painel.', true);
                resultStackEl.innerHTML = '';
                copyButton.disabled = true;
                if (pdfButton) pdfButton.disabled = true;
                return;
            }

            let state;
            try {
                state = JSON.parse(storedResult);
            } catch (error) {
                showStatus('Dados da pesquisa corrompidos. Execute uma nova pesquisa.', true);
                resultStackEl.innerHTML = '';
                copyButton.disabled = true;
                if (pdfButton) pdfButton.disabled = true;
                return;
            }

            renderBloqueios(state);
        }

        copyButton.addEventListener('click', async () => {
            if (!bloqueiosState || !bloqueiosState.payload) {
                return;
            }

            const payloadText = JSON.stringify(bloqueiosState.payload, null, 2);
            try {
                await navigator.clipboard.writeText(payloadText);
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
            pdfButton.addEventListener('click', emitBloqueiosPdf);
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
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                window.location.href = '/login';
            }
        });

        if (!checkAuth()) {
            return;
        }
        loadMonthlyCredits();
        renderStoredResult();
    </script>
</body>
</html>
