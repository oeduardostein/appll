<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Intenção de venda - LL Despachante</title>
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
            --shadow-md: 0 12px 28px rgba(15, 23, 42, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(180deg, #EEF3FF 0%, #F7F9FF 45%, #F9FBFF 100%);
            color: var(--text-strong);
            min-height: 100vh;
        }

        .atpv-page {
            min-height: 100vh;
            background: transparent;
        }

        .atpv-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 24px 20px 28px;
            border-radius: 0 0 36px 36px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .atpv-header-title {
            flex: 1;
            min-width: 0;
        }

        .atpv-header-title h1 {
            font-size: 22px;
            font-weight: 700;
        }

        .atpv-header-title p {
            margin-top: 4px;
            color: rgba(255, 255, 255, 0.85);
            font-size: 14px;
        }

        .atpv-header button.icon-button {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.14);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .atpv-header button.icon-button svg {
            width: 20px;
            height: 20px;
        }

        .atpv-body {
            max-width: 760px;
            margin: 0 auto;
            padding: 24px 20px 32px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .atpv-status-message {
            border: 1px solid var(--divider);
            background: var(--card);
            border-radius: 18px;
            padding: 14px 16px;
            color: var(--text-muted);
            display: none;
            text-align: center;
            box-shadow: var(--shadow-md);
        }

        .atpv-status-message.show {
            display: block;
        }

        .atpv-status-message.atpv-status-error {
            border-color: #FECACA;
            background: #FEF2F2;
            color: #B91C1C;
        }

        .atpv-result-stack {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .atpv-actions {
            display: flex;
            justify-content: center;
        }

        .atpv-primary-button {
            appearance: none;
            border: none;
            border-radius: 999px;
            padding: 14px 24px;
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            background: var(--primary);
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(11, 82, 194, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        .atpv-primary-button:disabled {
            cursor: not-allowed;
            opacity: 0.55;
            box-shadow: none;
        }

        .atpv-primary-button:not(:disabled):hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(11, 82, 194, 0.3);
        }

        .atpv-summary-card,
        .atpv-section-card {
            background: var(--card);
            border-radius: 28px;
            padding: 22px;
            box-shadow: var(--shadow-md);
        }

        .atpv-summary-card h2 {
            margin-bottom: 12px;
            font-size: 18px;
            color: var(--text-soft);
        }

        .atpv-summary-grid,
        .atpv-section-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 18px;
        }

        .atpv-info-row {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .atpv-info-label {
            font-size: 12px;
            color: var(--text-soft);
            font-weight: 600;
        }

        .atpv-info-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-strong);
            word-break: break-word;
        }

        .atpv-section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-strong);
            margin-bottom: 14px;
        }

        .atpv-section-empty {
            font-size: 14px;
            color: var(--text-muted);
            text-align: center;
            padding: 12px 0;
        }

        .atpv-communication-card {
            border-radius: 18px;
            border: 1px solid rgba(11, 82, 194, 0.25);
            padding: 16px;
            background: #fff;
            margin-bottom: 12px;
        }

        .atpv-communication-card:last-child {
            margin-bottom: 0;
        }

        .atpv-communication-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .atpv-communication-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px 16px;
        }

        .atpv-communication-grid .atpv-info-row {
            gap: 2px;
        }

        @media (max-width: 640px) {
            .atpv-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .atpv-summary-grid,
            .atpv-section-grid,
            .atpv-communication-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="atpv-page">
        <header class="atpv-header">
            <button class="icon-button" type="button" id="atpvBackBtn" aria-label="Voltar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <div class="atpv-header-title">
                <h1>Intenção de venda</h1>
                <p id="atpvHeaderSubtitle">Confira os dados consultados</p>
            </div>
            <button class="icon-button" type="button" id="atpvCopyBtn" aria-label="Copiar JSON" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="12" height="12" rx="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
            </button>
        </header>
        <main class="atpv-body">
            <div class="atpv-status-message" id="atpvStatusMessage" role="status"></div>
            <div class="atpv-actions">
                <button class="atpv-primary-button" type="button" id="atpvDownloadPdfBtn" disabled>
                    Baixar PDF já emitido
                </button>
            </div>
            <div class="atpv-result-stack" id="atpvResultStack"></div>
        </main>
    </div>

    <script>
        const statusMessageEl = document.getElementById('atpvStatusMessage');
        const resultStackEl = document.getElementById('atpvResultStack');
        const backButton = document.getElementById('atpvBackBtn');
        const copyButton = document.getElementById('atpvCopyBtn');
        const downloadPdfButton = document.getElementById('atpvDownloadPdfBtn');
        const sessionKey = 'atpv_intencao_result';
        let storedState = null;

        function safeGetStorage(storage, key) {
            try {
                return storage.getItem(key);
            } catch (error) {
                return null;
            }
        }

        function getStoredResult() {
            const sessionValue = safeGetStorage(sessionStorage, sessionKey);
            if (sessionValue) {
                return sessionValue;
            }
            const localValue = safeGetStorage(localStorage, sessionKey);
            if (localValue) {
                try {
                    sessionStorage.setItem(sessionKey, localValue);
                } catch (error) {
                    // fallback quietly
                }
                return localValue;
            }
            showStatus('Nenhum resultado disponível. Realize a consulta de intenção de venda no painel.', true);
            return null;
        }

        function showStatus(message, isError = false) {
            if (!statusMessageEl) return;
            if (!message) {
                statusMessageEl.textContent = '';
                statusMessageEl.classList.remove('show', 'atpv-status-error');
                return;
            }
            statusMessageEl.textContent = message;
            statusMessageEl.classList.add('show');
            statusMessageEl.classList.toggle('atpv-status-error', isError);
        }

        function formatValue(value) {
            if (value == null || value === '') {
                return '—';
            }
            return String(value);
        }

        function renderInfoRows(items) {
            return items
                .map(([label, value]) => `
                    <div class="atpv-info-row">
                        <div class="atpv-info-label">${label}</div>
                        <div class="atpv-info-value">${formatValue(value)}</div>
                    </div>
                `)
                .join('');
        }

        function renderCommunicationCard(entry, index) {
            const buyer = entry?.comprador || {};
            const intention = entry?.intencao || {};

            return `
                <div class="atpv-communication-card">
                    <div class="atpv-communication-label">Comunicação ${index + 1}</div>
                    <div class="atpv-communication-grid">
                        <div class="atpv-info-row">
                            <div class="atpv-info-label">Estado</div>
                            <div class="atpv-info-value">${formatValue(intention.estado)}</div>
                        </div>
                        <div class="atpv-info-row">
                            <div class="atpv-info-label">Data/hora</div>
                            <div class="atpv-info-value">${formatValue(intention.data_hora)}</div>
                        </div>
                        <div class="atpv-info-row">
                            <div class="atpv-info-label">Valor da venda</div>
                            <div class="atpv-info-value">${formatValue(intention.valor_venda)}</div>
                        </div>
                        <div class="atpv-info-row">
                            <div class="atpv-info-label">Comprador</div>
                            <div class="atpv-info-value">${formatValue(buyer.nome)} (${formatValue(buyer.documento)})</div>
                        </div>
                        <div class="atpv-info-row">
                            <div class="atpv-info-label">Cidade/UF</div>
                            <div class="atpv-info-value">${formatValue(buyer.municipio)} / ${formatValue(buyer.uf)}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderAtpvResult(state) {
            if (!state || !state.payload) {
                showStatus('Não foi possível processar o resultado.', true);
                resultStackEl.innerHTML = '';
                if (copyButton) copyButton.disabled = true;
                return;
            }

            let payload = state.payload;
            if (typeof payload === 'string') {
                try {
                    payload = JSON.parse(payload);
                } catch (error) {
                    payload = null;
                }
            }

            if (!payload || typeof payload !== 'object') {
                showStatus('Resposta inválida da consulta.', true);
                resultStackEl.innerHTML = '';
                if (copyButton) copyButton.disabled = true;
                return;
            }

            if (payload.ok && payload.data && typeof payload.data === 'object') {
                payload = payload.data;
            }

            const consulta = payload.consulta || {};
            const veiculo = payload.veiculo || {};
            const proprietario = payload.proprietario || {};
            const comunicacoes = Array.isArray(payload.comunicacao_vendas)
                ? payload.comunicacao_vendas
                : [];
            const intencaoVenda = payload.intencao_venda || {};
            const veiculoIntencao = intencaoVenda.veiculo || {};
            const compradorIntencao = intencaoVenda.comprador || {};
            const dadosIntencao = intencaoVenda.intencao || {};
            const assinaturaIntencao = intencaoVenda.assinatura || {};
            const resumoPlaca = veiculoIntencao.placa || consulta.placa;
            const resumoRenavam = veiculoIntencao.renavam || consulta.renavam;
            const resumoChassi = veiculoIntencao.chassi || veiculo.chassi;

            const summaryHtml = `
                <div class="atpv-summary-card">
                    <h2>${formatValue(payload.fonte?.titulo)}</h2>
                    <div class="atpv-summary-grid">
                        <div class="atpv-info-row">
                            <div class="atpv-info-label">Placa</div>
                            <div class="atpv-info-value">${formatValue(resumoPlaca)}</div>
                        </div>
                        <div class="atpv-info-row">
                            <div class="atpv-info-label">Renavam</div>
                            <div class="atpv-info-value">${formatValue(resumoRenavam)}</div>
                        </div>
                        <div class="atpv-info-row">
                            <div class="atpv-info-label">Gerado em</div>
                            <div class="atpv-info-value">${formatValue(payload.fonte?.gerado_em)}</div>
                        </div>
                        <div class="atpv-info-row">
                            <div class="atpv-info-label">Chassi</div>
                            <div class="atpv-info-value">${formatValue(resumoChassi)}</div>
                        </div>
                    </div>
                </div>
            `;

            const intencaoHtml = `
                <div class="atpv-section-card">
                    <div class="atpv-section-title">Intenção de venda</div>
                    <div class="atpv-section-grid">
                        ${renderInfoRows([
                            ['Número ATPVE', veiculoIntencao.numero_atpve],
                            ['Hodômetro', veiculoIntencao.hodometro],
                            ['Comprador', compradorIntencao.nome],
                            ['Documento', compradorIntencao.documento],
                            ['Email', compradorIntencao.email],
                            ['Município', compradorIntencao.municipio],
                            ['UF', compradorIntencao.uf],
                            ['Valor da venda', compradorIntencao.valor_venda],
                            ['Estado', dadosIntencao.estado],
                            ['Data/Hora', dadosIntencao.data_hora],
                            ['Atualização', dadosIntencao.data_hora_atualizacao],
                            ['Assinatura', assinaturaIntencao.tipo],
                        ])}
                    </div>
                </div>
            `;

            const vehicleHtml = `
                <div class="atpv-section-card">
                    <div class="atpv-section-title">Dados do veículo</div>
                    <div class="atpv-section-grid">
                        ${renderInfoRows([
                            ['Marca', veiculo.marca],
                            ['Modelo', veiculo.tipo],
                            ['Cor', veiculo.cor],
                            ['Categoria', veiculo.categoria],
                            ['Procedência', veiculo.procedencia],
                            ['Combustível', veiculo.combustivel],
                        ])}
                    </div>
                </div>
            `;

            const ownerHtml = `
                <div class="atpv-section-card">
                    <div class="atpv-section-title">Proprietário</div>
                    <div class="atpv-section-grid">
                        ${renderInfoRows([
                            ['Nome', proprietario.nome],
                        ])}
                    </div>
                </div>
            `;

            const communicationsHtml = `
                <div class="atpv-section-card">
                    <div class="atpv-section-title">Comunicações de venda</div>
                    ${comunicacoes.length === 0
                        ? '<div class="atpv-section-empty">Nenhuma comunicação de venda encontrada.</div>'
                        : comunicacoes.map(renderCommunicationCard).join('')
                    }
                </div>
            `;

            resultStackEl.innerHTML = summaryHtml + intencaoHtml + vehicleHtml + ownerHtml + communicationsHtml;
            showStatus('');
            storedState = state;
            if (copyButton) {
                copyButton.disabled = false;
            }
            if (downloadPdfButton) {
                downloadPdfButton.disabled = false;
            }
        }

        function renderStoredResult() {
            const stored = getStoredResult();
            if (!stored) {
                resultStackEl.innerHTML = '';
                if (copyButton) copyButton.disabled = true;
                return;
            }

            let state;
            try {
                state = JSON.parse(stored);
            } catch (error) {
                showStatus('Dados da consulta corrompidos. Execute uma nova consulta.', true);
                resultStackEl.innerHTML = '';
                if (copyButton) copyButton.disabled = true;
                return;
            }

            if (state && typeof state === 'object' && state.storedAt) {
                const minutes = Math.floor((Date.now() - state.storedAt) / 60000);
                if (minutes >= 30) {
                    showStatus('Este resultado expirou. Faça uma nova consulta.', true);
                    resultStackEl.innerHTML = '';
                    if (copyButton) copyButton.disabled = true;
                    return;
                }
            }

            try {
                renderAtpvResult(state);
            } catch (error) {
                console.error('Erro ao renderizar intenção de venda:', error);
                showStatus('Não foi possível exibir o resultado. Refaça a consulta.', true);
                resultStackEl.innerHTML = '';
                if (copyButton) copyButton.disabled = true;
                if (downloadPdfButton) downloadPdfButton.disabled = true;
            }
        }

        function getAuthToken() {
            return sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
        }

        function checkAuth() {
            const token = getAuthToken();
            if (!token) {
                window.location.href = '/login';
                return false;
            }
            return true;
        }

        backButton.addEventListener('click', () => {
            window.location.href = '/home';
        });

        if (copyButton) {
            copyButton.addEventListener('click', async () => {
                if (!storedState || !storedState.payload) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(JSON.stringify(storedState.payload, null, 2));
                    copyButton.disabled = true;
                    setTimeout(() => {
                        copyButton.disabled = false;
                    }, 1500);
                } catch (error) {
                    alert('Não foi possível copiar o resultado.');
                }
            });
        }

        async function downloadAtpvPdf() {
            if (!storedState) {
                showStatus('Resultado indisponível para baixar o PDF.', true);
                return;
            }

            const authToken = getAuthToken();
            if (!authToken) {
                window.location.href = '/login';
                return;
            }

            const plate = (storedState.plate || storedState.payload?.consulta?.placa || '').toString().trim();
            const renavam = (storedState.renavam || storedState.payload?.consulta?.renavam || '').toString().trim();
            const captcha = (storedState.captcha || '').toString().trim();

            if (!plate || !renavam || !captcha) {
                showStatus('Dados insuficientes para baixar o PDF.', true);
                return;
            }

            showStatus('Baixando PDF da ATPV-e...');
            if (downloadPdfButton) downloadPdfButton.disabled = true;

            try {
                const params = new URLSearchParams({
                    placa: plate,
                    renavam: renavam,
                    captcha: captcha,
                });
                const response = await fetch(`/api/emissao-atpv/pdf?${params}`, {
                    headers: {
                        'Accept': 'application/pdf',
                        'Authorization': `Bearer ${authToken}`,
                    },
                });

                if (!response.ok) {
                    let message = 'Falha ao baixar o PDF da ATPV-e.';
                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        const data = await response.json().catch(() => ({}));
                        message = data.message || message;
                    }
                    showStatus(message, true);
                    return;
                }

                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('pdf')) {
                    showStatus('Resposta inesperada ao gerar o PDF da ATPV-e.', true);
                    return;
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `ATPV-${plate}-${Date.now()}.pdf`;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
                showStatus('');
            } catch (error) {
                showStatus('Não foi possível baixar o PDF da ATPV-e.', true);
            } finally {
                if (downloadPdfButton) downloadPdfButton.disabled = false;
            }
        }

        if (downloadPdfButton) {
            downloadPdfButton.addEventListener('click', downloadAtpvPdf);
        }

        if (checkAuth()) {
            renderStoredResult();
        }
    </script>
</body>
</html>
