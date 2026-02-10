@extends('admin.layouts.app')

@section('content')
    <style>
        .consulta-base__header {
            margin-bottom: 32px;
        }

        .consulta-base__header h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .consulta-base__header p {
            margin: 8px 0 0;
            color: var(--text-muted);
            font-size: 15px;
        }

        .consulta-base__card {
            background: var(--surface);
            border-radius: 18px;
            box-shadow:
                0 24px 48px rgba(15, 23, 42, 0.08),
                0 1px 0 rgba(255, 255, 255, 0.6);
            padding: 28px 32px;
            margin-bottom: 24px;
        }

        .consulta-base__card h2 {
            margin: 0 0 20px;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .consulta-base__file-input {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: border-color 160ms ease, background-color 160ms ease;
        }

        .consulta-base__file-input:hover {
            border-color: var(--brand-primary);
            background: rgba(11, 78, 162, 0.02);
        }

        .consulta-base__file-input.has-file {
            border-color: #16a34a;
            background: rgba(22, 163, 74, 0.04);
        }

        .consulta-base__file-input input {
            display: none;
        }

        .consulta-base__file-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .consulta-base__file-name {
            margin-top: 14px;
            font-weight: 600;
            color: var(--text-strong);
            display: none;
        }

        .consulta-base__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .consulta-base__btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 12px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }

        .consulta-base__btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .consulta-base__btn--primary {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(11, 78, 162, 0.2);
        }

        .consulta-base__btn--primary:hover:not(:disabled) {
            background: var(--brand-primary-hover);
            transform: translateY(-1px);
        }

        .consulta-base__btn--secondary {
            background: #f3f5f9;
            color: var(--text-default);
            border: 1px solid var(--border);
        }

        .consulta-base__btn--secondary:hover:not(:disabled) {
            background: #e8ecf3;
        }

        .consulta-base__btn--warning {
            background: #f97316;
            color: #fff;
            box-shadow: 0 12px 24px rgba(249, 115, 22, 0.2);
        }

        .consulta-base__btn--warning:hover:not(:disabled) {
            background: #ea580c;
            transform: translateY(-1px);
        }

        .consulta-base__btn--success {
            background: #16a34a;
            color: #fff;
            box-shadow: 0 12px 24px rgba(22, 163, 74, 0.2);
        }

        .consulta-base__btn--success:hover:not(:disabled) {
            background: #15803d;
            transform: translateY(-1px);
        }

        .consulta-base__progress {
            display: none;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: #f0f9ff;
            border-radius: 12px;
            margin-top: 20px;
        }

        .consulta-base__progress.is-visible {
            display: flex;
        }

        .consulta-base__progress-bar {
            flex: 1;
            height: 8px;
            background: #e0e7ef;
            border-radius: 999px;
            overflow: hidden;
        }

        .consulta-base__progress-fill {
            height: 100%;
            width: 0%;
            background: var(--brand-primary);
            border-radius: 999px;
            transition: width 300ms ease;
        }

        .consulta-base__progress-text {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-default);
            min-width: 95px;
            text-align: right;
        }

        .consulta-base__table-wrapper {
            overflow-x: auto;
            border-radius: 16px;
            background: var(--surface);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
            margin-top: 24px;
        }

        .consulta-base__table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .consulta-base__table thead {
            background: #f7f9fc;
        }

        .consulta-base__table th {
            text-align: left;
            padding: 18px 20px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .consulta-base__table td {
            padding: 14px 20px;
            font-size: 14px;
            border-top: 1px solid #ecf1f8;
            vertical-align: middle;
        }

        .consulta-base__status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 999px;
            letter-spacing: .2px;
        }

        .consulta-base__status--pending {
            background: #f3f5f9;
            color: #64748b;
        }

        .consulta-base__status--loading {
            background: #eef4ff;
            color: #0b4ea2;
        }

        .consulta-base__status--success {
            background: #dcfce7;
            color: #166534;
        }

        .consulta-base__status--error {
            background: #fee2e2;
            color: #b91c1c;
        }

        .consulta-base__consulta-pill {
            display: inline-flex;
            align-items: center;
            font-size: 12px;
            font-weight: 700;
            border-radius: 999px;
            padding: 6px 10px;
            letter-spacing: .3px;
        }

        .consulta-base__consulta-pill--success {
            background: #dcfce7;
            color: #166534;
        }

        .consulta-base__consulta-pill--error {
            background: #fee2e2;
            color: #b91c1c;
        }

        .consulta-base__consulta-message {
            margin-top: 6px;
            color: #475569;
            font-size: 12px;
        }

        .consulta-base__spinner {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            border: 2px solid rgba(11, 78, 162, 0.2);
            border-top-color: #0b4ea2;
            animation: consulta-base-spin 700ms linear infinite;
        }

        .consulta-base__empty {
            text-align: center;
            padding: 46px 20px;
            color: var(--text-muted);
        }

        .consulta-base__empty svg {
            opacity: .45;
            margin-bottom: 14px;
        }

        @keyframes consulta-base-spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .consulta-base__card {
                padding: 20px;
            }
        }
    </style>

    <div class="consulta-base__header">
        <h1>Consultas Base Estadual</h1>
        <p>Faça upload de uma planilha com a coluna PLACA. O sistema consulta automaticamente e exporta todos os campos da resposta em colunas separadas.</p>
    </div>

    <div class="consulta-base__card">
        <h2>1. Upload da planilha</h2>

        <label class="consulta-base__file-input" id="fileDropZone">
            <input type="file" id="fileInput" accept=".xlsx,.xls">
            <span class="consulta-base__file-label">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 2v6h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Clique para selecionar ou arraste um arquivo XLSX</span>
            </span>
        </label>

        <div class="consulta-base__file-name" id="fileName"></div>

        <p style="margin: 16px 0 0; color: var(--text-muted); font-size: 14px;">
            A planilha deve conter a coluna <strong>PLACA</strong>. As demais colunas são preservadas no arquivo exportado.
        </p>
    </div>

    <div class="consulta-base__card" id="verificationCard" style="display: none;">
        <h2>2. Processamento</h2>

        <div class="consulta-base__actions">
            <button type="button" class="consulta-base__btn consulta-base__btn--primary" id="btnIniciar" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M5 3v18l15-9-15-9z" fill="currentColor"/>
                </svg>
                Iniciar consultas
            </button>

            <button type="button" class="consulta-base__btn consulta-base__btn--secondary" id="btnLimpar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Limpar
            </button>

            <button type="button" class="consulta-base__btn consulta-base__btn--warning" id="btnPause" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M8 5h3v14H8zM13 5h3v14h-3z" fill="currentColor"/>
                </svg>
                Pausar
            </button>
        </div>

        <div class="consulta-base__progress" id="progressBar">
            <div class="consulta-base__progress-bar">
                <div class="consulta-base__progress-fill" id="progressFill"></div>
            </div>
            <span class="consulta-base__progress-text" id="progressText">0%</span>
        </div>
    </div>

    <div class="consulta-base__card" id="resultsCard" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <h2 style="margin: 0;">3. Resultados</h2>

            <button type="button" class="consulta-base__btn consulta-base__btn--success" id="btnDownloadResultado" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Baixar planilha atualizada
            </button>
        </div>

        <div class="consulta-base__table-wrapper">
            <table class="consulta-base__table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Placa</th>
                        <th>Nome</th>
                        <th>Consulta</th>
                        <th>Processamento</th>
                    </tr>
                </thead>
                <tbody id="resultsBody"></tbody>
            </table>
        </div>
    </div>

    <div class="consulta-base__card" id="emptyState">
        <div class="consulta-base__empty">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <p>Nenhuma planilha carregada.<br>Faça upload de um arquivo XLSX para começar.</p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        (function () {
            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const CONSULTAR_URL = '{{ route("admin.consultas-base-estadual.consultar") }}';
            const EXPORTAR_URL = '{{ route("admin.consultas-base-estadual.exportar") }}';
            const CAPTCHA_SOLVE_URL = "{{ url('api/captcha/solve') }}";

            let planilhaData = [];
            let planilhaColumns = [];
            let isProcessing = false;
            let isPaused = false;
            let pauseResolvers = [];

            const fileInput = document.getElementById('fileInput');
            const fileDropZone = document.getElementById('fileDropZone');
            const fileName = document.getElementById('fileName');
            const btnIniciar = document.getElementById('btnIniciar');
            const btnLimpar = document.getElementById('btnLimpar');
            const btnPause = document.getElementById('btnPause');
            const btnDownloadResultado = document.getElementById('btnDownloadResultado');
            const progressBar = document.getElementById('progressBar');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            const resultsBody = document.getElementById('resultsBody');
            const verificationCard = document.getElementById('verificationCard');
            const resultsCard = document.getElementById('resultsCard');
            const emptyState = document.getElementById('emptyState');

            fileDropZone.addEventListener('dragover', (event) => {
                event.preventDefault();
                fileDropZone.style.borderColor = 'var(--brand-primary)';
                fileDropZone.style.background = 'rgba(11, 78, 162, 0.04)';
            });

            fileDropZone.addEventListener('dragleave', () => {
                fileDropZone.style.borderColor = '';
                fileDropZone.style.background = '';
            });

            fileDropZone.addEventListener('drop', (event) => {
                event.preventDefault();
                fileDropZone.style.borderColor = '';
                fileDropZone.style.background = '';

                const files = event.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    handleFileSelect(files[0]);
                }
            });

            fileInput.addEventListener('change', (event) => {
                if (event.target.files.length > 0) {
                    handleFileSelect(event.target.files[0]);
                }
            });

            function handleFileSelect(file) {
                if (!file.name.match(/\.(xlsx|xls)$/i)) {
                    alert('Por favor, selecione um arquivo Excel (.xlsx ou .xls).');
                    return;
                }

                fileName.textContent = file.name;
                fileName.style.display = 'block';
                fileDropZone.classList.add('has-file');

                const reader = new FileReader();
                reader.onload = (event) => {
                    try {
                        const data = new Uint8Array(event.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                        const rows = XLSX.utils.sheet_to_json(firstSheet, { header: 1, defval: '' });

                        if (rows.length === 0) {
                            alert('A planilha está vazia.');
                            return;
                        }

                        const headerRowIndex = findHeaderRowIndex(rows);
                        if (headerRowIndex === -1) {
                            alert('Não foi possível localizar a coluna PLACA na planilha.');
                            return;
                        }

                        const headerRow = rows[headerRowIndex].map((cell) => String(cell || '').trim());
                        const normalizedHeaders = headerRow.map((value) => normalizeHeaderLabel(value));

                        const columnIndexes = [];
                        planilhaColumns = [];

                        headerRow.forEach((label, index) => {
                            const trimmed = String(label || '').trim();
                            if (!trimmed) {
                                return;
                            }

                            let uniqueLabel = trimmed;
                            let suffix = 2;
                            while (planilhaColumns.includes(uniqueLabel)) {
                                uniqueLabel = `${trimmed} (${suffix})`;
                                suffix += 1;
                            }

                            planilhaColumns.push(uniqueLabel);
                            columnIndexes.push(index);
                        });

                        const placaIndex = findColumnIndex(normalizedHeaders, ['PLACA']);
                        const renavamIndex = findColumnIndex(normalizedHeaders, ['RENAVAM']);
                        const nomeIndex = findColumnIndex(normalizedHeaders, ['NOME', 'ESTABELECIMENTO', 'LOJA']);

                        planilhaData = [];

                        for (let i = headerRowIndex + 1; i < rows.length; i++) {
                            const row = rows[i];
                            if (!row || row.length === 0) {
                                continue;
                            }

                            const normalizedRow = row.map((value) => normalizeHeaderLabel(value));
                            if (isRepeatedHeaderRow(normalizedRow, normalizedHeaders, placaIndex)) {
                                continue;
                            }

                            const raw = {};
                            planilhaColumns.forEach((column, colIdx) => {
                                raw[column] = normalizeCellValue(row[columnIndexes[colIdx]]);
                            });

                            if (Object.values(raw).every((value) => value === '')) {
                                continue;
                            }

                            const placaValue = normalizeCellValue(row[placaIndex]).toUpperCase();
                            const renavamValue = renavamIndex >= 0 ? normalizeCellValue(row[renavamIndex]) : '';
                            const nomeValue = nomeIndex >= 0 ? normalizeCellValue(row[nomeIndex]) : '';

                            planilhaData.push({
                                index: planilhaData.length + 1,
                                placa: placaValue,
                                renavam: renavamValue,
                                nome: nomeValue,
                                raw,
                                consulta_status: '',
                                consulta_mensagem: '',
                                campos: {},
                                status: 'pending',
                                error: ''
                            });
                        }

                        renderTable();
                        showUI();
                    } catch (error) {
                        console.error('Erro ao ler planilha:', error);
                        alert('Erro ao ler o arquivo. Verifique se é um arquivo Excel válido.');
                    }
                };

                reader.readAsArrayBuffer(file);
            }

            function showUI() {
                verificationCard.style.display = 'block';
                resultsCard.style.display = 'block';
                emptyState.style.display = 'none';
                btnIniciar.disabled = false;
                updateDownloadButton();
            }

            function renderTable() {
                resultsBody.innerHTML = planilhaData.map((row) => `
                    <tr data-index="${row.index}" data-status="${row.status}">
                        <td>${row.index}</td>
                        <td><strong>${escapeHtml(row.placa)}</strong></td>
                        <td>${escapeHtml(row.nome) || '—'}</td>
                        <td>${renderConsulta(row)}</td>
                        <td>${renderStatus(row)}</td>
                    </tr>
                `).join('');
            }

            function renderConsulta(row) {
                if (!row.consulta_status) {
                    return '—';
                }

                const isSuccess = row.consulta_status === 'SUCESSO';
                const badgeClass = isSuccess ? 'consulta-base__consulta-pill--success' : 'consulta-base__consulta-pill--error';

                return `
                    <div>
                        <span class="consulta-base__consulta-pill ${badgeClass}">${escapeHtml(row.consulta_status)}</span>
                        ${row.consulta_mensagem ? `<div class="consulta-base__consulta-message">${escapeHtml(row.consulta_mensagem)}</div>` : ''}
                    </div>
                `;
            }

            function renderStatus(row) {
                switch (row.status) {
                    case 'pending':
                        return '<span class="consulta-base__status consulta-base__status--pending">Aguardando</span>';
                    case 'loading':
                        return '<span class="consulta-base__status consulta-base__status--loading"><span class="consulta-base__spinner"></span> Consultando...</span>';
                    case 'success':
                        return '<span class="consulta-base__status consulta-base__status--success">Concluído</span>';
                    case 'error':
                        return `<span class="consulta-base__status consulta-base__status--error" title="${escapeHtml(row.error)}">Erro</span>`;
                    default:
                        return '';
                }
            }

            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function normalizeCellValue(value) {
                if (value === null || value === undefined) {
                    return '';
                }

                return String(value).trim();
            }

            function normalizeHeaderLabel(value) {
                if (value === null || value === undefined) {
                    return '';
                }

                return String(value)
                    .trim()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-zA-Z0-9]+/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toUpperCase();
            }

            function findHeaderRowIndex(rows) {
                for (let i = 0; i < rows.length; i++) {
                    const normalized = rows[i].map((value) => normalizeHeaderLabel(value));
                    if (normalized.includes('PLACA')) {
                        return i;
                    }
                }

                return -1;
            }

            function findColumnIndex(headers, candidates) {
                for (const candidate of candidates) {
                    const index = headers.findIndex((header) => header === candidate || header.includes(candidate));
                    if (index >= 0) {
                        return index;
                    }
                }

                return -1;
            }

            function isRepeatedHeaderRow(row, header, placaIndex) {
                if (placaIndex < 0 || row[placaIndex] !== 'PLACA') {
                    return false;
                }

                let matches = 0;
                for (let i = 0; i < Math.min(row.length, header.length); i++) {
                    if (row[i] && header[i] && row[i] === header[i]) {
                        matches += 1;
                    }
                }

                return matches >= 2;
            }

            function waitForResume() {
                if (!isPaused) {
                    return Promise.resolve();
                }

                return new Promise((resolve) => {
                    pauseResolvers.push(resolve);
                });
            }

            async function fetchCaptchaSolution() {
                const response = await fetch(CAPTCHA_SOLVE_URL, {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    let message;
                    try {
                        const data = await response.json();
                        message = data.message ?? `Erro ao resolver captcha (HTTP ${response.status})`;
                    } catch {
                        message = `Erro ao resolver captcha (HTTP ${response.status})`;
                    }
                    throw new Error(message);
                }

                const payload = await response.json().catch(() => ({}));
                const solution = String(payload.solution || payload.Solution || '').trim().toUpperCase();

                if (!solution) {
                    throw new Error('Captcha automático retornou valor inválido.');
                }

                return solution;
            }

            btnPause.addEventListener('click', () => {
                if (!isProcessing) return;

                isPaused = !isPaused;
                btnPause.textContent = isPaused ? 'Retomar' : 'Pausar';

                if (!isPaused && pauseResolvers.length > 0) {
                    pauseResolvers.forEach((resolve) => resolve());
                    pauseResolvers = [];
                }
            });

            btnIniciar.addEventListener('click', async () => {
                if (isProcessing) return;

                if (planilhaData.length === 0) {
                    alert('Por favor, carregue uma planilha para iniciar.');
                    return;
                }

                isProcessing = true;
                btnIniciar.disabled = true;
                btnDownloadResultado.disabled = true;
                btnPause.disabled = false;
                progressBar.classList.add('is-visible');
                isPaused = false;
                btnPause.textContent = 'Pausar';
                pauseResolvers = [];

                const total = planilhaData.length;
                let completed = 0;

                for (let i = 0; i < planilhaData.length; i++) {
                    await waitForResume();
                    const row = planilhaData[i];

                    if (!row.placa) {
                        row.status = 'error';
                        row.error = 'Placa não informada';
                        row.consulta_status = 'ERRO';
                        row.consulta_mensagem = 'PLACA VAZIA';
                        row.campos = {};

                        completed++;
                        updateProgress(completed, total);
                        renderTable();
                        updateDownloadButton();
                        continue;
                    }

                    let captchaSolution = '';
                    try {
                        captchaSolution = await fetchCaptchaSolution();
                    } catch (error) {
                        row.status = 'error';
                        row.error = error.message || 'Erro ao resolver captcha';
                        row.consulta_status = 'ERRO';
                        row.consulta_mensagem = row.error;
                        row.campos = {};

                        completed++;
                        updateProgress(completed, total);
                        renderTable();
                        updateDownloadButton();
                        continue;
                    }

                    row.status = 'loading';
                    renderTable();

                    try {
                        const response = await fetch(CONSULTAR_URL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF_TOKEN,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                placa: row.placa,
                                renavam: row.renavam,
                                captcha_response: captchaSolution,
                                debug: true
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            row.status = 'success';
                            row.error = '';
                            row.consulta_status = 'SUCESSO';
                            row.consulta_mensagem = result.message || 'Consulta realizada com sucesso.';
                            row.campos = result.campos && typeof result.campos === 'object' ? result.campos : {};
                        } else {
                            row.status = 'error';
                            row.error = result.error || 'Erro desconhecido';
                            row.consulta_status = 'ERRO';
                            row.consulta_mensagem = row.error;
                            row.campos = {};
                        }
                    } catch (error) {
                        row.status = 'error';
                        row.error = 'Falha na comunicação';
                        row.consulta_status = 'ERRO';
                        row.consulta_mensagem = row.error;
                        row.campos = {};
                    }

                    completed++;
                    updateProgress(completed, total);
                    renderTable();
                    updateDownloadButton();

                    if (i < planilhaData.length - 1) {
                        await sleep(500);
                    }
                }

                isProcessing = false;
                btnIniciar.disabled = false;
                btnPause.disabled = true;
                isPaused = false;
                pauseResolvers = [];
                btnPause.textContent = 'Pausar';
                updateDownloadButton();
            });

            function updateProgress(completed, total) {
                const percent = Math.round((completed / total) * 100);
                progressFill.style.width = percent + '%';
                progressText.textContent = `${completed}/${total} (${percent}%)`;
            }

            function sleep(ms) {
                return new Promise((resolve) => setTimeout(resolve, ms));
            }

            btnLimpar.addEventListener('click', () => {
                if (isProcessing) {
                    if (!confirm('Uma consulta está em andamento. Deseja realmente limpar?')) {
                        return;
                    }
                }

                planilhaData = [];
                planilhaColumns = [];
                isProcessing = false;
                isPaused = false;
                pauseResolvers = [];

                fileInput.value = '';
                fileName.style.display = 'none';
                fileName.textContent = '';
                fileDropZone.classList.remove('has-file');
                progressBar.classList.remove('is-visible');
                progressFill.style.width = '0%';
                progressText.textContent = '0%';
                resultsBody.innerHTML = '';
                verificationCard.style.display = 'none';
                resultsCard.style.display = 'none';
                emptyState.style.display = 'block';
                btnIniciar.disabled = true;
                btnPause.disabled = true;
                btnPause.textContent = 'Pausar';
                btnDownloadResultado.disabled = true;
            });

            function updateDownloadButton() {
                const processedRows = planilhaData.filter((row) => row.consulta_status !== '');
                btnDownloadResultado.disabled = processedRows.length === 0;
            }

            function resolveDownloadFilename(response, fallbackName) {
                const header = response.headers.get('Content-Disposition') || response.headers.get('content-disposition') || '';
                if (!header) {
                    return fallbackName;
                }

                const utfMatch = header.match(/filename\*=UTF-8''([^;]+)/i);
                if (utfMatch && utfMatch[1]) {
                    try {
                        return decodeURIComponent(utfMatch[1]);
                    } catch {
                        return utfMatch[1];
                    }
                }

                const asciiMatch = header.match(/filename=\"?([^\";]+)\"?/i);
                if (asciiMatch && asciiMatch[1]) {
                    return asciiMatch[1];
                }

                return fallbackName;
            }

            async function downloadPlanilhaAtualizada() {
                const dados = planilhaData.map((row) => ({
                    values: planilhaColumns.map((column) => row.raw?.[column] ?? ''),
                    consulta_status: row.consulta_status || '',
                    consulta_mensagem: row.consulta_mensagem || '',
                    campos: row.campos || {}
                }));

                if (dados.length === 0) {
                    return;
                }

                try {
                    const response = await fetch(EXPORTAR_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN
                        },
                        body: JSON.stringify({
                            dados,
                            colunas: planilhaColumns
                        })
                    });

                    if (!response.ok) {
                        throw new Error('Erro ao gerar arquivo');
                    }

                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const anchor = document.createElement('a');
                    anchor.href = url;
                    anchor.download = resolveDownloadFilename(
                        response,
                        'consultas_base_estadual_' + new Date().toISOString().slice(0, 10) + '.xlsx'
                    );
                    document.body.appendChild(anchor);
                    anchor.click();
                    document.body.removeChild(anchor);
                    window.URL.revokeObjectURL(url);
                } catch (error) {
                    console.error('Erro ao baixar arquivo:', error);
                    alert('Erro ao gerar o arquivo. Tente novamente.');
                }
            }

            btnDownloadResultado.addEventListener('click', downloadPlanilhaAtualizada);
        })();
    </script>
@endsection
