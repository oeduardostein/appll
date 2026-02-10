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
            --primary: #0B4FCA;
            --bg: #F7F9FC;
            --text-strong: #1E293B;
            --text-muted: #6B7280;
            --card-border: rgba(226, 232, 240, 0.9);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

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
            padding: 20px 18px 22px;
            box-shadow: 0 14px 32px rgba(11, 79, 202, 0.35);
        }

        .renainf-header-inner {
            max-width: 820px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .renainf-title { flex: 1; display: flex; flex-direction: column; gap: 6px; }
        .renainf-title-text { font-size: 22px; font-weight: 800; letter-spacing: 0.2px; }
        .renainf-title-subtitle { font-size: 14px; color: rgba(255,255,255,0.88); }

        .header-actions { display: flex; align-items: center; gap: 10px; }

        .icon-button {
            width: 44px; height: 44px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.32);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .icon-button svg { width: 20px; height: 20px; }
        .icon-button:hover { background: rgba(255, 255, 255, 0.18); transform: translateY(-1px); }
        .icon-button.is-loading { opacity: 0.7; cursor: not-allowed; }
        .icon-button.is-loading svg { display: none; }

        .header-spinner {
            width: 18px; height: 18px; border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .renainf-body {
            max-width: 820px;
            margin: 0 auto;
            padding: 22px 18px 36px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .card {
            background: #fff;
            border-radius: 24px;
            border: 1px solid var(--card-border);
            box-shadow: 0 16px 26px rgba(15, 23, 42, 0.08);
            padding: 22px 22px 20px;
        }

        .card + .card { margin-top: 4px; }
        .card-title { font-size: 17px; font-weight: 800; margin-bottom: 12px; letter-spacing: 0.2px; }
        .muted { color: var(--text-muted); }

        .hero-card {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .hero-top { display: flex; gap: 14px; align-items: center; }
        .hero-icon {
            width: 54px; height: 54px;
            border-radius: 18px;
            background: rgba(11,79,202,0.12);
            display: grid; place-items: center;
        }
        .hero-icon svg { width: 28px; height: 28px; color: var(--primary); }
        .hero-title { font-size: 22px; font-weight: 800; }
        .hero-subtitle { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
        .divider { height: 1px; background: rgba(226, 232, 240, 0.9); margin: 4px 0 6px; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px 16px;
            margin-top: 4px;
        }
        .info-item { display: flex; flex-direction: column; gap: 4px; }
        .label { font-size: 13px; font-weight: 600; color: var(--text-muted); }
        .value { font-size: 16px; font-weight: 700; color: var(--text-strong); }

        .table-card { padding: 16px 16px 10px; }
        .table-head { font-size: 15px; font-weight: 800; margin: 0 6px 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 10px; text-align: left; font-size: 14px; }
        th { color: var(--text-muted); font-weight: 700; }
        tr + tr td { border-top: 1px solid rgba(226,232,240,0.8); }

        .empty {
            text-align: center;
            color: var(--text-muted);
            padding: 18px 0;
            font-size: 14px;
        }

        @media (max-width: 520px) {
            .renainf-header { border-radius: 0 0 26px 26px; }
            .card { border-radius: 20px; }
            .hero-title { font-size: 20px; }
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
                <button class="icon-button" type="button" id="renainfCopyBtn" title="Compartilhar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7"></path>
                        <path d="M16 6l-4-4-4 4"></path>
                        <path d="M12 2v13"></path>
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
        <section id="renainfHero"></section>
        <section id="renainfConsulta"></section>
        <section id="renainfFonte"></section>
        <section id="renainfOccurrences"></section>
    </main>

    <script>
        function getAuthToken() {
            return sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
        }

        const authToken = getAuthToken();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const renainfHero = document.getElementById('renainfHero');
        const renainfConsulta = document.getElementById('renainfConsulta');
        const renainfFonte = document.getElementById('renainfFonte');
        const renainfOccurrences = document.getElementById('renainfOccurrences');
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
            const plate = result?.consulta?.placa || result?.plate || renainfMeta?.plate;
            if (!plate) return '';
            return `Placa: ${plate}`;
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

        function buildInfoGrid(items) {
            if (!items.length) return '';
            return `<div class="info-grid">${items.map(item => `
                <div class="info-item">
                    <span class="label">${item.label}</span>
                    <span class="value">${item.value ?? '—'}</span>
                </div>
            `).join('')}</div>`;
        }

        function renderHero(result, meta, occurrences) {
            const plate = result?.consulta?.placa || result?.plate || meta?.plate || '—';
            const source = result?.fonte?.titulo || 'eCRVsp - DETRAN';
            const period = formatPeriod(meta?.startDate, meta?.endDate);
            const ufPesquisada = (meta?.uf || result?.consulta?.uf_pesquisada || '').toUpperCase() || '—';
            const ufEmplacamento = result?.consulta?.uf_emplacamento || '—';
            const exigibilidade = result?.consulta?.indicador_exigibilidade || meta?.statusLabel || '—';
            const ocorrencias = result?.renainf?.quantidade_ocorrencias || occurrences.length || '—';

            const info = buildInfoGrid([
                { label: 'Período pesquisado', value: period },
                { label: 'UF pesquisada', value: ufPesquisada },
                { label: 'UF de emplacamento', value: ufEmplacamento },
                { label: 'Indicador de exigibilidade', value: exigibilidade },
                { label: 'Ocorrências encontradas', value: ocorrencias },
            ]);

            return `
                <section class="card hero-card">
                    <div class="hero-top">
                        <div class="hero-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="7" rx="2"></rect>
                                <path d="M7 11V7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v4"></path>
                                <circle cx="7.5" cy="18" r="1.5"></circle>
                                <circle cx="16.5" cy="18" r="1.5"></circle>
                            </svg>
                        </div>
                        <div>
                            <div class="hero-title">${plate}</div>
                            <div class="hero-subtitle">${source}</div>
                        </div>
                    </div>
                    <div class="divider"></div>
                    ${info}
                </section>
            `;
        }

        function renderConsultaCard(result, meta, occurrences) {
            const plate = result?.consulta?.placa || result?.plate || meta?.plate || '—';
            const ufPesq = (meta?.uf || result?.consulta?.uf_pesquisada || '').toUpperCase() || '—';
            const ufEmpl = result?.consulta?.uf_emplacamento || '—';
            const exigibilidade = result?.consulta?.indicador_exigibilidade || meta?.statusLabel || '—';
            const statusFiltro = meta?.statusLabel || '—';
            const qtd = result?.renainf?.quantidade_ocorrencias || occurrences.length || '—';

            const info = buildInfoGrid([
                { label: 'Placa consultada', value: plate },
                { label: 'UF de emplacamento', value: ufEmpl },
                { label: 'Indicador de exigibilidade', value: exigibilidade },
                { label: 'UF pesquisada', value: ufPesq },
                { label: 'Filtro de status', value: statusFiltro },
                { label: 'Quantidade de ocorrências', value: qtd },
            ]);

            return `
                <section class="card">
                    <div class="card-title">Dados da consulta</div>
                    ${info}
                </section>
            `;
        }

        function renderFonteCard(result) {
            const title = result?.fonte?.titulo || result?.sourceTitle;
            const generated = result?.fonte?.gerado_em || result?.sourceGeneratedAt;
            if (!title && !generated) return '';
            const info = buildInfoGrid([
                { label: 'Sistema', value: title || '—' },
                { label: 'Gerado em', value: generated || '—' },
            ]);
            return `
                <section class="card">
                    <div class="card-title" style="color: var(--primary);">Fonte</div>
                    ${info}
                </section>
            `;
        }

        function renderOccurrencesTable(occurrences) {
            if (!occurrences.length) {
                return `<section class="card table-card"><div class="table-head">Ocorrências encontradas</div><div class="empty">Nenhuma ocorrência encontrada.</div></section>`;
            }

            const rows = occurrences.map(item => `
                <tr>
                    <td>${item.orgao_autuador || item.orgao || '—'}</td>
                    <td>${item.auto_infracao || item.auto || '—'}</td>
                    <td>${item.infracao || item.codigo_infracao || '—'}</td>
                    <td>${formatDateDisplay(item.data_infracao || item.data)}</td>
                    <td>${item.exigibilidade || item.indicador_exigibilidade || '—'}</td>
                </tr>
            `).join('');

            return `
                <section class="card table-card">
                    <div class="table-head">Ocorrências encontradas</div>
                    <table>
                        <thead>
                            <tr>
                                <th>Órgão autuador</th>
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
                </section>
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
            renainfHero.innerHTML = '<div class="card"><div class="empty">Nenhum resultado disponível.</div></div>';
            renainfConsulta.innerHTML = '';
            renainfFonte.innerHTML = '';
            renainfOccurrences.innerHTML = '';
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
            const occurrences = deductionOccurrences(renainfResultData);
            renainfSubtitle.textContent = formatSubtitle(renainfResultData);
            if (renainfResultData) {
                renainfHero.innerHTML = renderHero(renainfResultData, renainfMeta, occurrences);
                renainfConsulta.innerHTML = renderConsultaCard(renainfResultData, renainfMeta, occurrences);
                renainfFonte.innerHTML = renderFonteCard(renainfResultData);
                renainfOccurrences.innerHTML = renderOccurrencesTable(occurrences);
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
