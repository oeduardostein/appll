@extends('admin.layouts.app')

@section('content')
    <style>
        .teste-planilha__header {
            margin-bottom: 32px;
        }

        .teste-planilha__header h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .teste-planilha__header p {
            margin: 8px 0 0;
            color: var(--text-muted);
            font-size: 15px;
        }

        .teste-planilha__card {
            background: var(--surface);
            border-radius: 18px;
            box-shadow:
                0 24px 48px rgba(15, 23, 42, 0.08),
                0 1px 0 rgba(255, 255, 255, 0.6);
            padding: 28px 32px;
            margin-bottom: 24px;
        }

        .teste-planilha__card h2 {
            margin: 0 0 20px;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .teste-planilha__form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }

        .teste-planilha__form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1 1 auto;
            min-width: 200px;
        }

        .teste-planilha__form-group label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .teste-planilha__input {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            color: var(--text-default);
            background: #fff;
            outline: none;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .teste-planilha__input:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(11, 78, 162, 0.12);
        }

        .teste-planilha__file-input {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: border-color 160ms ease, background-color 160ms ease;
        }

        .teste-planilha__file-input:hover {
            border-color: var(--brand-primary);
            background: rgba(11, 78, 162, 0.02);
        }

        .teste-planilha__file-input.has-file {
            border-color: #16a34a;
            background: rgba(22, 163, 74, 0.04);
        }

        .teste-planilha__file-input input {
            display: none;
        }

        .teste-planilha__file-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .teste-planilha__file-label svg {
            opacity: 0.6;
        }

        .teste-planilha__file-name {
            font-weight: 600;
            color: var(--text-strong);
        }

        .teste-planilha__btn {
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

        .teste-planilha__btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .teste-planilha__btn--primary {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(11, 78, 162, 0.2);
        }

        .teste-planilha__btn--primary:hover:not(:disabled) {
            background: var(--brand-primary-hover);
            transform: translateY(-1px);
        }

        .teste-planilha__btn--secondary {
            background: #f3f5f9;
            color: var(--text-default);
            border: 1px solid var(--border);
        }

        .teste-planilha__btn--secondary:hover:not(:disabled) {
            background: #e8ecf3;
        }

        .teste-planilha__btn--warning {
            background: #f97316;
            color: #fff;
            box-shadow: 0 12px 24px rgba(249, 115, 22, 0.2);
        }

        .teste-planilha__btn--warning:hover:not(:disabled) {
            background: #ea580c;
            transform: translateY(-1px);
        }

        .teste-planilha__btn--success {
            background: #16a34a;
            color: #fff;
            box-shadow: 0 12px 24px rgba(22, 163, 74, 0.2);
        }

        .teste-planilha__btn--success:hover:not(:disabled) {
            background: #15803d;
            transform: translateY(-1px);
        }

        .teste-planilha__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .teste-planilha__progress {
            display: none;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: #f0f9ff;
            border-radius: 12px;
            margin-top: 20px;
        }

        .teste-planilha__progress.is-visible {
            display: flex;
        }

        .teste-planilha__progress-bar {
            flex: 1;
            height: 8px;
            background: #e0e7ef;
            border-radius: 999px;
            overflow: hidden;
        }

        .teste-planilha__progress-fill {
            height: 100%;
            width: 0%;
            background: var(--brand-primary);
            border-radius: 999px;
            transition: width 300ms ease;
        }

        .teste-planilha__progress-text {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-default);
            min-width: 80px;
            text-align: right;
        }

        .teste-planilha__table-wrapper {
            overflow-x: auto;
            border-radius: 16px;
            background: var(--surface);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        }

        .teste-planilha__table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .teste-planilha__table thead {
            background: #f7f9fc;
        }

        .teste-planilha__table th {
            text-align: left;
            padding: 18px 20px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .teste-planilha__table td {
            padding: 14px 20px;
            font-size: 14px;
            border-top: 1px solid #ecf1f8;
            vertical-align: middle;
        }

        .teste-planilha__table tr[data-status="pending"] td {
            color: var(--text-muted);
        }

        .teste-planilha__table tr[data-status="loading"] td {
            background: #fffbeb;
        }

        .teste-planilha__table tr[data-status="success"] td {
            background: #f0fdf4;
        }

        .teste-planilha__table tr[data-status="error"] td {
            background: #fef2f2;
        }

        .teste-planilha__status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .teste-planilha__status--pending {
            background: #f3f4f6;
            color: #6b7280;
        }

        .teste-planilha__status--loading {
            background: #fef3c7;
            color: #b45309;
        }

        .teste-planilha__status--success {
            background: #dcfce7;
            color: #16a34a;
        }

        .teste-planilha__status--error {
            background: #fee2e2;
            color: #dc2626;
        }

        .teste-planilha__status--warning {
            background: #fef3c7;
            color: #b45309;
        }

        .teste-planilha__result {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .teste-planilha__result-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
            width: fit-content;
        }

        .teste-planilha__result-badge--liberado {
            background: #dcfce7;
            color: #16a34a;
        }

        .teste-planilha__result-badge--nao-liberado {
            background: #fee2e2;
            color: #dc2626;
        }

        .teste-planilha__result-badge--erro {
            background: #f3f4f6;
            color: #6b7280;
        }

        .teste-planilha__result-detail {
            font-size: 12px;
            color: var(--text-muted);
        }

        .teste-planilha__spinner {
            width: 14px;
            height: 14px;
            border: 2px solid #fbbf24;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 800ms linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .teste-planilha__empty {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-muted);
        }

        .teste-planilha__empty svg {
            margin-bottom: 16px;
            opacity: 0.4;
        }

        .teste-planilha__obs-warning {
            font-weight: 600;
            color: #dc2626;
        }
    </style>

    <header class="teste-planilha__header">
        <h1>Teste de planilha Gravame</h1>
        <p>Faça upload de uma planilha XLSX com placas/renavams para consultar gravame ativo e intenção de gravame.</p>
    </header>

    <div class="teste-planilha__card">
        <h2>1. Upload da Planilha</h2>

        <div class="teste-planilha__file-input" id="fileDropZone">
            <input type="file" id="fileInput" accept=".xlsx,.xls">
            <label for="fileInput" class="teste-planilha__file-label">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Clique para selecionar ou arraste um arquivo XLSX</span>
                <span class="teste-planilha__file-name" id="fileName" style="display: none;"></span>
            </label>
        </div>

        <p style="margin-top: 12px; font-size: 13px; color: var(--text-muted);">
            A planilha deve conter as colunas: <strong>PLACA</strong>, <strong>RENAVAM</strong> e <strong>NOME</strong>
        </p>
    </div>

    <div class="teste-planilha__card" id="verificationCard" style="display: none;">
        <h2>2. Consulta de Gravame</h2>

        <p style="margin-top: 12px; font-size: 13px; color: var(--text-muted);">
            A automação irá consultar gravame ativo e intenção de gravame para cada placa da planilha.
        </p>

        <div class="teste-planilha__actions">
            <button type="button" class="teste-planilha__btn teste-planilha__btn--primary" id="btnIniciar" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M5 3l14 9-14 9V3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Iniciar Consulta
            </button>

            <button type="button" class="teste-planilha__btn teste-planilha__btn--secondary" id="btnLimpar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Limpar
            </button>

            <button type="button" class="teste-planilha__btn teste-planilha__btn--warning" id="btnPause" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M8 5h3v14H8zM13 5h3v14h-3z" fill="currentColor"/>
                </svg>
                Pausar
            </button>
        </div>

        <div class="teste-planilha__progress" id="progressBar">
            <div class="teste-planilha__progress-bar">
                <div class="teste-planilha__progress-fill" id="progressFill"></div>
            </div>
            <span class="teste-planilha__progress-text" id="progressText">0%</span>
        </div>
    </div>

    <div class="teste-planilha__card" id="resultsCard" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <h2 style="margin: 0;">3. Resultados</h2>

            <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                <button type="button" class="teste-planilha__btn teste-planilha__btn--success" id="btnDownloadLiberados" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Baixar Liberados
                </button>

                <button type="button" class="teste-planilha__btn teste-planilha__btn--warning" id="btnDownloadGravame" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Baixar Com Gravame
                </button>
            </div>
        </div>

        <div class="teste-planilha__table-wrapper" style="margin-top: 24px;">
            <table class="teste-planilha__table" id="resultsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Placa</th>
                        <th>Renavam</th>
                        <th>Nome</th>
                        <th>Resultado</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="resultsBody">
                </tbody>
            </table>
        </div>
    </div>

    <div class="teste-planilha__card" id="emptyState">
        <div class="teste-planilha__empty">
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
        (function() {
            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const CONSULTAR_URL = '{{ route("admin.teste-planilha-gravame.consultar") }}';
            const EXPORTAR_URL = '{{ route("admin.teste-planilha-gravame.exportar") }}';
            const CAPTCHA_SOLVE_URL = "{{ url('api/captcha/solve') }}";

            let planilhaData = [];
            let isProcessing = false;
            let isPaused = false;
            let pauseResolvers = [];

            // Elements
            const fileInput = document.getElementById('fileInput');
            const fileDropZone = document.getElementById('fileDropZone');
            const fileName = document.getElementById('fileName');
            const btnIniciar = document.getElementById('btnIniciar');
            const btnLimpar = document.getElementById('btnLimpar');
            const btnPause = document.getElementById('btnPause');
            const btnDownloadLiberados = document.getElementById('btnDownloadLiberados');
            const btnDownloadGravame = document.getElementById('btnDownloadGravame');
            const progressBar = document.getElementById('progressBar');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            const resultsBody = document.getElementById('resultsBody');
            const verificationCard = document.getElementById('verificationCard');
            const resultsCard = document.getElementById('resultsCard');
            const emptyState = document.getElementById('emptyState');

            // Drag and drop
            fileDropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileDropZone.style.borderColor = 'var(--brand-primary)';
                fileDropZone.style.background = 'rgba(11, 78, 162, 0.04)';
            });

            fileDropZone.addEventListener('dragleave', () => {
                fileDropZone.style.borderColor = '';
                fileDropZone.style.background = '';
            });

            fileDropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                fileDropZone.style.borderColor = '';
                fileDropZone.style.background = '';
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    handleFileSelect(files[0]);
                }
            });

            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleFileSelect(e.target.files[0]);
                }
            });

            function handleFileSelect(file) {
                if (!file.name.match(/\.(xlsx|xls)$/i)) {
                    alert('Por favor, selecione um arquivo Excel (.xlsx ou .xls)');
                    return;
                }

                fileName.textContent = file.name;
                fileName.style.display = 'block';
                fileDropZone.classList.add('has-file');

                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                        const jsonData = XLSX.utils.sheet_to_json(firstSheet, { defval: '' });

                        if (jsonData.length === 0) {
                            alert('A planilha está vazia.');
                            return;
                        }

                        // Normalize column names (case-insensitive)
                        planilhaData = jsonData.map((row, index) => {
                            const normalizedRow = {};
                            Object.keys(row).forEach(key => {
                                normalizedRow[key.toUpperCase().trim()] = String(row[key]).trim();
                            });
                            return {
                                index: index + 1,
                                placa: normalizedRow['PLACA'] || '',
                                renavam: normalizedRow['RENAVAM'] || '',
                                nome: normalizedRow['NOME'] || '',
                                resultado: '',
                                resultado_detalhe: '',
                                resultado_status: 'pending',
                                status: 'pending',
                                error: ''
                            };
                        });

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
                updateDownloadButtons();
            }

            function renderTable() {
                resultsBody.innerHTML = planilhaData.map(row => `
                    <tr data-index="${row.index}" data-status="${row.status}">
                        <td>${row.index}</td>
                        <td><strong>${escapeHtml(row.placa)}</strong></td>
                        <td>${escapeHtml(row.renavam)}</td>
                        <td>${escapeHtml(row.nome) || '—'}</td>
                        <td>${renderResultado(row)}</td>
                        <td>${renderStatus(row)}</td>
                    </tr>
                `).join('');
            }

            function renderResultado(row) {
                if (!row.resultado && !row.resultado_detalhe) {
                    return '—';
                }

                let badgeClass = 'teste-planilha__result-badge--erro';
                if (row.resultado_status === 'liberado') {
                    badgeClass = 'teste-planilha__result-badge--liberado';
                } else if (row.resultado_status === 'nao_liberado') {
                    badgeClass = 'teste-planilha__result-badge--nao-liberado';
                }

                const badgeText = escapeHtml(row.resultado || 'Resultado');
                const detailText = escapeHtml(row.resultado_detalhe || '');

                return `
                    <div class="teste-planilha__result">
                        <span class="teste-planilha__result-badge ${badgeClass}">${badgeText}</span>
                        ${detailText ? `<div class="teste-planilha__result-detail">${detailText}</div>` : ''}
                    </div>
                `;
            }

            function renderStatus(row) {
                switch (row.status) {
                    case 'pending':
                        return '<span class="teste-planilha__status teste-planilha__status--pending">Aguardando</span>';
                    case 'loading':
                        return '<span class="teste-planilha__status teste-planilha__status--loading"><span class="teste-planilha__spinner"></span> Consultando...</span>';
                    case 'success':
                        return '<span class="teste-planilha__status teste-planilha__status--success">Concluído</span>';
                    case 'error':
                        return `<span class="teste-planilha__status teste-planilha__status--error" title="${escapeHtml(row.error)}">Erro</span>`;
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
                    pauseResolvers.forEach(resolve => resolve());
                    pauseResolvers = [];
                }
            });

            // Iniciar consulta
            btnIniciar.addEventListener('click', async () => {
                if (isProcessing) return;

                if (planilhaData.length === 0) {
                    alert('Por favor, carregue uma planilha para iniciar.');
                    return;
                }

                isProcessing = true;
                btnIniciar.disabled = true;
                btnDownloadLiberados.disabled = true;
                btnDownloadGravame.disabled = true;
                progressBar.classList.add('is-visible');
                btnPause.disabled = false;
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
                        row.resultado = 'ERRO NA CONSULTA';
                        row.resultado_detalhe = 'PLACA VAZIA';
                        row.resultado_status = 'erro';
                        completed++;
                        updateProgress(completed, total);
                        renderTable();
                        updateDownloadButtons();
                        continue;
                    }

                    // Resolve captcha before sending a consulta request
                    let captchaSolution = '';
                    try {
                        captchaSolution = await fetchCaptchaSolution();
                    } catch (error) {
                        console.error('Erro ao resolver captcha:', error);
                        row.status = 'error';
                        row.error = error.message || 'Erro ao resolver captcha';
                        row.resultado = 'ERRO NA CONSULTA';
                        row.resultado_detalhe = row.error;
                        row.resultado_status = 'erro';
                        completed++;
                        updateProgress(completed, total);
                        renderTable();
                        updateDownloadButtons();
                        continue;
                    }

                    // Update status to loading
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
                            row.resultado = result.resultado || '';
                            row.resultado_detalhe = result.resultado_detalhe || '';
                            row.resultado_status = result.resultado_status || 'erro';
                        } else {
                            row.status = 'error';
                            row.error = result.error || 'Erro desconhecido';
                            row.resultado = 'ERRO NA CONSULTA';
                            row.resultado_detalhe = row.error;
                            row.resultado_status = 'erro';
                        }
                    } catch (error) {
                        console.error('Erro na consulta:', error);
                        row.status = 'error';
                        row.error = 'Falha na comunicação';
                        row.resultado = 'ERRO NA CONSULTA';
                        row.resultado_detalhe = row.error;
                        row.resultado_status = 'erro';
                    }

                    completed++;
                    updateProgress(completed, total);
                    renderTable();
                    updateDownloadButtons();

                    // Delay between requests to avoid overload
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
                updateDownloadButtons();
            });

            function updateProgress(completed, total) {
                const percent = Math.round((completed / total) * 100);
                progressFill.style.width = percent + '%';
                progressText.textContent = `${completed}/${total} (${percent}%)`;
            }

            function sleep(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            // Limpar
            btnLimpar.addEventListener('click', () => {
                if (isProcessing) {
                    if (!confirm('Uma consulta está em andamento. Deseja realmente limpar?')) {
                        return;
                    }
                }

                planilhaData = [];
                isProcessing = false;
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
                btnDownloadLiberados.disabled = true;
                btnDownloadGravame.disabled = true;
                btnPause.disabled = true;
                isPaused = false;
                pauseResolvers = [];
                btnPause.textContent = 'Pausar';
            });

            function updateDownloadButtons() {
                const liberados = planilhaData.filter(row => row.resultado_status === 'liberado');
                const comGravame = planilhaData.filter(row => row.resultado_status === 'nao_liberado');
                btnDownloadLiberados.disabled = liberados.length === 0;
                btnDownloadGravame.disabled = comGravame.length === 0;
            }

            async function downloadPlanilha(tipo, dados) {
                if (dados.length === 0) return;

                try {
                    const response = await fetch(EXPORTAR_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN
                        },
                        body: JSON.stringify({ dados, tipo })
                    });

                    if (!response.ok) {
                        throw new Error('Erro ao gerar arquivo');
                    }

                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    const prefix = tipo === 'liberados' ? 'gravame_liberados' : 'gravame_com_gravame';
                    a.download = prefix + '_' + new Date().toISOString().slice(0, 10) + '.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                } catch (error) {
                    console.error('Erro ao baixar:', error);
                    alert('Erro ao gerar o arquivo. Tente novamente.');
                }
            }

            btnDownloadLiberados.addEventListener('click', async () => {
                const liberados = planilhaData.filter(row => row.resultado_status === 'liberado');
                const dados = liberados.map(row => ({
                    placa: row.placa,
                    renavam: row.renavam,
                    nome: row.nome,
                    resultado: row.resultado,
                    resultado_detalhe: row.resultado_detalhe
                }));

                await downloadPlanilha('liberados', dados);
            });

            btnDownloadGravame.addEventListener('click', async () => {
                const comGravame = planilhaData.filter(row => row.resultado_status === 'nao_liberado');
                const dados = comGravame.map(row => ({
                    placa: row.placa,
                    renavam: row.renavam,
                    nome: row.nome,
                    resultado: row.resultado,
                    resultado_detalhe: row.resultado_detalhe
                }));

                await downloadPlanilha('com_gravame', dados);
            });
        })();
    </script>
@endsection
