<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Consulta RENAINF - LL Despachante</title>
    <style>
        :root {
            color-scheme: light;
            --primary: #0047AB;
            --bg: #F8FAFC;
            --text-strong: #1E293B;
            --text-muted: #64748B;
            --card-border: rgba(226, 232, 240, 0.9);
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

        .renainf-header {
            background: var(--primary);
            color: #fff;
            border-radius: 0 0 32px 32px;
            padding: 20px;
        }

        .renainf-header-inner {
            max-width: 720px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .renainf-title {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .renainf-title-text {
            font-size: 20px;
            font-weight: 700;
        }

        .renainf-title-subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
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

        .header-spinner {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .renainf-body {
            max-width: 720px;
            margin: 0 auto;
            padding: 20px 20px 32px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .renainf-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
            border: 1px solid var(--card-border);
            padding: 22px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .renainf-card-title {
            font-size: 16px;
            font-weight: 700;
        }

        .renainf-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .renainf-tile {
            flex: 1;
            min-width: 140px;
            background: #F8FAFC;
            border-radius: 16px;
            padding: 12px 14px;
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .renainf-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .renainf-value {
            font-size: 16px;
            font-weight: 600;
            margin-top: 6px;
        }

        .renainf-chip {
            font-size: 12px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 14px;
            background: rgba(0, 71, 171, 0.12);
            color: var(--primary);
            align-self: flex-start;
        }

        .renainf-section-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.85);
            padding: 18px;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .renainf-section-title {
            font-size: 15px;
            font-weight: 700;
        }

        .renainf-occurrence,
        .renainf-infraction {
            background: #F8FAFC;
            border-radius: 16px;
            padding: 14px;
            border: 1px solid rgba(226, 232, 240, 0.7);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            font-size: 13px;
        }

        .renainf-detail-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .renainf-detail-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .renainf-json-section {
            background: #fff;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            padding: 20px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .renainf-json-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .renainf-json-pre {
            background: #F8FAFC;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            padding: 16px;
            font-size: 13px;
            color: var(--text-muted);
            overflow-x: auto;
            white-space: pre-wrap;
        }

        @media (min-width: 768px) {
            .renainf-body {
                padding: 28px 20px 36px;
            }
        }
    </style>
</head>
<body>
    <div class="renainf-header">
        <div class="renainf-header-inner">
            <button class="icon-button" type="button" id="renainfBackBtn" title="Voltar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <div class="renainf-title">
                <span class="renainf-title-text">RENAINF</span>
                <span class="renainf-title-subtitle" id="renainfSubtitle"></span>
            </div>
            <div class="header-actions">
                <button class="icon-button" type="button" id="renainfCopyBtn" title="Copiar resultado">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                        <rect x="2" y="2" width="13" height="13" rx="2"></rect>
                    </svg>
                </button>
                <button class="icon-button" type="button" id="renainfPdfBtn" title="Emitir PDF" onclick="emitRenainfPdf()">
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

    <main class="renainf-body">
        <section id="renainfSummary"></section>
        <section id="renainfStats"></section>
        <section id="renainfConsulta"></section>
        <section id="renainfOccurrences"></section>
        <section id="renainfInfractions"></section>
        <section class="renainf-json-section">
            <div class="renainf-json-title">Resposta completa</div>
            <pre class="renainf-json-pre" id="renainfJson"></pre>
        </section>
    </main>

    <script>
        const authToken = localStorage.getItem('auth_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const renainfSummary = document.getElementById('renainfSummary');
        const renainfStats = document.getElementById('renainfStats');
        const renainfConsulta = document.getElementById('renainfConsulta');
        const renainfOccurrences = document.getElementById('renainfOccurrences');
        const renainfInfractions = document.getElementById('renainfInfractions');
        const renainfJson = document.getElementById('renainfJson');
        const renainfSubtitle = document.getElementById('renainfSubtitle');
        const copyBtn = document.getElementById('renainfCopyBtn');
        const pdfBtn = document.getElementById('renainfPdfBtn');
        const backBtn = document.getElementById('renainfBackBtn');

        let renainfResultData = null;
        let renainfMeta = null;
        let isPdfGenerating = false;

        function checkAuth() {
            if (!authToken) {
                window.location.href = '/login';
                return false;
            }
            return true;
        }

        function loadResultFromStorage() {
            const stored = sessionStorage.getItem('renainf_result');
            if (!stored) return null;
            try {
                return JSON.parse(stored);
            } catch (error) {
                return null;
            }
        }

        function loadMetaFromStorage() {
            const stored = sessionStorage.getItem('renainf_meta');
            if (!stored) return null;
            try {
                return JSON.parse(stored);
            } catch (error) {
                return null;
            }
        }

        function formatSubtitle(result) {
            const plate = result?.plate || renainfMeta?.plate;
            if (!plate) return '';
            return `Placa: ${plate}`;
        }

        function toNumber(value) {
            if (value === null || value === undefined) return NaN;
            if (typeof value === 'number') return value;
            const sanitized = value.toString().replace(/[^0-9\-,\.]/g, '').replace(',', '.');
            const parsed = parseFloat(sanitized);
            return Number.isNaN(parsed) ? NaN : parsed;
        }

        function formatCurrency(value) {
            const number = toNumber(value);
            if (Number.isNaN(number)) return '—';
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL',
            }).format(number);
        }

        function formatPeriod(start, end) {
            if (!start || !end) return '—';
            return `${formatDateDisplay(start)} • ${formatDateDisplay(end)}`;
        }

        function formatDateDisplay(value) {
            if (!value) return '—';
            if (value.includes('/')) return value;
            const parts = value.split('-');
            if (parts.length !== 3) return value;
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }

        function getSummary(result) {
            const summary = result?.summary || result?.resumo || result?.totals || {};
            const fromInfractions = Array.isArray(result?.infractions);
            const infractionsCount = fromInfractions ? result.infractions.length : 0;
            const totalInfractions = summary.totalInfractions ?? summary.total_infractions ?? summary.total ?? infractionsCount;
            const totalValue = summary.totalValue ?? summary.total_value ?? summary.valor_total ?? summary.total ?? 0;
            const openValue = summary.openValue ?? summary.open_value ?? summary.valor_em_aberto ?? 0;
            const lastUpdated = summary.lastUpdatedLabel || summary.last_updated_at || result?.sourceGeneratedAt || '—';
            return { totalInfractions, totalValue, openValue, lastUpdated };
        }

        function deductionOccurrences(result) {
            if (Array.isArray(result?.occurrences) && result.occurrences.length) {
                return result.occurrences;
            }
            const root = result?.renainf || {};
            if (Array.isArray(root?.ocorrencias) && root.ocorrencias.length) {
                return root.ocorrencias;
            }
            return [];
        }

        function deductionInfractions(result) {
            if (Array.isArray(result?.infractions) && result.infractions.length) {
                return result.infractions;
            }
            const aliases = ['infracoes', 'items', 'itens', 'infractions_list', 'resultado'];
            for (const key of aliases) {
                if (Array.isArray(result?.[key]) && result[key].length) {
                    return result[key];
                }
            }
            return [];
        }

        function renderSummary(result) {
            const plate = result?.plate || '—';
            const uf = result?.uf || '—';
            const statusLabel = result?.statusLabel || result?.status || '—';
            const period = formatPeriod(renainfMeta?.startDate, renainfMeta?.endDate);
            return `
                <div class="renainf-card">
                    <div class="renainf-card-title">Resumo da consulta</div>
                    <div class="renainf-row">
                        <div class="renainf-tile">
                            <div class="renainf-label">Placa</div>
                            <div class="renainf-value">${plate}</div>
                        </div>
                        <div class="renainf-tile">
                            <div class="renainf-label">UF pesquisada</div>
                            <div class="renainf-value">${uf}</div>
                        </div>
                    </div>
                    <div class="renainf-row">
                        <div class="renainf-tile">
                            <div class="renainf-label">Status da multa</div>
                            <div class="renainf-value">${statusLabel}</div>
                        </div>
                        <div class="renainf-tile">
                            <div class="renainf-label">Período</div>
                            <div class="renainf-value">${period}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderStats(summary) {
            return `
                <div class="renainf-card">
                    <div class="renainf-card-title">Resumo financeiro</div>
                    <div class="renainf-row">
                        <div class="renainf-tile">
                            <div class="renainf-label">Total de infrações</div>
                            <div class="renainf-value">${summary.totalInfractions}</div>
                        </div>
                        <div class="renainf-tile">
                            <div class="renainf-label">Valor total</div>
                            <div class="renainf-value">${formatCurrency(summary.totalValue)}</div>
                        </div>
                    </div>
                    <div class="renainf-row">
                        <div class="renainf-tile">
                            <div class="renainf-label">Valor em aberto</div>
                            <div class="renainf-value">${formatCurrency(summary.openValue)}</div>
                        </div>
                        <div class="renainf-tile">
                            <div class="renainf-label">Última atualização</div>
                            <div class="renainf-value">${summary.lastUpdated}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderConsulta(result) {
            const consulta = result?.consulta || {}; 
            const fields = [];
            if (consulta.placa) fields.push({ label: 'Placa consultada', value: consulta.placa });
            if (consulta.uf_emplacamento) fields.push({ label: 'UF de emplacamento', value: consulta.uf_emplacamento });
            if (consulta.indicador_exigibilidade) fields.push({ label: 'Indicador de exigibilidade', value: consulta.indicador_exigibilidade });
            if (!fields.length) return '';

            const rows = fields
                .map(item => `
                    <div class="renainf-detail-row">
                        <span class="renainf-detail-label">${item.label}</span>
                        <span class="renainf-detail-value">${item.value}</span>
                    </div>
                `)
                .join('');

            return `
                <div class="renainf-section-card">
                    <div class="renainf-section-title">Dados da consulta</div>
                    ${rows}
                </div>
            `;
        }

        function renderFonte(result) {
            const title = result?.sourceTitle || result?.fonte?.titulo || result?.fonte_title;
            const generated = result?.sourceGeneratedAt || result?.fonte?.gerado_em || result?.fonte_generated_at;
            if (!title && !generated) return '';
            return `
                <div class="renainf-section-card">
                    <div class="renainf-section-title">Fonte</div>
                    ${title ? `<p class="renainf-detail-label">Sistema</p><p class="renainf-detail-value">${title}</p>` : ''}
                    ${generated ? `<p class="renainf-detail-label">Gerado em</p><p class="renainf-detail-value">${generated}</p>` : ''}
                </div>
            `;
        }

        function renderOccurrences(occurrences) {
            if (!occurrences.length) return '';
            const items = occurrences
                .map((occurrence) => `
                    <div class="renainf-occurrence">
                        <div>
                            <div class="renainf-detail-label">Orgão autuador</div>
                            <div class="renainf-detail-value">${occurrence.orgao_autuador || occurrence.orgao}</div>
                        </div>
                        <div>
                            <div class="renainf-detail-label">Auto de infração</div>
                            <div class="renainf-detail-value">${occurrence.auto_infracao || occurrence.auto}</div>
                        </div>
                        <div>
                            <div class="renainf-detail-label">Infração</div>
                            <div class="renainf-detail-value">${occurrence.infracao || occurrence.codigo_infracao}</div>
                        </div>
                        <div>
                            <div class="renainf-detail-label">Data</div>
                            <div class="renainf-detail-value">${occurrence.data_infracao || occurrence.data}</div>
                        </div>
                        <div>
                            <div class="renainf-detail-label">Exigibilidade</div>
                            <div class="renainf-detail-value">${occurrence.exigibilidade || occurrence.indicador_exigibilidade || '—'}</div>
                        </div>
                    </div>
                `)
                .join('');

            return `
                <div class="renainf-section-card">
                    <div class="renainf-section-title">Ocorrências</div>
                    ${items}
                </div>
            `;
        }

        function renderInfractions(infractions) {
            if (!infractions.length) return '';
            const items = infractions
                .map((infraction) => `
                    <div class="renainf-infraction">
                        <div>
                            <div class="renainf-detail-label">Auto</div>
                            <div class="renainf-detail-value">${infraction.auto_infracao || infraction.code || '—'}</div>
                        </div>
                        <div>
                            <div class="renainf-detail-label">Descrição</div>
                            <div class="renainf-detail-value">${infraction.description || infraction.descricao || '—'}</div>
                        </div>
                        <div>
                            <div class="renainf-detail-label">Valor</div>
                            <div class="renainf-detail-value">${formatCurrency(infraction.amount ?? infraction.valor ?? infraction.valor_infracao)}</div>
                        </div>
                        <div>
                            <div class="renainf-detail-label">Status</div>
                            <div class="renainf-detail-value">${infraction.status || infraction.situacao || '—'}</div>
                        </div>
                        <div>
                            <div class="renainf-detail-label">Data</div>
                            <div class="renainf-detail-value">${infraction.date || infraction.data || infraction.data_emissao || '—'}</div>
                        </div>
                    </div>
                `)
                .join('');

            return `
                <div class="renainf-section-card">
                    <div class="renainf-section-title">Infrações</div>
                    ${items}
                </div>
            `;
        }

        function setPdfLoading(isLoading) {
            if (!pdfBtn) return;
            pdfBtn.classList.toggle('is-loading', isLoading);
            pdfBtn.disabled = isLoading;
        }

        async function emitRenainfPdf() {
            if (isPdfGenerating) return;
            if (!renainfResultData) {
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
                if (authToken) headers['Authorization'] = `Bearer ${authToken}`;
                if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

                const response = await fetch('/api/renainf/pdf', {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({
                        payload: renainfResultData,
                        meta: renainfMeta,
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
                const placaValue = renainfMeta?.plate || renainfResultData?.plate || 'consulta';
                const sanitized = placaValue.replace(/[^A-Za-z0-9]/g, '') || 'consulta';
                const filename = `pesquisa_renainf_${sanitized}.pdf`;
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

        function renderEmpty() {
            renainfSummary.innerHTML = '<p style="color:#94A3B8; text-align:center;">Nenhum resultado disponível.</p>';
            renainfStats.innerHTML = '';
            renainfConsulta.innerHTML = '';
            renainfOccurrences.innerHTML = '';
            renainfInfractions.innerHTML = '';
            renainfJson.textContent = 'Nenhum resultado disponível.';
        }

        function copyResult() {
            if (!renainfResultData) return;
            try {
                navigator.clipboard.writeText(JSON.stringify(renainfResultData, null, 2));
                alert('Dados copiados para a área de transferência.');
            } catch (error) {
                alert('Não foi possível copiar o resultado.');
            }
        }

        if (checkAuth()) {
            renainfResultData = loadResultFromStorage();
            renainfMeta = loadMetaFromStorage();
            renainfSubtitle.textContent = formatSubtitle(renainfResultData);
            if (renainfResultData) {
                renainfSummary.innerHTML = renderSummary(renainfResultData);
                renainfStats.innerHTML = renderStats(getSummary(renainfResultData));
                renainfConsulta.innerHTML = renderConsulta(renainfResultData);
                renainfOccurrences.innerHTML = renderOccurrences(deductionOccurrences(renainfResultData));
                renainfInfractions.innerHTML = renderInfractions(deductionInfractions(renainfResultData));
                renainfJson.textContent = JSON.stringify(renainfResultData, null, 2);
            } else {
                renderEmpty();
            }
        }

        backBtn?.addEventListener('click', () => {
            sessionStorage.removeItem('renainf_result');
            sessionStorage.removeItem('renainf_meta');
            window.location.href = '/home';
        });

        copyBtn?.addEventListener('click', copyResult);
    </script>
</body>
</html>
