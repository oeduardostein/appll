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
            --primary: #0B52C2;
            --primary-dark: #0A3D9A;
            --bg: #F5F7FD;
            --card: #FFFFFF;
            --card-muted: #F3F5FB;
            --divider: #E2E8F0;
            --text-strong: #1F2937;
            --text-muted: #6B7280;
            --text-soft: #8A94A6;
            --error: #EF4444;
            --shadow-lg: 0 18px 38px rgba(15, 23, 42, 0.12);
            --shadow-md: 0 12px 28px rgba(15, 23, 42, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(180deg, #EEF3FF 0%, #F6F8FF 40%, #F8FAFC 100%);
            color: var(--text-strong);
            min-height: 100vh;
        }

        button,
        input,
        textarea {
            font-family: inherit;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        .bloqueios-page {
            min-height: 100vh;
            background: transparent;
        }

        .bloqueios-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 0 0 36px 36px;
            color: #fff;
            padding: 20px 20px 28px;
            box-shadow: 0 16px 28px rgba(11, 82, 194, 0.24);
        }

        .bloqueios-header-inner {
            max-width: 720px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .bloqueios-header-title {
            flex: 1;
            min-width: 0;
        }

        .bloqueios-header-title h1 {
            font-size: 22px;
            font-weight: 700;
        }

        .bloqueios-header-origin {
            margin-top: 4px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.75);
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .icon-button {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.14);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
            position: relative;
        }

        .icon-button svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        .icon-button:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.22);
            transform: translateY(-1px);
        }

        .icon-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .icon-button.is-success {
            background: rgba(255, 255, 255, 0.28);
            border-color: rgba(255, 255, 255, 0.7);
        }

        .icon-button .icon-check {
            display: none;
        }

        .icon-button.is-success .icon-copy {
            display: none;
        }

        .icon-button.is-success .icon-check {
            display: block;
        }

        .icon-button.is-loading svg {
            display: none;
        }

        .icon-button.is-loading .header-spinner {
            display: inline-block;
        }

        .header-spinner {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        .bloqueios-body {
            max-width: 720px;
            margin: 0 auto;
            padding: 20px 20px 36px;
        }

        .bloqueios-status-message {
            border: 1px solid var(--divider);
            background: var(--card);
            border-radius: 18px;
            padding: 14px 16px;
            margin-bottom: 20px;
            text-align: center;
            color: var(--text-muted);
            display: none;
            box-shadow: var(--shadow-md);
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
            gap: 18px;
        }

        .bloqueios-summary-card {
            background: var(--card);
            border-radius: 28px;
            padding: 22px;
            box-shadow: var(--shadow-lg);
        }

        .bloqueios-summary-heading {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .bloqueios-summary-icon {
            width: 62px;
            height: 62px;
            border-radius: 20px;
            background: rgba(11, 82, 194, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bloqueios-summary-icon svg {
            width: 34px;
            height: 34px;
            fill: none;
            stroke: var(--primary);
            stroke-width: 1.6;
        }

        .bloqueios-summary-title {
            font-size: 22px;
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
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 18px;
        }

        .bloqueios-summary-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .bloqueios-summary-item .bloqueios-info-label {
            font-size: 13px;
            color: var(--text-soft);
            text-transform: none;
        }

        .bloqueios-summary-item .bloqueios-info-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-strong);
            word-break: break-word;
        }

        .bloqueios-section-card {
            background: var(--card);
            border-radius: 24px;
            padding: 20px;
            border: 1px solid rgba(14, 23, 42, 0.08);
            box-shadow: var(--shadow-md);
        }

        .bloqueios-section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-strong);
            margin-bottom: 12px;
        }

        .bloqueios-info-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 14px;
        }

        .bloqueios-info-row:last-child {
            margin-bottom: 0;
        }

        .bloqueios-info-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-soft);
            text-transform: none;
        }

        .bloqueios-info-value {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-strong);
            word-break: break-word;
        }

        .bloqueios-renajud-card {
            background: var(--card-muted);
            border-radius: 20px;
            padding: 18px;
            border: 1px solid rgba(11, 82, 194, 0.12);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .bloqueios-renajud-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 14px;
            border-radius: 999px;
            background: #E7EEFF;
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            width: fit-content;
        }

        .bloqueios-renajud-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-strong);
        }

        .bloqueios-renajud-detail {
            display: flex;
            flex-direction: column;
            gap: 6px;
            background: #fff;
            border-radius: 14px;
            padding: 14px;
            border: 1px solid rgba(15, 23, 42, 0.08);
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
            line-height: 1.4;
        }

        .bloqueios-renajud-meta,
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
            .bloqueios-header-inner {
                gap: 12px;
            }

            .bloqueios-header-title h1 {
                font-size: 20px;
            }

            .header-actions {
                gap: 8px;
            }

            .icon-button {
                width: 38px;
                height: 38px;
            }

            .bloqueios-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="bloqueios-page">
        <header class="bloqueios-header">
            <div class="bloqueios-header-inner">
                <button class="icon-button" type="button" id="bloqueiosBackBtn" title="Voltar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <div class="bloqueios-header-title">
                    <h1>Bloqueios ativos</h1>
                    <div class="bloqueios-header-origin" id="bloqueiosOriginText">Origem: —</div>
                </div>
                <div class="header-actions">
                    <button class="icon-button" type="button" id="bloqueiosCopyJsonBtn" title="Copiar JSON" disabled>
                        <svg class="icon-copy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="12" height="12" rx="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <svg class="icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span class="sr-only" id="bloqueiosCopyLabel">Copiar JSON</span>
                    </button>
                    <button class="icon-button" type="button" id="bloqueiosPdfBtn" title="Gerar PDF" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 3v12"></path>
                            <path d="m7 10 5 5 5-5"></path>
                            <rect x="4" y="17" width="16" height="4" rx="2"></rect>
                        </svg>
                        <span class="header-spinner" aria-hidden="true"></span>
                        <span class="sr-only" id="bloqueiosPdfLabel">Gerar PDF</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="bloqueios-body">
            <div class="bloqueios-status-message" id="bloqueiosStatusMessage" role="status"></div>
            <div class="bloqueios-result-stack" id="bloqueiosResultStack"></div>
        </main>
    </div>

    <script>
        let authToken = localStorage.getItem('auth_token');
        const statusMessageEl = document.getElementById('bloqueiosStatusMessage');
        const resultStackEl = document.getElementById('bloqueiosResultStack');
        const originTextEl = document.getElementById('bloqueiosOriginText');
        const copyButton = document.getElementById('bloqueiosCopyJsonBtn');
        const copyButtonLabel = document.getElementById('bloqueiosCopyLabel');
        const backButton = document.getElementById('bloqueiosBackBtn');
        let bloqueiosState = null;
        let copyFeedbackTimeout = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const pdfButton = document.getElementById('bloqueiosPdfBtn');
        let bloqueiosPdfLoading = false;

        function checkAuth() {
            if (!authToken) {
                window.location.href = '/login';
                return false;
            }
            return true;
        }

        function getStoredResult() {
            try {
                return sessionStorage.getItem('bloqueios_ativos_result');
            } catch (error) {
                showStatus('Não foi possível acessar o resultado desta pesquisa. Faça uma nova consulta.', true);
                return null;
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

            html += `
                <div class="bloqueios-renajud-detail">
                    <div class="bloqueios-renajud-label">Motivo do bloqueio</div>
                    <div class="bloqueios-renajud-value">${motivoBloqueio}</div>
                </div>
            `;

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
            let payload = state?.payload;
            if (typeof payload === 'string') {
                try {
                    payload = JSON.parse(payload);
                } catch (error) {
                    payload = null;
                }
            }
            if (payload && typeof payload === 'object' && payload.data && typeof payload.data === 'object') {
                payload = payload.data;
            }
            if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
                showStatus('Resposta inválida da pesquisa.', true);
                resultStackEl.innerHTML = '';
                if (copyButton) copyButton.disabled = true;
                if (pdfButton) pdfButton.disabled = true;
                return;
            }

            if (!payload.consulta && !payload.fonte && !payload.renajud) {
                const message = payload.message || 'Não foi possível processar o resultado.';
                showStatus(message, true);
            }

            const consulta = payload.consulta || {};
            const quantidade = consulta.quantidade || {};
            const fonte = payload.fonte || {};
            const renajudRaw = payload.renajud;
            const renajud = Array.isArray(renajudRaw) ? renajudRaw : (renajudRaw ? [renajudRaw] : []);

            const placa = formatDisplayValue(consulta.placa);
            const municipioPlaca = formatDisplayValue(consulta.municipio_placa);
            const chassi = formatDisplayValue(consulta.chassi || state?.chassi);
            const ocorrenciasEncontradasLabel = formatDisplayValue(quantidade.ocorrencias_encontradas);

            const fonteTitulo = formatDisplayValue(fonte.titulo);
            const fonteGerado = formatDisplayValue(fonte.gerado_em);

            const originLabel = state.origin || 'DETRAN';
            if (originTextEl) {
                originTextEl.textContent = `Origem: ${originLabel}`;
            }

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
                        ${createSummaryItem('Ocorrências encontradas', ocorrenciasEncontradasLabel)}
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
            if (copyButton) {
                copyButton.disabled = false;
                copyButton.classList.remove('is-success');
                if (copyButtonLabel) {
                    copyButtonLabel.textContent = 'Copiar JSON';
                }
            }
            if (pdfButton) {
                pdfButton.disabled = false;
                pdfButton.classList.remove('is-loading');
            }
        }

        function renderStoredResult() {
            const storedResult = getStoredResult();
            if (!storedResult) {
                showStatus('Nenhum resultado disponível. Realize a pesquisa de bloqueios ativos no painel.', true);
                resultStackEl.innerHTML = '';
                if (copyButton) copyButton.disabled = true;
                if (pdfButton) pdfButton.disabled = true;
                return;
            }

            let state;
            try {
                state = JSON.parse(storedResult);
            } catch (error) {
                showStatus('Dados da pesquisa corrompidos. Execute uma nova pesquisa.', true);
                resultStackEl.innerHTML = '';
                if (copyButton) copyButton.disabled = true;
                if (pdfButton) pdfButton.disabled = true;
                return;
            }

            try {
                renderBloqueios(state);
            } catch (error) {
                console.error('Erro ao renderizar bloqueios ativos:', error);
                showStatus('Não foi possível exibir o resultado. Refaca a pesquisa.', true);
                resultStackEl.innerHTML = '';
            }
        }

        if (copyButton) {
            copyButton.addEventListener('click', async () => {
            if (!bloqueiosState || !bloqueiosState.payload) {
                return;
            }

            const payloadText = JSON.stringify(bloqueiosState.payload, null, 2);
            try {
                await navigator.clipboard.writeText(payloadText);
                copyButton.disabled = true;
                copyButton.classList.add('is-success');
                if (copyButtonLabel) {
                    copyButtonLabel.textContent = 'Copiado!';
                }
                if (copyFeedbackTimeout) {
                    clearTimeout(copyFeedbackTimeout);
                }
                copyFeedbackTimeout = setTimeout(() => {
                    copyButton.classList.remove('is-success');
                    if (copyButtonLabel) {
                        copyButtonLabel.textContent = 'Copiar JSON';
                    }
                    copyButton.disabled = false;
                }, 1800);
            } catch (error) {
                alert('Não foi possível copiar o resultado.');
            }
            });
        }

        if (pdfButton) {
            pdfButton.addEventListener('click', emitBloqueiosPdf);
        }

        if (backButton) {
            backButton.addEventListener('click', () => {
                window.location.href = '/home';
            });
        }

        if (!checkAuth()) {
            return;
        }
        renderStoredResult();
    </script>
</body>
</html>
