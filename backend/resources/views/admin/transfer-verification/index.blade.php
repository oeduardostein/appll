@extends('admin.layouts.app')

@section('content')
    <style>
        .admin-verification__header {
            margin-bottom: 32px;
        }

        .admin-verification__header h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .admin-verification__header p {
            margin: 8px 0 0;
            color: var(--text-muted);
            font-size: 15px;
        }

        .upload-zone {
            background: var(--surface);
            border: 2px dashed var(--border);
            border-radius: 18px;
            padding: 48px 32px;
            text-align: center;
            cursor: pointer;
            transition: border-color 200ms ease, background-color 200ms ease;
            margin-bottom: 32px;
        }

        .upload-zone:hover,
        .upload-zone.is-dragover {
            border-color: var(--brand-primary);
            background: var(--brand-light);
        }

        .upload-zone.is-disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        .upload-zone__icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            color: var(--brand-primary);
        }

        .upload-zone__title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-strong);
            margin: 0 0 8px;
        }

        .upload-zone__description {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
        }

        .upload-zone__input {
            display: none;
        }

        .progress-section {
            display: none;
            margin-bottom: 32px;
        }

        .progress-section.is-visible {
            display: block;
        }

        .progress-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .progress-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .progress-stats {
            display: flex;
            gap: 24px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .progress-stats strong {
            color: var(--text-strong);
        }

        .progress-bar-container {
            background: #e5e7eb;
            border-radius: 999px;
            height: 8px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .progress-bar {
            background: var(--brand-primary);
            height: 100%;
            width: 0%;
            border-radius: 999px;
            transition: width 300ms ease;
        }

        .progress-bar.is-error {
            background: #ef4444;
        }

        .progress-bar.is-complete {
            background: #22c55e;
        }

        .admin-table-container {
            max-height: 500px;
            overflow-y: auto;
            border-radius: 16px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface);
        }

        .admin-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #f7f9fc;
        }

        .admin-table th {
            text-align: left;
            padding: 16px 20px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .admin-table td {
            padding: 14px 20px;
            font-size: 14px;
            border-top: 1px solid #ecf1f8;
        }

        .admin-table tr.is-processing td {
            background: #fef3c7;
        }

        .admin-table tr.is-success td {
            background: #f0fdf4;
        }

        .admin-table tr.is-error td {
            background: #fef2f2;
        }

        .admin-table tr.is-warning td {
            background: #fffbeb;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .status-badge.pending {
            background: #e5e7eb;
            color: #6b7280;
        }

        .status-badge.processing {
            background: #fef3c7;
            color: #b45309;
        }

        .status-badge.success {
            background: #d1fae5;
            color: #047857;
        }

        .status-badge.error {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-badge.warning {
            background: #ffedd5;
            color: #c2410c;
        }

        .obs-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .obs-badge.transfer-not-completed {
            background: #fee2e2;
            color: #b91c1c;
        }

        .obs-badge.transfer-completed {
            background: #d1fae5;
            color: #047857;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .admin-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 12px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 160ms ease, transform 160ms ease;
        }

        .admin-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .admin-button--primary {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(11, 78, 162, 0.2);
        }

        .admin-button--primary:hover:not(:disabled) {
            background: var(--brand-primary-hover);
            transform: translateY(-1px);
        }

        .admin-button--secondary {
            background: #fff;
            color: var(--text-default);
            border: 1px solid var(--border);
        }

        .admin-button--secondary:hover:not(:disabled) {
            background: #f3f4f6;
        }

        .admin-button--danger {
            background: #ef4444;
            color: #fff;
        }

        .admin-button--danger:hover:not(:disabled) {
            background: #dc2626;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid currentColor;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .alert--error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .alert--success {
            background: #f0fdf4;
            color: #047857;
            border: 1px solid #bbf7d0;
        }

        .alert--warning {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .file-info__icon {
            width: 40px;
            height: 40px;
            background: var(--brand-light);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--brand-primary);
        }

        .file-info__details {
            flex: 1;
        }

        .file-info__name {
            font-weight: 600;
            color: var(--text-strong);
            font-size: 14px;
        }

        .file-info__meta {
            color: var(--text-muted);
            font-size: 13px;
        }

        .file-info__remove {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background-color 160ms ease, color 160ms ease;
        }

        .file-info__remove:hover {
            background: #fee2e2;
            color: #b91c1c;
        }

        .instructions {
            background: var(--brand-light);
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 32px;
        }

        .instructions h3 {
            margin: 0 0 12px;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-strong);
        }

        .instructions ul {
            margin: 0;
            padding-left: 20px;
            color: var(--text-default);
            font-size: 14px;
            line-height: 1.6;
        }

        .instructions code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
        }
    </style>

    <header class="admin-verification__header">
        <h1>Verificação de Transferências</h1>
        <p>Faça upload de uma planilha XLSX para verificar se as transferências foram concluídas.</p>
    </header>

    <div class="instructions">
        <h3>Formato do arquivo</h3>
        <ul>
            <li>O arquivo deve ser um <code>.xlsx</code> com as colunas: <code>PLACA</code>, <code>RENAVAM</code> e <code>NOME</code></li>
            <li>O <code>NOME</code> é o nome do antigo proprietário para verificação</li>
            <li>Se o nome do proprietário atual for igual ao nome informado, será marcado como <strong>"Transferência NÃO CONCLUÍDA"</strong></li>
            <li>Cada linha será consultada individualmente na base estadual</li>
        </ul>
    </div>

    <div id="alertContainer"></div>

    <div id="fileInfo" class="file-info" style="display: none;">
        <div class="file-info__icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
        </div>
        <div class="file-info__details">
            <div class="file-info__name" id="fileName"></div>
            <div class="file-info__meta" id="fileMeta"></div>
        </div>
        <button type="button" class="file-info__remove" id="removeFile" title="Remover arquivo">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <div class="upload-zone" id="uploadZone">
        <input type="file" id="fileInput" class="upload-zone__input" accept=".xlsx,.xls">
        <div class="upload-zone__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
        </div>
        <h3 class="upload-zone__title">Arraste o arquivo ou clique para selecionar</h3>
        <p class="upload-zone__description">Apenas arquivos .xlsx (máximo 10MB)</p>
    </div>

    <section class="progress-section" id="progressSection">
        <div class="progress-header">
            <h2>Processamento</h2>
            <div class="progress-stats">
                <span>Total: <strong id="totalCount">0</strong></span>
                <span>Processados: <strong id="processedCount">0</strong></span>
                <span>Erros: <strong id="errorCount">0</strong></span>
            </div>
        </div>

        <div class="progress-bar-container">
            <div class="progress-bar" id="progressBar"></div>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Placa</th>
                        <th>Renavam</th>
                        <th>Nome Informado</th>
                        <th>Nome Proprietário</th>
                        <th>Data CRLV</th>
                        <th>OBS</th>
                        <th style="width: 120px;">Status</th>
                    </tr>
                </thead>
                <tbody id="resultsTable"></tbody>
            </table>
        </div>

        <div class="action-buttons">
            <button type="button" class="admin-button admin-button--primary" id="startBtn" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
                Iniciar Verificação
            </button>

            <button type="button" class="admin-button admin-button--danger" id="stopBtn" style="display: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="6" y="6" width="12" height="12"></rect>
                </svg>
                Parar
            </button>

            <button type="button" class="admin-button admin-button--secondary" id="downloadBtn" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Baixar Resultado
            </button>

            <button type="button" class="admin-button admin-button--secondary" id="resetBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                    <path d="M3 3v5h5"></path>
                </svg>
                Nova Verificação
            </button>
        </div>
    </section>

    <script>
        (function() {
            const uploadZone = document.getElementById('uploadZone');
            const fileInput = document.getElementById('fileInput');
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileMeta = document.getElementById('fileMeta');
            const removeFile = document.getElementById('removeFile');
            const progressSection = document.getElementById('progressSection');
            const progressBar = document.getElementById('progressBar');
            const resultsTable = document.getElementById('resultsTable');
            const totalCount = document.getElementById('totalCount');
            const processedCount = document.getElementById('processedCount');
            const errorCount = document.getElementById('errorCount');
            const startBtn = document.getElementById('startBtn');
            const stopBtn = document.getElementById('stopBtn');
            const downloadBtn = document.getElementById('downloadBtn');
            const resetBtn = document.getElementById('resetBtn');
            const alertContainer = document.getElementById('alertContainer');

            let rows = [];
            let isProcessing = false;
            let shouldStop = false;
            let currentFile = null;

            // Upload zone events
            uploadZone.addEventListener('click', () => fileInput.click());

            uploadZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadZone.classList.add('is-dragover');
            });

            uploadZone.addEventListener('dragleave', () => {
                uploadZone.classList.remove('is-dragover');
            });

            uploadZone.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadZone.classList.remove('is-dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFile(files[0]);
                }
            });

            fileInput.addEventListener('change', () => {
                if (fileInput.files.length > 0) {
                    handleFile(fileInput.files[0]);
                }
            });

            removeFile.addEventListener('click', resetAll);
            resetBtn.addEventListener('click', resetAll);

            startBtn.addEventListener('click', startProcessing);
            stopBtn.addEventListener('click', () => {
                shouldStop = true;
                stopBtn.disabled = true;
            });

            downloadBtn.addEventListener('click', () => {
                window.location.href = '{{ route("admin.transfer-verification.download") }}';
            });

            function showAlert(message, type = 'error') {
                alertContainer.innerHTML = `<div class="alert alert--${type}">${message}</div>`;
                setTimeout(() => {
                    alertContainer.innerHTML = '';
                }, 5000);
            }

            function handleFile(file) {
                if (!file.name.match(/\.xlsx?$/i)) {
                    showAlert('Por favor, selecione um arquivo .xlsx');
                    return;
                }

                if (file.size > 10 * 1024 * 1024) {
                    showAlert('O arquivo deve ter no máximo 10MB');
                    return;
                }

                currentFile = file;
                uploadFile(file);
            }

            async function uploadFile(file) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', '{{ csrf_token() }}');

                uploadZone.classList.add('is-disabled');

                try {
                    const response = await fetch('{{ route("admin.transfer-verification.upload") }}', {
                        method: 'POST',
                        body: formData,
                    });

                    const data = await response.json();

                    if (!data.success) {
                        showAlert(data.message || 'Erro ao processar arquivo');
                        uploadZone.classList.remove('is-disabled');
                        return;
                    }

                    rows = data.rows;
                    showFileInfo(file, data.total);
                    showProgressSection();
                    populateTable();
                    startBtn.disabled = false;

                } catch (error) {
                    showAlert('Erro ao enviar arquivo: ' + error.message);
                    uploadZone.classList.remove('is-disabled');
                }
            }

            function showFileInfo(file, total) {
                fileName.textContent = file.name;
                fileMeta.textContent = `${formatFileSize(file.size)} • ${total} registros`;
                fileInfo.style.display = 'flex';
                uploadZone.style.display = 'none';
            }

            function formatFileSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            }

            function showProgressSection() {
                progressSection.classList.add('is-visible');
                totalCount.textContent = rows.length;
                processedCount.textContent = '0';
                errorCount.textContent = '0';
            }

            function populateTable() {
                resultsTable.innerHTML = rows.map((row, index) => `
                    <tr id="row-${index}">
                        <td>${index + 1}</td>
                        <td>${escapeHtml(row.placa)}</td>
                        <td>${escapeHtml(row.renavam)}</td>
                        <td>${escapeHtml(row.nome)}</td>
                        <td class="cell-proprietario">-</td>
                        <td class="cell-crlv">-</td>
                        <td class="cell-obs">-</td>
                        <td><span class="status-badge pending">Pendente</span></td>
                    </tr>
                `).join('');
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }

            async function startProcessing() {
                if (isProcessing) return;

                isProcessing = true;
                shouldStop = false;
                startBtn.style.display = 'none';
                stopBtn.style.display = 'inline-flex';
                stopBtn.disabled = false;
                downloadBtn.disabled = true;

                let processed = 0;
                let errors = 0;

                for (let i = 0; i < rows.length; i++) {
                    if (shouldStop) {
                        break;
                    }

                    const row = rows[i];

                    // Skip already processed rows
                    if (row.status !== 'pending') {
                        continue;
                    }

                    updateRowStatus(i, 'processing');

                    try {
                        const result = await verifyRow(i, row);

                        if (result.success) {
                            updateRowResult(i, result);
                            row.status = 'success';
                        } else {
                            updateRowError(i, result.error || result.obs);
                            row.status = 'error';
                            errors++;
                        }
                    } catch (error) {
                        updateRowError(i, error.message);
                        row.status = 'error';
                        errors++;
                    }

                    processed++;
                    updateProgress(processed, errors);

                    // Small delay between requests
                    if (i < rows.length - 1 && !shouldStop) {
                        await sleep(500);
                    }
                }

                isProcessing = false;
                startBtn.style.display = 'inline-flex';
                stopBtn.style.display = 'none';
                downloadBtn.disabled = false;

                if (shouldStop) {
                    showAlert('Processamento interrompido pelo usuário', 'warning');
                } else {
                    showAlert('Processamento concluído!', 'success');
                    progressBar.classList.add('is-complete');
                }
            }

            async function verifyRow(index, row) {
                const response = await fetch('{{ route("admin.transfer-verification.verify") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        index: index,
                        placa: row.placa,
                        renavam: row.renavam,
                        nome: row.nome,
                    }),
                });

                return await response.json();
            }

            function updateRowStatus(index, status) {
                const tr = document.getElementById(`row-${index}`);
                if (!tr) return;

                tr.className = `is-${status}`;
                const statusCell = tr.querySelector('td:last-child');

                const statusLabels = {
                    pending: 'Pendente',
                    processing: 'Processando...',
                    success: 'Concluído',
                    error: 'Erro',
                };

                statusCell.innerHTML = `<span class="status-badge ${status}">${status === 'processing' ? '<span class="spinner"></span>' : ''}${statusLabels[status]}</span>`;
            }

            function updateRowResult(index, result) {
                const tr = document.getElementById(`row-${index}`);
                if (!tr) return;

                tr.className = result.obs && result.obs.includes('NÃO CONCLUÍDA') ? 'is-warning' : 'is-success';

                tr.querySelector('.cell-proprietario').textContent = result.nome_proprietario || '-';
                tr.querySelector('.cell-crlv').textContent = result.data_crlv || '-';

                const obsCell = tr.querySelector('.cell-obs');
                if (result.obs && result.obs.includes('NÃO CONCLUÍDA')) {
                    obsCell.innerHTML = `<span class="obs-badge transfer-not-completed">${escapeHtml(result.obs)}</span>`;
                } else if (result.obs) {
                    obsCell.textContent = result.obs;
                } else {
                    obsCell.innerHTML = '<span class="obs-badge transfer-completed">OK</span>';
                }

                updateRowStatus(index, 'success');
            }

            function updateRowError(index, error) {
                const tr = document.getElementById(`row-${index}`);
                if (!tr) return;

                tr.querySelector('.cell-obs').textContent = error || 'Erro desconhecido';
                updateRowStatus(index, 'error');
            }

            function updateProgress(processed, errors) {
                const percent = (processed / rows.length) * 100;
                progressBar.style.width = `${percent}%`;
                processedCount.textContent = processed;
                errorCount.textContent = errors;

                if (errors > 0) {
                    progressBar.classList.add('is-error');
                }
            }

            function sleep(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            function resetAll() {
                rows = [];
                currentFile = null;
                isProcessing = false;
                shouldStop = false;

                fileInput.value = '';
                fileInfo.style.display = 'none';
                uploadZone.style.display = 'block';
                uploadZone.classList.remove('is-disabled');
                progressSection.classList.remove('is-visible');
                progressBar.style.width = '0%';
                progressBar.classList.remove('is-complete', 'is-error');
                resultsTable.innerHTML = '';
                totalCount.textContent = '0';
                processedCount.textContent = '0';
                errorCount.textContent = '0';
                startBtn.disabled = true;
                startBtn.style.display = 'inline-flex';
                stopBtn.style.display = 'none';
                downloadBtn.disabled = true;
                alertContainer.innerHTML = '';
            }
        })();
    </script>
@endsection
