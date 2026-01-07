<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pesquisa Gravame - LL Despachante</title>
    <style>
        :root {
            color-scheme: light;
            --primary: #0047AB;
            --primary-dark: #0B3E98;
            --bg: #F8FAFC;
            --white: #FFFFFF;
            --text-strong: #1E293B;
            --text-muted: #64748B;
            --outline: #E2E8F0;
            --warning: #EF4444;
            --status-active: #F5F2E6;
            --status-active-border: #FFD873;
            --status-inactive: #E4E7EC;
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

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
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

        .icon-button.is-loading {
            opacity: 0.7;
            cursor: not-allowed;
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

        .gravame-result-header {
            background: var(--primary);
            color: #fff;
            border-radius: 0 0 32px 32px;
            padding: 16px 20px 26px;
        }

        .gravame-result-header-inner {
            max-width: 720px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .gravame-result-title {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .gravame-result-title-text {
            font-size: 20px;
            font-weight: 700;
        }

        .gravame-result-subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.85);
        }

        .gravame-result-subtitle:empty {
            display: none;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .gravame-result-body {
            max-width: 720px;
            margin: 0 auto;
            padding: 20px 20px 32px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .gravame-summary-card {
            background: var(--primary);
            color: #fff;
            border-radius: 24px;
            padding: 22px;
            box-shadow: 0 12px 28px rgba(0, 71, 171, 0.2);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .gravame-summary-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .gravame-summary-title {
            font-size: 18px;
            font-weight: 700;
        }

        .gravame-origin-chip {
            font-size: 14px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 14px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .gravame-generated-at {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.9);
        }

        .gravame-summary-tiles {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .gravame-summary-tile {
            flex: 1;
            min-width: 130px;
            background: #fff;
            color: var(--text-strong);
            border-radius: 16px;
            padding: 12px 14px;
        }

        .gravame-summary-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
        }

        .gravame-summary-value {
            font-size: 16px;
            font-weight: 600;
            margin-top: 6px;
        }

        .gravame-details-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 18px 30px rgba(15, 23, 42, 0.08);
            padding: 22px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .gravame-details-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .gravame-details-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: #EFF2FF;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gravame-details-icon svg {
            width: 32px;
            height: 32px;
            stroke: var(--primary);
            fill: none;
        }

        .gravame-status-label {
            font-size: 16px;
            font-weight: 700;
        }

        .gravame-status-date {
            font-size: 13px;
            color: var(--text-muted);
        }

        .gravame-detail-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .gravame-detail-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .gravame-detail-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .gravame-status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            padding: 6px 14px;
            font-weight: 600;
            font-size: 13px;
        }

        .gravame-status-pill.active {
            background: var(--status-active);
            color: #B78103;
            border: 1px solid #FFE1A8;
        }

        .gravame-status-pill.inactive {
            background: var(--status-inactive);
            color: #475467;
            border: 1px solid #CCD4E5;
        }

        .gravame-divider {
            height: 1px;
            background: #E4E7EC;
        }

        .gravame-json-section {
            background: #fff;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            padding: 20px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .gravame-json-section h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .gravame-json-pre {
            background: #F8FAFC;
            border-radius: 14px;
            border: 1px solid #E2E8F0;
            padding: 16px;
            font-size: 14px;
            color: var(--text-muted);
            overflow-x: auto;
        }

        @media (min-width: 768px) {
            .gravame-result-header {
                padding: 26px 32px 34px;
            }

            .gravame-result-body {
                padding: 24px 20px 36px;
            }
        }
    </style>
</head>
<body>
    <div class="gravame-result-header">
        <div class="gravame-result-header-inner">
            <button class="icon-button" type="button" id="gravameBackBtn" title="Voltar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <div class="gravame-result-title">
                <span class="gravame-result-title-text">Gravame</span>
                <span class="gravame-result-subtitle" id="gravameSubtitle"></span>
            </div>
            <div class="header-actions">
                <button class="icon-button" type="button" id="gravameCopyBtn" title="Copiar resultado">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                        <rect x="2" y="2" width="13" height="13" rx="2"></rect>
                    </svg>
                </button>
                <button class="icon-button" type="button" id="gravamePdfBtn" title="Emitir PDF" onclick="emitGravamePdf()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v12"></path>
                        <path d="m7 10 5 5 5-5"></path>
                        <rect x="4" y="17" width="16" height="4" rx="2"></rect>
                    </svg>
                    <span class="header-spinner" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>

    <main class="gravame-result-body">
        <section id="gravameSummaryCard"></section>
        <section id="gravameDetailsCard"></section>
    </main>

    <script>
        const authToken = localStorage.getItem('auth_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const summaryContainer = document.getElementById('gravameSummaryCard');
        const detailsContainer = document.getElementById('gravameDetailsCard');
        const subtitleEl = document.getElementById('gravameSubtitle');
        const copyBtn = document.getElementById('gravameCopyBtn');
        const pdfBtn = document.getElementById('gravamePdfBtn');
        const backBtn = document.getElementById('gravameBackBtn');

        let gravameResultData = null;
        let gravameMeta = null;
        let isPdfGenerating = false;

        function checkAuth() {
            if (!authToken) {
                window.location.href = '/login';
                return false;
            }
            return true;
        }

        function loadResultFromStorage() {
            const stored = sessionStorage.getItem('gravame_result');
            if (!stored) return null;
            try {
                return JSON.parse(stored);
            } catch (error) {
                return null;
            }
        }

        function loadMetaFromStorage() {
            const stored = sessionStorage.getItem('gravame_meta');
            if (!stored) return null;
            try {
                return JSON.parse(stored);
            } catch (error) {
                return null;
            }
        }

        function nonEmptyString(value) {
            if (value == null) return '';
            const text = value.toString().trim();
            return text === '' ? '' : text;
        }

        function formatSubtitleText() {
            const placa = nonEmptyString(gravameMeta?.placa) || nonEmptyString(gravameResultData?.veiculo?.placa);
            if (placa) {
                return `Placa: ${placa}`;
            }
            return '';
        }

        function formatStatusLabel(payload) {
            const intencao = payload?.intencao_gravame || {};
            const gravames = payload?.gravames || {};
            const restricaoIntencao = nonEmptyString(intencao.restricao_financeira);
            const restricaoAtual = nonEmptyString(gravames.restricao_financeira);
            if (restricaoIntencao || restricaoAtual) {
                return 'Ativo';
            }
            return 'Não encontrado';
        }

        function renderSummaryCard(payload, meta) {
            const veiculo = payload?.veiculo || {};
            const fonte = payload?.fonte || {};
            const generatedAt = nonEmptyString(fonte.gerado_em);
            const origin = nonEmptyString(payload?.origin);
            const placaValue = nonEmptyString(meta?.placa) || nonEmptyString(veiculo.placa) || '—';
            const renavamValue = nonEmptyString(meta?.renavam) || nonEmptyString(veiculo.renavam) || '—';
            const ufValue = nonEmptyString(meta?.uf) || nonEmptyString(veiculo.uf) || '—';
            const procedencia = nonEmptyString(veiculo.procedencia);
            const originLabel = origin === 'another_base_estadual' ? 'Outros estados' : 'Base estadual';

            let tiles = `
                <div class="gravame-summary-tiles">
                    <div class="gravame-summary-tile">
                        <div class="gravame-summary-label">Placa</div>
                        <div class="gravame-summary-value">${placaValue}</div>
                    </div>
                    <div class="gravame-summary-tile">
                        <div class="gravame-summary-label">Renavam</div>
                        <div class="gravame-summary-value">${renavamValue}</div>
                    </div>
                </div>
            `;

            let extraRow = '';
            if (ufValue !== '—' || procedencia) {
                let thirdTile = '';
                if (ufValue !== '—') {
                    thirdTile += `
                        <div class="gravame-summary-tile">
                            <div class="gravame-summary-label">UF</div>
                            <div class="gravame-summary-value">${ufValue}</div>
                        </div>
                    `;
                }
                if (procedencia) {
                    thirdTile += `
                        <div class="gravame-summary-tile">
                            <div class="gravame-summary-label">Procedência</div>
                            <div class="gravame-summary-value">${procedencia}</div>
                        </div>
                    `;
                }
                extraRow = `<div class="gravame-summary-tiles">${thirdTile}</div>`;
            }

            return `
                <div class="gravame-summary-card">
                    <div class="gravame-summary-header">
                        <div class="gravame-summary-title">Resumo da consulta</div>
                        ${originLabel ? `<span class="gravame-origin-chip">${originLabel}</span>` : ''}
                    </div>
                    ${generatedAt ? `<p class="gravame-generated-at">Gerado em ${generatedAt}</p>` : ''}
                    ${tiles}
                    ${extraRow}
                </div>
            `;
        }

        function renderDetailsCard(payload) {
            const gravames = payload?.gravames || {};
            const gravamesDatas = payload?.gravames_datas || {};
            const intencao = payload?.intencao_gravame || {};

            const inclusionDate = nonEmptyString(intencao.data_inclusao) ||
                nonEmptyString(gravamesDatas.inclusao_financiamento) ||
                '—';
            const restricaoFinanceira = nonEmptyString(intencao.restricao_financeira) ||
                nonEmptyString(gravames.restricao_financeira) ||
                '—';
            const agenteFinanceiro = nonEmptyString(intencao.agente_financeiro) ||
                nonEmptyString(gravames.nome_agente) ||
                '—';
            const nomeFinanciado = nonEmptyString(intencao.nome_financiado) ||
                nonEmptyString(gravames.arrendatario) ||
                '—';
            const documentoFinanciado = nonEmptyString(intencao.cnpj_cpf) ||
                nonEmptyString(gravames.cnpj_cpf_financiado) ||
                '—';
            const arrendatario = nonEmptyString(gravames.arrendatario);
            const statusLabel = formatStatusLabel(payload);
            const isActive = statusLabel.toLowerCase().includes('ativo');

            return `
                <div class="gravame-details-card">
                    <div class="gravame-details-header">
                        <div class="gravame-details-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                        <div>
                            <div class="gravame-status-label">Gravame: ${statusLabel}</div>
                            <div class="gravame-status-date">Inclusão: ${inclusionDate}</div>
                        </div>
                    </div>
                    <div class="gravame-divider"></div>
                    <div class="gravame-detail-row">
                        <span class="gravame-detail-label">Restrição Financeira</span>
                        <span class="gravame-detail-value">${restricaoFinanceira}</span>
                        <span class="gravame-status-pill ${isActive ? 'active' : 'inactive'}">${statusLabel}</span>
                    </div>
                    <div class="gravame-divider"></div>
                    <div class="gravame-detail-row">
                        <span class="gravame-detail-label">Agente Financeiro</span>
                        <span class="gravame-detail-value">${agenteFinanceiro}</span>
                    </div>
                    <div class="gravame-divider"></div>
                    <div class="gravame-detail-row">
                        <span class="gravame-detail-label">Nome do Financiado</span>
                        <span class="gravame-detail-value">${nomeFinanciado}</span>
                    </div>
                    ${arrendatario ? `
                        <div class="gravame-divider"></div>
                        <div class="gravame-detail-row">
                            <span class="gravame-detail-label">Arrendatário / Financiado</span>
                            <span class="gravame-detail-value">${arrendatario}</span>
                        </div>
                    ` : ''}
                    <div class="gravame-divider"></div>
                    <div class="gravame-detail-row">
                        <span class="gravame-detail-label">Documento do Financiado</span>
                        <span class="gravame-detail-value">${documentoFinanciado}</span>
                    </div>
                </div>
            `;
        }

        function setPdfLoading(isLoading) {
            if (!pdfBtn) return;
            pdfBtn.classList.toggle('is-loading', isLoading);
            pdfBtn.disabled = isLoading;
        }

        async function emitGravamePdf() {
            if (isPdfGenerating) return;
            if (!gravameResultData) {
                alert('Nenhum resultado disponível para gerar o PDF.');
                return;
            }

            isPdfGenerating = true;
            setPdfLoading(true);

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

                const response = await fetch('/api/gravame/pdf', {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({
                        payload: gravameResultData,
                        meta: gravameMeta,
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
                const placaValue = nonEmptyString(gravameMeta?.placa) || 'consulta';
                const sanitized = placaValue.replace(/[^A-Za-z0-9]/g, '') || 'consulta';
                const filename = `pesquisa_gravame_${sanitized}.pdf`;

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
                isPdfGenerating = false;
                setPdfLoading(false);
            }
        }

        if (checkAuth()) {
            gravameResultData = loadResultFromStorage();
            gravameMeta = loadMetaFromStorage();
            subtitleEl.textContent = formatSubtitleText();
            if (gravameResultData) {
                summaryContainer.innerHTML = renderSummaryCard(gravameResultData, gravameMeta);
                detailsContainer.innerHTML = renderDetailsCard(gravameResultData);
            } else {
                summaryContainer.innerHTML = '<p style="text-align:center; color:#94A3B8;">Nenhum resultado disponível.</p>';
                detailsContainer.innerHTML = '';
            }
        }

        backBtn?.addEventListener('click', () => {
            sessionStorage.removeItem('gravame_result');
            sessionStorage.removeItem('gravame_meta');
            window.location.href = '/home';
        });

        copyBtn?.addEventListener('click', async () => {
            if (!gravameResultData) return;
            try {
                await navigator.clipboard.writeText(JSON.stringify(gravameResultData, null, 2));
                alert('Dados copiados para a área de transferência.');
            } catch (error) {
                alert('Não foi possível copiar o resultado.');
            }
        });
    </script>
</body>
</html>
