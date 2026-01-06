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
        .renainf-plate-card {
            background: #fff;
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
            padding: 20px;
            display: flex;
            gap: 16px;
            align-items: center;
        }
        .renainf-plate-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background: rgba(15, 23, 42, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .renainf-plate-icon svg {
            width: 32px;
            height: 32px;
            stroke: var(--primary);
        }
        .renainf-plate-content {
            flex: 1;
        }
        .renainf-plate-title {
            font-size: 22px;
            font-weight: 700;
            color: #1D1C3F;
        }
        .renainf-plate-subtitle {
            font-size: 14px;
            color: #4B5563;
        }
        .renainf-highlight-row {
            font-size: 14px;
            color: #1D1C3F;
            font-weight: 600;
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

        .renainf-occurrences-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            color: #1D1E33;
        }

        .renainf-occurrences-table th,
        .renainf-occurrences-table td {
            padding: 10px 8px;
            text-align: left;
        }

        .renainf-occurrences-table thead {
            font-weight: 700;
            color: #667085;
            text-transform: none;
        }

        .renainf-occurrences-table tbody tr {
            border-bottom: 1px solid rgba(226, 232, 240, 0.7);
        }

        .renainf-occurrences-table tbody tr:last-child {
            border-bottom: none;
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
        <section id="renainfPlateCard"></section>
        <section id="renainfConsultaCard"></section>
        <section id="renainfFonteCard"></section>
        <section class="renainf-section-card">
            <div class="renainf-section-title">Ocorrências encontradas</div>
            <div class="renainf-occurrences-table" id="renainfOccurrencesTable"></div>
        </section>
    </main>

    <script>
        const authToken = localStorage.getItem('auth_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const renainfPlateCard = document.getElementById('renainfPlateCard');
        const renainfConsultaCard = document.getElementById('renainfConsultaCard');
        const renainfFonteCard = document.getElementById('renainfFonteCard');
        const renainfOccurrencesTable = document.getElementById('renainfOccurrencesTable');
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

        function renderPlateCard(result) {
            const plate = result?.plate || '—';
            const subtitle = result?.sourceTitle || 'Consulta RENAINF';
            const subText = result?.sourceGeneratedAt
                ? `Gerado em ${result.sourceGeneratedAt}`
                : renainfMeta?.startDate
                    ? `Período pesquisado ${formatDateDisplay(renainfMeta.startDate)} • ${formatDateDisplay(renainfMeta.endDate)}`
                    : '';
            return `
                <div class="renainf-plate-card">
                    <div class="renainf-plate-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="7" width="18" height="10" rx="2"></rect>
                            <path d="M3 17h18"></path>
                            <path d="M7 9V7"></path>
                            <path d="M17 9V7"></path>
                        </svg>
                    </div>
                    <div class="renainf-plate-content">
                        <div class="renainf-plate-title">${plate}</div>
                        <div class="renainf-plate-subtitle">${subtitle}</div>
                        ${subText ? `<div class="renainf-highlight-row">${subText}</div>` : ''}
                    </div>
                </div>
            `;
        }

        function renderConsultaCard(result) {
            const consulta = result?.consulta || {};
            const status = result?.statusLabel || result?.status || '—';
            const indicator = consulta.indicador_exigibilidade || status;
            const plate = consulta.placa || result?.plate || '—';
            const ufEmplac = consulta.uf_emplacamento || result?.uf || '—';
            const ufPesquisa = result?.uf || '—';
            const period = formatPeriod(renainfMeta?.startDate, renainfMeta?.endDate);
            const minimap = [
                { label: 'Placa consultada', value: plate },
                { label: 'UF de emplacamento', value: ufEmplac },
                { label: 'Indicador de exigibilidade', value: indicator },
                { label: 'UF pesquisada', value: ufPesquisa },
                { label: 'Ocorrências encontradas', value: deductionOccurrences(result).length },
            ];
            const rows = minimap
                .map((item) => `
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

        function renderFonteCard(result) {
            const title = result?.sourceTitle || result?.fonte?.titulo || result?.fonte_title;
            const generated = result?.sourceGeneratedAt || result?.fonte?.gerado_em || result?.fonte_generated_at;
            if (!title && !generated) return '';
            return `
                <div class="renainf-section-card">
                    <div class="renainf-section-title">Fonte</div>
                    ${title ? `<div class="renainf-detail-row"><span class="renainf-detail-label">Sistema</span><span class="renainf-detail-value">${title}</span></div>` : ''}
                    ${generated ? `<div class="renainf-detail-row"><span class="renainf-detail-label">Gerado em</span><span class="renainf-detail-value">${generated}</span></div>` : ''}
                </div>
            `;
        }

        function renderOccurrencesTable(occurrences) {
            if (!occurrences.length) {
                return '<p style="color:#94A3B8; text-align:center;">Nenhuma ocorrência encontrada.</p>';
            }
            const rows = occurrences
                .map((occurrence) => {
                    const orgao = occurrence.orgao_autuador || occurrence.orgao || '—';
                    const auto = occurrence.auto_infracao || occurrence.auto || '—';
                    const infracao = occurrence.infracao || occurrence.codigo_infracao || '—';
                    const data = occurrence.data_infracao || occurrence.data || '—';
                    const exig = occurrence.exigibilidade || occurrence.indicador_exigibilidade || '—';
                    return `
                        <tr>
                            <td>${orgao}</td>
                            <td>${auto}</td>
                            <td>${infracao}</td>
                            <td>${formatDateDisplay(data)}</td>
                            <td>${exig}</td>
                        </tr>
                    `;
                })
                .join('');
            return `
                <table>
                    <thead>
                        <tr>
                            <th>Orgão autuador</th>
                            <th>Auto de infração</th>
                            <th>Infração</th>
                            <th>Data da infração</th>
                            <th>Exigibilidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
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
