<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pesquisa BIN - LL Despachante</title>
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
            --border: #E2E8F0;
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

        button {
            font-family: inherit;
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

        .bin-result-header {
            background: var(--primary);
            color: #fff;
            border-radius: 0 0 32px 32px;
            padding: 20px;
        }

        .bin-result-header-inner {
            max-width: 720px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .bin-result-title {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .bin-result-title-text {
            font-size: 20px;
            font-weight: 700;
        }

        .bin-result-subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
        }

        .bin-result-subtitle:empty {
            display: none;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bin-result-body {
            max-width: 720px;
            margin: 0 auto;
            padding: 20px 20px 32px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .bin-summary-card {
            background: var(--primary);
            color: #fff;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0, 71, 171, 0.2);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .bin-summary-title {
            font-size: 18px;
            font-weight: 700;
        }

        .bin-summary-rows {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .bin-summary-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .bin-summary-tile {
            flex: 1;
            min-width: 140px;
            background: #fff;
            color: var(--text-strong);
            border-radius: 14px;
            padding: 14px 16px;
            border: 1px solid rgba(226, 232, 240, 0.7);
        }

        .bin-summary-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
        }

        .bin-summary-value {
            font-size: 16px;
            font-weight: 600;
            margin-top: 6px;
        }

        .bin-section-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.6);
            padding: 20px 20px 24px;
        }

        .bin-section-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .bin-item {
            margin-bottom: 12px;
        }

        .bin-item:last-child {
            margin-bottom: 0;
        }

        .bin-item-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
        }

        .bin-item-value {
            font-size: 15px;
            color: var(--text-strong);
            margin-top: 4px;
        }

        .bin-empty {
            font-size: 14px;
            color: var(--text-muted);
        }

        .bin-message-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.6);
            padding: 20px;
            text-align: center;
            color: var(--text-muted);
        }

        @media (min-width: 768px) {
            .bin-result-header {
                padding: 24px;
            }

            .bin-result-title-text {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="bin-result-header">
        <div class="bin-result-header-inner">
            <button class="icon-button" type="button" id="binBackBtn" title="Voltar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <div class="bin-result-title">
                <span class="bin-result-title-text">Pesquisa BIN</span>
                <span class="bin-result-subtitle" id="binSubtitle"></span>
            </div>
            <div class="header-actions">
                <button class="icon-button" type="button" id="binCopyBtn" title="Copiar resultado">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                        <rect x="2" y="2" width="13" height="13" rx="2"></rect>
                    </svg>
                </button>
                <button class="icon-button" type="button" id="binPdfBtn" title="Emitir PDF" onclick="emitBinPdf()">
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

    <main class="bin-result-body" id="binResultBody"></main>

    <script>
        function getAuthToken() {
            return sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
        }

        const authToken = getAuthToken();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const binResultBody = document.getElementById('binResultBody');
        const binSubtitle = document.getElementById('binSubtitle');
        const binCopyBtn = document.getElementById('binCopyBtn');
        const binPdfBtn = document.getElementById('binPdfBtn');

        let binResultData = null;
        let binMeta = null;
        let isPdfGenerating = false;

        function checkAuth() {
            if (!authToken) {
                window.location.href = '/login';
                return false;
            }
            return true;
        }

        function nonEmptyString(value) {
            if (value == null) return '';
            const text = value.toString().trim();
            return text === '' ? '' : text;
        }

        function formatDisplayValue(value, fallback = '—') {
            const text = nonEmptyString(value);
            return text === '' ? fallback : text;
        }

        function formatLabel(value) {
            const normalized = value.replace(/_/g, ' ').trim();
            if (!normalized) return value;
            return normalized.charAt(0).toUpperCase() + normalized.slice(1);
        }

        function asMap(value) {
            if (value && typeof value === 'object' && !Array.isArray(value)) {
                return value;
            }
            return null;
        }

        function asArray(value) {
            return Array.isArray(value) ? value : [];
        }

        function buildItemsFromMap(map) {
            if (!map) return [];
            return Object.entries(map)
                .map(([key, value]) => ({
                    label: formatLabel(key),
                    value: formatDisplayValue(value, ''),
                }))
                .filter((item) => item.value !== '');
        }

        function buildItemsFromSection(section) {
            const items = asArray(section?.items);
            return items
                .map((item) => ({
                    label: item?.label ? item.label.toString() : '',
                    value: formatDisplayValue(item?.value, ''),
                }))
                .filter((item) => item.label !== '' && item.value !== '');
        }

        function renderItemsList(items) {
            if (!items.length) {
                return '<div class="bin-empty">Sem informações disponíveis.</div>';
            }
            return items
                .map(
                    (item) => `
                        <div class="bin-item">
                            <div class="bin-item-label">${item.label}</div>
                            <div class="bin-item-value">${item.value}</div>
                        </div>
                    `
                )
                .join('');
        }

        function renderSummaryCard(summary) {
            const placaValue = formatDisplayValue(summary.placa);
            const renavamValue = formatDisplayValue(summary.renavam);
            const chassiValue = nonEmptyString(summary.chassi);
            const proprietarioValue = nonEmptyString(summary.proprietario);

            let extraRow = '';
            if (chassiValue) {
                extraRow = `
                    <div class="bin-summary-row">
                        <div class="bin-summary-tile">
                            <div class="bin-summary-label">Chassi</div>
                            <div class="bin-summary-value">${formatDisplayValue(chassiValue)}</div>
                        </div>
                        ${proprietarioValue ? `
                            <div class="bin-summary-tile">
                                <div class="bin-summary-label">Proprietário</div>
                                <div class="bin-summary-value">${formatDisplayValue(proprietarioValue)}</div>
                            </div>
                        ` : ''}
                    </div>
                `;
            }

            return `
                <div class="bin-summary-card">
                    <div class="bin-summary-title">Consulta realizada</div>
                    <div class="bin-summary-rows">
                        <div class="bin-summary-row">
                            <div class="bin-summary-tile">
                                <div class="bin-summary-label">Placa</div>
                                <div class="bin-summary-value">${placaValue}</div>
                            </div>
                            <div class="bin-summary-tile">
                                <div class="bin-summary-label">Renavam</div>
                                <div class="bin-summary-value">${renavamValue}</div>
                            </div>
                        </div>
                        ${extraRow}
                    </div>
                </div>
            `;
        }

        function buildSubtitle(summary) {
            if (summary.placa) {
                return `Placa: ${summary.placa}`;
            }
            if (summary.chassi) {
                return `Chassi: ${summary.chassi}`;
            }
            if (summary.renavam) {
                return `Renavam: ${summary.renavam}`;
            }
            return '';
        }

        function displayBinResult(payload) {
            const normalized = asMap(payload?.normalized);
            const identificacao = asMap(normalized?.identificacao_do_veiculo_na_bin);
            const gravames = asMap(normalized?.gravames);

            const summary = {
                placa: nonEmptyString(identificacao?.placa) || nonEmptyString(binMeta?.placa),
                renavam: nonEmptyString(identificacao?.renavam) || nonEmptyString(binMeta?.renavam),
                chassi: nonEmptyString(identificacao?.chassi) || nonEmptyString(binMeta?.chassi),
                proprietario: nonEmptyString(gravames?.nome_financiado),
            };

            binSubtitle.textContent = buildSubtitle(summary);

            const blocks = [];
            blocks.push(renderSummaryCard(summary));

            const fonteItems = buildItemsFromMap(asMap(payload?.fonte));
            blocks.push(`
                <div class="bin-section-card">
                    <div class="bin-section-title">Fonte</div>
                    ${renderItemsList(fonteItems)}
                </div>
            `);

            const sections = asArray(payload?.sections);
            sections.forEach((section) => {
                const title = section?.title ? section.title.toString() : '';
                if (!title) {
                    return;
                }
                const items = buildItemsFromSection(section);
                blocks.push(`
                    <div class="bin-section-card">
                        <div class="bin-section-title">${title}</div>
                        ${renderItemsList(items)}
                    </div>
                `);
            });

            binResultBody.innerHTML = blocks.join('');
        }

        function setPdfLoading(isLoading) {
            if (!binPdfBtn) return;
            binPdfBtn.classList.toggle('is-loading', isLoading);
            binPdfBtn.disabled = isLoading;
        }

        async function emitBinPdf() {
            if (isPdfGenerating) return;
            if (!binResultData) {
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

                const response = await fetch('/api/bin/pdf', {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({
                        payload: binResultData,
                        meta: binMeta,
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
                const placaValue = nonEmptyString(binMeta?.placa) || 'consulta';
                const sanitized = placaValue.replace(/[^A-Za-z0-9]/g, '') || 'consulta';
                const filename = `pesquisa_bin_${sanitized}.pdf`;

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

        function loadResultFromStorage() {
            const stored = sessionStorage.getItem('bin_result');
            if (!stored) {
                return null;
            }
            try {
                return JSON.parse(stored);
            } catch (error) {
                return null;
            }
        }

        function loadMetaFromStorage() {
            const stored = sessionStorage.getItem('bin_meta');
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
            binResultBody.innerHTML = `
                <div class="bin-message-card">Nenhum resultado disponível.</div>
            `;
        }

        document.getElementById('binBackBtn')?.addEventListener('click', () => {
            sessionStorage.removeItem('bin_result');
            sessionStorage.removeItem('bin_meta');
            window.location.href = '/home';
        });

        binCopyBtn?.addEventListener('click', async () => {
            if (!binResultData) return;
            const text = JSON.stringify(binResultData, null, 2);
            try {
                await navigator.clipboard.writeText(text);
                alert('Dados copiados para a área de transferência.');
            } catch (error) {
                alert('Não foi possível copiar o resultado.');
            }
        });

        if (checkAuth()) {
            binResultData = loadResultFromStorage();
            binMeta = loadMetaFromStorage();
            if (binResultData) {
                displayBinResult(binResultData);
            } else {
                showEmptyResult();
            }
        }
    </script>
</body>
</html>
