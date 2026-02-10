<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Resultado Base Estadual - LL Despachante</title>
    <style>
        :root {
            color-scheme: light;
            --primary: #0047AB;
            --primary-dark: #0B3E98;
            --bg: #F8FAFC;
            --white: #FFFFFF;
            --text-strong: #1E293B;
            --text-muted: #64748B;
            --text-soft: #667085;
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
            background-color: var(--bg);
            color: var(--text-strong);
            min-height: 100vh;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        button,
        input,
        textarea {
            font-family: inherit;
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

        .be-result {
            min-height: 100vh;
            background: var(--bg);
        }

        .be-result-header {
            background: var(--primary);
            color: #fff;
            border-radius: 0 0 32px 32px;
            padding: 20px;
        }

        .be-result-header-inner {
            max-width: 720px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .be-result-title {
            flex: 1;
            font-size: 20px;
            font-weight: 700;
        }

        .be-result-body {
            max-width: 720px;
            margin: 0 auto;
            padding: 20px 20px 32px;
        }

        .result-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 18px rgba(16, 24, 40, 0.05);
            padding: 24px;
            margin-bottom: 16px;
        }

        .vehicle-summary-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.06);
            padding: 20px;
            margin-bottom: 16px;
        }

        .vehicle-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .vehicle-icon {
            width: 64px;
            height: 64px;
            background: rgba(0, 71, 171, 0.18);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .vehicle-icon svg {
            width: 32px;
            height: 32px;
            stroke: #0047AB;
            fill: none;
        }

        .vehicle-info {
            flex: 1;
            min-width: 0;
        }

        .vehicle-placa {
            font-size: 24px;
            font-weight: 800;
            color: #1E293B;
            margin-bottom: 4px;
        }

        .vehicle-marca {
            font-size: 18px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 2px;
        }

        .vehicle-ano {
            font-size: 14px;
            color: #64748B;
        }

        .vehicle-tiles {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        @media (min-width: 640px) {
            .vehicle-tiles {
                grid-template-columns: 1fr 1fr;
            }
        }

        .vehicle-tile {
            background: #F8FAFC;
            padding: 16px;
            border-radius: 18px;
            border: 1px solid #E2E8F0;
        }

        .vehicle-tile-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 6px;
        }

        .vehicle-tile-value {
            font-size: 16px;
            font-weight: 700;
            color: #1E293B;
        }

        .vehicle-tile-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 8px;
        }

        .vehicle-proprietario {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #E2E8F0;
        }

        .vehicle-proprietario-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 4px;
        }

        .vehicle-proprietario-value {
            font-size: 14px;
            font-weight: 700;
            color: #1E293B;
        }

        .btn-text-link {
            border: none;
            background: none;
            color: #2F80ED;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-text-link:hover {
            text-decoration: underline;
        }

        .action-menu-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 18px rgba(16, 24, 40, 0.05);
            overflow: hidden;
            margin-bottom: 16px;
        }

        .action-menu-item {
            width: 100%;
            border: none;
            background: transparent;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .action-menu-item:hover {
            background: #F8FAFC;
        }

        .action-menu-item:not(:last-child) {
            border-bottom: 1px solid #E2E8F0;
        }

        .action-menu-icon {
            width: 20px;
            height: 20px;
            color: #0047AB;
        }

        .action-menu-label {
            flex: 1;
            text-align: left;
            font-size: 15px;
            font-weight: 600;
            color: #1E293B;
        }

        .action-menu-arrow {
            width: 18px;
            height: 18px;
            color: #94A3B8;
        }

        .section-card {
            background: white;
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 10px 18px rgba(16, 24, 40, 0.05);
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #1E293B;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding: 10px 0;
            border-bottom: 1px solid #F1F5F9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 13px;
            color: #64748B;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #1E293B;
            text-align: right;
        }

        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 950;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            width: min(520px, 92vw);
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.2);
            overflow: hidden;
        }

        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: #1E293B;
        }

        .modal-close {
            border: none;
            background: none;
            font-size: 24px;
            line-height: 1;
            color: #64748B;
            cursor: pointer;
        }

        .modal-close:hover {
            color: #1E293B;
        }

        .modal-body {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }

        @media (min-width: 768px) {
            .be-result-header {
                padding: 24px;
            }

            .be-result-title {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="be-result">
        <div class="be-result-header">
            <div class="be-result-header-inner">
                <button class="icon-button" type="button" id="baseResultBack" title="Voltar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <div class="be-result-title">Consulta base estadual</div>
                <div class="header-actions">
                    <button class="icon-button" type="button" id="basePdfTopBtn" title="Emitir PDF" onclick="emitBaseEstadualPdf()">
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
        <div class="be-result-body">
            <div id="baseResultContent"></div>
        </div>

        <div class="modal" id="detailModal" aria-hidden="true">
            <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
                <div class="modal-header">
                    <h2 class="modal-title" id="modalTitle">Detalhes</h2>
                    <button class="modal-close" type="button" aria-label="Fechar" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body" id="modalBody"></div>
            </div>
        </div>
    </div>

    <script>
        const baseResultContent = document.getElementById('baseResultContent');
        const baseResultBack = document.getElementById('baseResultBack');
        function getAuthToken() {
            return sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
        }

        const authToken = getAuthToken();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        function checkAuth() {
            if (!authToken) {
                window.location.href = '/login';
                return false;
            }
            return true;
        }

        function formatDisplayValue(value) {
            if (value == null || value === '') return '—';
            if (typeof value === 'string') {
                const trimmed = value.trim();
                return trimmed === '' ? '—' : trimmed;
            }
            return value.toString();
        }

        function parseMarca(value) {
            const text = formatDisplayValue(value);
            if (text === '—') return 'Marca não informada';
            const parts = text.split(' - ');
            if (parts.length >= 2) {
                const joined = parts.slice(1).join(' - ').trim();
                return joined === '' ? text : joined;
            }
            return text;
        }

        function buildAnoModelo(modelo, fabricacao) {
            if (modelo === '—' && fabricacao === '—') {
                return 'Ano não informado';
            }
            if (modelo !== '—' && fabricacao !== '—') {
                return `${modelo} / ${fabricacao}`;
            }
            return modelo !== '—' ? modelo : fabricacao;
        }

        function buildInfoRows(source, labels) {
            if (!source || typeof source !== 'object') return '';
            let html = '';
            for (const [key, label] of Object.entries(labels)) {
                const value = source[key];
                if (value !== null && value !== undefined && value !== '') {
                    const formatted = formatDisplayValue(value);
                    if (formatted !== '—') {
                        html += `
                            <div class="info-row">
                                <div class="info-label">${label}</div>
                                <div class="info-value">${formatted}</div>
                            </div>
                        `;
                    }
                }
            }
            return html;
        }

        function displayBaseEstadualResult(data) {
            const content = baseResultContent;

            if (data.veiculo || data.fonte) {
                const veiculo = data.veiculo || {};
                const proprietario = data.proprietario || {};
                const crvCrlv = data.crv_crlv_atualizacao || {};

                const placaValue = formatDisplayValue(veiculo.placa);
                const marca = parseMarca(veiculo.marca);
                const anoModelo = formatDisplayValue(veiculo.ano_modelo);
                const anoFab = formatDisplayValue(veiculo.ano_fabricacao);
                const anoDisplay = buildAnoModelo(anoModelo, anoFab);
                const municipio = formatDisplayValue(veiculo.municipio);
                const proprietarioNome = formatDisplayValue(proprietario.nome);
                const licenciamentoEx = formatDisplayValue(crvCrlv.exercicio_licenciamento);
                const licenciamentoData = formatDisplayValue(crvCrlv.data_licenciamento);
                const licStatus = licenciamentoData !== '—' ? 'em dia' : 'Não informado';

                let html = `
                    <div class="vehicle-summary-card">
                        <div class="vehicle-header">
                            <div class="vehicle-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1M5 17l-1 4h18l-1-4M5 17h14M9 9h6m-6 4h6"></path>
                                </svg>
                            </div>
                            <div class="vehicle-info">
                                <div class="vehicle-placa">${placaValue}</div>
                                <div class="vehicle-marca">${marca}</div>
                                <div class="vehicle-ano">${anoDisplay}</div>
                            </div>
                        </div>
                        <div class="vehicle-tiles">
                            <div class="vehicle-tile">
                                <div class="vehicle-tile-label">Licenciamento</div>
                                <div class="vehicle-tile-value">${licenciamentoEx}</div>
                                <span class="vehicle-tile-badge" style="background: rgba(76, 175, 80, 0.15); color: #4CAF50;">${licStatus}</span>
                            </div>
                            <div class="vehicle-tile">
                                <div class="vehicle-tile-label">Município</div>
                                <div class="vehicle-tile-value">${municipio}</div>
                            </div>
                        </div>
                        <div class="vehicle-proprietario">
                            <div>
                                <div class="vehicle-proprietario-label">Proprietário</div>
                                <div class="vehicle-proprietario-value">${proprietarioNome}</div>
                            </div>
                            <button class="btn-text-link" type="button" onclick="showVehicleDetails()">Ver completo</button>
                        </div>
                    </div>

                    <div class="action-menu-card">
                        <button class="action-menu-item" type="button" onclick="showVehicleDetails()">
                            <svg class="action-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1M5 17l-1 4h18l-1-4M5 17h14M9 9h6m-6 4h6"></path>
                            </svg>
                            <span class="action-menu-label">Informações do veículo</span>
                            <svg class="action-menu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        <button class="action-menu-item" type="button" onclick="showGravameDetails()">
                            <svg class="action-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            <span class="action-menu-label">Gravame</span>
                            <svg class="action-menu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        <button class="action-menu-item" type="button" onclick="showDebitosDetails()">
                            <svg class="action-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            <span class="action-menu-label">Multas e débitos</span>
                            <svg class="action-menu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        <button class="action-menu-item" type="button" onclick="showRestricoesDetails()">
                            <svg class="action-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <span class="action-menu-label">Restrições</span>
                            <svg class="action-menu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        <button class="action-menu-item" type="button" onclick="showComunicacaoDetails()">
                            <svg class="action-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <span class="action-menu-label">Comunicações de venda</span>
                            <svg class="action-menu-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                `;

                if (data.fonte) {
                    const fonteRows = buildInfoRows(data.fonte, {
                        'titulo': 'Título',
                        'gerado_em': 'Gerado em',
                    });
                    if (fonteRows) {
                        html += `
                            <div class="section-card">
                                <div class="section-title">Fonte</div>
                                ${fonteRows}
                            </div>
                        `;
                    }
                }

                content.innerHTML = html;
                window.baseResultData = data;
            } else if (data.message) {
                content.innerHTML = `
                    <div class="result-card">
                        <div style="padding: 16px; text-align: center;">
                            <p style="font-size: 16px; color: #1E293B;">${data.message}</p>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="result-card">
                        <pre style="background: #F8FAFC; padding: 16px; border-radius: 12px; border: 1px solid #E2E8F0; font-size: 13px; overflow-x: auto;">${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            }
        }

        let isPdfGenerating = false;

        function setPdfLoading(isLoading) {
            const pdfBtn = document.getElementById('basePdfTopBtn');
            if (!pdfBtn) return;
            pdfBtn.classList.toggle('is-loading', isLoading);
            pdfBtn.disabled = isLoading;
        }

        async function emitBaseEstadualPdf() {
            if (isPdfGenerating) return;
            if (!window.baseResultData || !window.baseResultData.veiculo) {
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

                const response = await fetch('/api/base-estadual/pdf', {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({
                        payload: window.baseResultData,
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
                const placa = (window.baseResultData.veiculo?.placa || 'consulta')
                    .toString()
                    .replace(/[^A-Za-z0-9]/g, '');
                const filename = `pesquisa_base_estadual_${placa || 'consulta'}.pdf`;

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

        function showVehicleDetails() {
            if (!window.baseResultData || !window.baseResultData.veiculo) return;

            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');

            modalTitle.textContent = 'Informações do veículo';

            let html = '';
            const veiculoRows = buildInfoRows(window.baseResultData.veiculo, {
                'placa': 'Placa',
                'renavam': 'RENAVAM',
                'chassi': 'Chassi',
                'tipo': 'Tipo',
                'procedencia': 'Procedência',
                'combustivel': 'Combustível',
                'cor': 'Cor',
                'marca': 'Marca',
                'categoria': 'Categoria',
                'ano_fabricacao': 'Ano fabricação',
                'ano_modelo': 'Ano modelo',
                'municipio': 'Município',
            });
            if (veiculoRows) {
                html += `<div class="section-card"><div class="section-title">Veículo</div>${veiculoRows}</div>`;
            }

            if (window.baseResultData.proprietario) {
                const proprietarioRows = buildInfoRows(window.baseResultData.proprietario, {
                    'nome': 'Nome',
                });
                if (proprietarioRows) {
                    html += `<div class="section-card"><div class="section-title">Proprietário</div>${proprietarioRows}</div>`;
                }
            }

            if (window.baseResultData.crv_crlv_atualizacao) {
                const crvRows = buildInfoRows(window.baseResultData.crv_crlv_atualizacao, {
                    'exercicio_licenciamento': 'Exercício licenciamento',
                    'data_licenciamento': 'Data licenciamento',
                });
                if (crvRows) {
                    html += `<div class="section-card"><div class="section-title">CRV / CRLV</div>${crvRows}</div>`;
                }
            }

            modalBody.innerHTML = html || '<p style="text-align: center; color: #64748B;">Nenhuma informação disponível.</p>';
            modal.classList.add('show');
        }

        function showGravameDetails() {
            if (!window.baseResultData) return;

            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');

            modalTitle.textContent = 'Gravame';

            let html = '';

            if (window.baseResultData.gravames) {
                const gravamesRows = buildInfoRows(window.baseResultData.gravames, {
                    'restricao_financeira': 'Restrição financeira',
                    'nome_agente': 'Nome do agente',
                    'arrendatario': 'Arrendatário',
                    'cnpj_cpf_financiado': 'CNPJ/CPF financiado',
                });
                if (gravamesRows) {
                    html += `<div class="section-card"><div class="section-title">Gravame atual</div>${gravamesRows}</div>`;
                }
            }

            if (window.baseResultData.intencao_gravame) {
                const intencaoRows = buildInfoRows(window.baseResultData.intencao_gravame, {
                    'restricao_financeira': 'Restrição financeira',
                    'agente_financeiro': 'Agente financeiro',
                    'nome_financiado': 'Nome financiado',
                    'cnpj_cpf': 'CNPJ/CPF',
                    'data_inclusao': 'Data inclusão',
                });
                if (intencaoRows) {
                    html += `<div class="section-card"><div class="section-title">Intenção de gravame</div>${intencaoRows}</div>`;
                }
            }

            modalBody.innerHTML = html || '<p style="text-align: center; color: #64748B;">Nenhuma informação de gravame encontrada.</p>';
            modal.classList.add('show');
        }

        function parseCurrencyValue(raw) {
            if (raw == null) {
                return 0;
            }
            const text = String(raw).trim();
            if (text === '') {
                return 0;
            }

            const sanitized = text.replace(/[^\d.,-]/g, '');
            if (sanitized === '') {
                return 0;
            }

            const lastDot = sanitized.lastIndexOf('.');
            const lastComma = sanitized.lastIndexOf(',');
            let decimalIndex = -1;
            if (lastDot >= 0 || lastComma >= 0) {
                const candidateIndex = lastDot > lastComma ? lastDot : lastComma;
                const digitsAfterSeparator = sanitized.length - candidateIndex - 1;
                if (digitsAfterSeparator <= 2) {
                    decimalIndex = candidateIndex;
                }
            }

            let buffer = '';
            for (let i = 0; i < sanitized.length; i++) {
                const char = sanitized[i];
                if (char === '.' || char === ',') {
                    if (i === decimalIndex) {
                        buffer += '.';
                    }
                    continue;
                }
                if ((char === '-' || char === '+') && buffer === '') {
                    buffer += char;
                    continue;
                }
                buffer += char;
            }

            if (buffer === '' || buffer === '-' || buffer === '+') {
                return 0;
            }

            return Number(buffer) || 0;
        }

        function showDebitosDetails() {
            if (!window.baseResultData || !window.baseResultData.debitos_multas) {
                const modal = document.getElementById('detailModal');
                const modalTitle = document.getElementById('modalTitle');
                const modalBody = document.getElementById('modalBody');
                modalTitle.textContent = 'Multas e débitos';
                modalBody.innerHTML = '<p style="text-align: center; color: #64748B;">Nenhum débito informado.</p>';
                modal.classList.add('show');
                return;
            }

            const debitos = window.baseResultData.debitos_multas;
            const labels = {
                'dersa': 'DERSA',
                'der': 'DER',
                'detran': 'DETRAN',
                'cetesb': 'CETESB',
                'renainf': 'RENAINF',
                'municipais': 'Municipais',
                'prf': 'Polícia Rodoviária Federal',
                'ipva': 'IPVA',
            };

            let total = 0;
            let html = '';

            for (const [key, label] of Object.entries(labels)) {
                const value = debitos[key];
                if (value != null) {
                    const numValue = parseCurrencyValue(value);
                    total += numValue;
                    const formatted = typeof value === 'number' ? value.toFixed(2).replace('.', ',') : value;
                    html += `
                        <div class="info-row">
                            <div class="info-label">${label}</div>
                            <div class="info-value">R$ ${formatted}</div>
                        </div>
                    `;
                }
            }

            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');

            modalTitle.textContent = 'Multas e débitos';
            modalBody.innerHTML = `
                <div class="section-card">
                    <div class="info-row">
                        <div class="info-label">Total em aberto</div>
                        <div class="info-value" style="font-size: 24px; color: ${total > 0 ? '#EF4444' : '#4CAF50'};">R$ ${total.toFixed(2).replace('.', ',')}</div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title">Detalhamento</div>
                    ${html || '<p style="text-align: center; color: #64748B;">Nenhum débito informado.</p>'}
                </div>
            `;
            modal.classList.add('show');
        }

        function showRestricoesDetails() {
            if (!window.baseResultData || !window.baseResultData.restricoes) {
                const modal = document.getElementById('detailModal');
                const modalTitle = document.getElementById('modalTitle');
                const modalBody = document.getElementById('modalBody');
                modalTitle.textContent = 'Restrições';
                modalBody.innerHTML = '<p style="text-align: center; color: #64748B;">Nenhuma restrição informada.</p>';
                modal.classList.add('show');
                return;
            }

            const restricoesRows = buildInfoRows(window.baseResultData.restricoes, {
                'furto': 'Furto',
                'bloqueio_guincho': 'Bloqueio de guincho',
                'administrativas': 'Administrativas',
                'judicial': 'Judicial',
                'tributaria': 'Tributária',
                'renajud': 'RENAJUD',
                'inspecao_ambiental': 'Inspeção ambiental',
            });

            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');

            modalTitle.textContent = 'Restrições';
            modalBody.innerHTML = restricoesRows
                ? `<div class="section-card">${restricoesRows}</div>`
                : '<p style="text-align: center; color: #64748B;">Nenhuma restrição informada.</p>';
            modal.classList.add('show');
        }

        function showComunicacaoDetails() {
            if (!window.baseResultData) return;

            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');

            modalTitle.textContent = 'Comunicações de venda';

            let html = '';

            const comunicacao = window.baseResultData.comunicacao_vendas;
            const comunicacaoNormalized = comunicacao
                ? {
                    ...comunicacao,
                    tipo_documento_comprador: comunicacao.tipo_documento_comprador ?? comunicacao.tipo_doc_comprador,
                    documento_comprador: comunicacao.documento_comprador ?? comunicacao.cnpj_cpf_comprador,
                }
                : null;

            if (comunicacaoNormalized) {
                const comunicacaoRows = buildInfoRows(comunicacaoNormalized, {
                    'status': 'Status',
                    'inclusao': 'Inclusão',
                    'tipo_documento_comprador': 'Tipo documento comprador',
                    'documento_comprador': 'CNPJ/CPF comprador',
                    'origem': 'Origem',
                });
                if (comunicacaoRows) {
                    html += `<div class="section-card"><div class="section-title">Comunicação</div>${comunicacaoRows}</div>`;
                }
            }

            if (comunicacaoNormalized && comunicacaoNormalized.datas) {
                const datasRows = buildInfoRows(comunicacaoNormalized.datas, {
                    'venda': 'Venda',
                    'nota_fiscal': 'Nota fiscal',
                    'protocolo_detran': 'Protocolo DETRAN',
                });
                if (datasRows) {
                    html += `<div class="section-card"><div class="section-title">Datas</div>${datasRows}</div>`;
                }
            }

            modalBody.innerHTML = html || '<p style="text-align: center; color: #64748B;">Nenhuma comunicação de venda registrada.</p>';
            modal.classList.add('show');
        }

        function closeModal() {
            document.getElementById('detailModal').classList.remove('show');
        }

        document.getElementById('detailModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        function loadResultFromStorage() {
            const stored = sessionStorage.getItem('base_estadual_result');
            if (!stored) {
                return null;
            }
            try {
                return JSON.parse(stored);
            } catch (error) {
                return null;
            }
        }

        function showEmptyResult() {
            baseResultContent.innerHTML = `
                <div class="result-card">
                    <div style="padding: 16px; text-align: center;">
                        <p style="font-size: 16px; color: #1E293B;">Nenhum resultado disponível.</p>
                    </div>
                </div>
            `;
        }

        baseResultBack.addEventListener('click', () => {
            sessionStorage.removeItem('base_estadual_result');
            window.location.href = '/home';
        });

        if (checkAuth()) {
            const resultData = loadResultFromStorage();
            if (resultData) {
                displayBaseEstadualResult(resultData);
            } else {
                showEmptyResult();
            }
        }
    </script>
</body>
</html>
