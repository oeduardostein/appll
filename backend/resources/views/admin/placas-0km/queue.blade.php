@extends('admin.layouts.app')

@section('content')
    <style>
        .queue-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .queue-header h1 {
            margin: 0;
            font-size: 24px;
            color: var(--text-strong);
        }

        .queue-header p {
            margin: 8px 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .queue-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-strong);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 14px;
        }

        .queue-grid {
            display: grid;
            gap: 18px;
        }

        @media (min-width: 1024px) {
            .queue-grid {
                grid-template-columns: 1.1fr 1fr;
            }
        }

        .queue-card {
            padding: 20px;
        }

        .queue-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .queue-field {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
        }

        .queue-field input {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 8px 10px;
            width: 120px;
            font-size: 14px;
            color: var(--text-strong);
        }

        .queue-button {
            border: none;
            border-radius: 10px;
            background: var(--brand-primary);
            color: #fff;
            padding: 9px 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .queue-button:hover {
            background: var(--brand-primary-hover);
        }

        .queue-muted {
            color: var(--text-muted);
            font-size: 13px;
        }

        .queue-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--surface-muted);
            padding: 6px 10px;
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .queue-status-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .queue-bar {
            height: 10px;
            border-radius: 999px;
            background: #edf2f8;
            overflow: hidden;
            margin-top: 12px;
        }

        .queue-bar > div {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #0b4ea2, #2d74cc);
            transition: width 180ms ease;
        }

        .queue-table-wrap {
            margin-top: 10px;
            overflow: auto;
        }

        .queue-table {
            width: 100%;
            border-collapse: collapse;
        }

        .queue-table th,
        .queue-table td {
            border-bottom: 1px solid #ecf1f8;
            padding: 10px 8px;
            font-size: 12px;
            vertical-align: top;
            text-align: left;
        }

        .queue-table th {
            color: var(--text-muted);
            font-weight: 700;
        }

        .queue-tag {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 700;
            background: #e2e8f0;
            color: #334155;
        }

        .queue-tag--ok {
            background: #dcfce7;
            color: #166534;
        }

        .queue-tag--err {
            background: #fee2e2;
            color: #991b1b;
        }

        .queue-tag--run {
            background: #fef3c7;
            color: #92400e;
        }

        .queue-link-batch {
            color: var(--brand-primary);
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .queue-thumb {
            width: 72px;
            height: 46px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #f8fafc;
            cursor: pointer;
            display: block;
        }

        .queue-requests {
            margin-top: 14px;
            display: grid;
            gap: 12px;
        }

        .queue-request {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #fff;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .queue-request__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        .queue-request__id {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 700;
            background: var(--surface-muted);
            color: var(--text-strong);
        }

        .queue-request__fields {
            display: grid;
            gap: 8px;
        }

        .queue-request__field {
            display: grid;
            gap: 3px;
        }

        .queue-request__label {
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .queue-request__value {
            color: var(--text-strong);
            font-size: 14px;
            line-height: 1.35;
        }

        .queue-request__plates {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        }

        .queue-request__plate {
            padding: 10px 12px;
            border-radius: 12px;
            background: var(--surface-muted);
            color: var(--text-strong);
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            line-height: 1.2;
        }

        .queue-request__plate::before {
            content: '';
            width: 14px;
            height: 14px;
            border-radius: 999px;
            border: 2px solid #bfd0e6;
            background: #fff;
            flex-shrink: 0;
        }

        .queue-request__meta {
            display: grid;
            gap: 8px;
        }

        .queue-request__error {
            white-space: pre-wrap;
            word-break: break-word;
            color: #991b1b;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 12px;
        }

        .queue-empty {
            padding: 12px 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .queue-error {
            margin-top: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #fecaca;
            background: #fee2e2;
            color: #991b1b;
            font-size: 13px;
            display: none;
        }

        .image-modal {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 24px;
        }

        .image-modal.is-open {
            display: flex;
        }

        .image-modal__content {
            max-width: min(1200px, 92vw);
            max-height: 90vh;
            background: #fff;
            border-radius: 14px;
            padding: 12px;
        }

        .image-modal__img {
            max-width: 100%;
            max-height: calc(90vh - 24px);
            display: block;
            border-radius: 10px;
        }
    </style>

    <div class="queue-header">
        <div>
            <h1>Fila Placas 0KM (Admin)</h1>
            <p>Acompanhamento da fila com preview da imagem enviada pelo agente.</p>
        </div>
        <a class="queue-link" href="{{ route('admin.placas-0km.index') }}">Voltar para consulta manual</a>
    </div>

    <div class="queue-grid">
        <section class="admin-card queue-card">
            <h2 style="margin: 0 0 10px; color: var(--text-strong); font-size: 18px;">Status da fila</h2>

            <div class="queue-actions">
                <label class="queue-field" for="batchIdInput">
                    Batch
                    <input id="batchIdInput" type="number" min="1" placeholder="Ex: 56">
                </label>
                <button class="queue-button" id="loadBatchBtn" type="button">Carregar batch</button>
                <button class="queue-button" id="refreshBtn" type="button">Atualizar</button>
            </div>

            <div class="queue-muted" id="statusText" style="margin-top: 12px;">Nenhum batch selecionado.</div>
            <div class="queue-bar"><div id="progressFill"></div></div>
            <div class="queue-status-row">
                <span class="queue-pill" id="batchPill">Batch: —</span>
                <span class="queue-pill" id="runnerPill">Runner: —</span>
            </div>
            <div class="queue-muted" id="currentText" style="margin-top: 10px;">Atual: —</div>
            <div class="queue-muted" id="countsText" style="margin-top: 6px;">—</div>
            <div class="queue-error" id="errorBox"></div>

            <div id="rowsBody" class="queue-requests">
                <div class="queue-empty">Sem itens carregados.</div>
            </div>
        </section>

        <section class="admin-card queue-card">
            <h2 style="margin: 0 0 10px; color: var(--text-strong); font-size: 18px;">Últimos batches</h2>
            <div class="queue-muted" id="batchesText">Carregando...</div>
            <div class="queue-table-wrap">
                <table class="queue-table">
                    <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Processado</th>
                        <th>OK</th>
                        <th>Falhas</th>
                    </tr>
                    </thead>
                    <tbody id="batchesBody">
                    <tr><td colspan="6" class="queue-empty">—</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="image-modal" id="imageModal" role="dialog" aria-modal="true">
        <div class="image-modal__content">
            <img class="image-modal__img" id="imageModalImg" alt="Screenshot da consulta">
        </div>
    </div>

    <script>
        (function () {
            const BATCHES_URL = '{{ route('admin.placas-0km.queue.batches') }}';
            const BATCH_SHOW_BASE_URL = '{{ url('/admin/placas-0km/fila/batches') }}';
            const STORAGE_URL_BASE = @json(asset('storage'));
            const SHOW_REQUEST_IMAGE = false; // Troque para true para voltar a exibir a imagem da requisição.

            const batchIdInput = document.getElementById('batchIdInput');
            const loadBatchBtn = document.getElementById('loadBatchBtn');
            const refreshBtn = document.getElementById('refreshBtn');
            const statusText = document.getElementById('statusText');
            const batchPill = document.getElementById('batchPill');
            const runnerPill = document.getElementById('runnerPill');
            const currentText = document.getElementById('currentText');
            const countsText = document.getElementById('countsText');
            const progressFill = document.getElementById('progressFill');
            const rowsBody = document.getElementById('rowsBody');
            const errorBox = document.getElementById('errorBox');
            const batchesText = document.getElementById('batchesText');
            const batchesBody = document.getElementById('batchesBody');
            const imageModal = document.getElementById('imageModal');
            const imageModalImg = document.getElementById('imageModalImg');

            const urlParams = new URLSearchParams(window.location.search);
            const initialBatchId = Number(urlParams.get('batch_id') || 0);
            let currentBatchId = null;
            let pollingTimer = null;

            function showError(message) {
                errorBox.textContent = message;
                errorBox.style.display = 'block';
            }

            function clearError() {
                errorBox.textContent = '';
                errorBox.style.display = 'none';
            }

            function tagHtml(status) {
                const s = String(status || '').toLowerCase();
                if (s === 'succeeded') return '<span class="queue-tag queue-tag--ok">OK</span>';
                if (s === 'failed') return '<span class="queue-tag queue-tag--err">FALHA</span>';
                if (s === 'running') return '<span class="queue-tag queue-tag--run">RODANDO</span>';
                return '<span class="queue-tag">PENDENTE</span>';
            }

            function toSafeText(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function normalizePlate(value) {
                const cleaned = String(value ?? '')
                    .replace(/[^A-Za-z0-9]/g, '')
                    .toUpperCase();
                if (cleaned.length === 7) {
                    return `${cleaned.slice(0, 3)}-${cleaned.slice(3)}`;
                }
                return cleaned;
            }

            function extractPlates(payload) {
                const seen = new Set();
                const out = [];
                const sources = [
                    payload?.data?.ocr?.plates,
                    payload?.data?.placas,
                ];

                for (const source of sources) {
                    if (!Array.isArray(source)) continue;
                    for (const plateRaw of source) {
                        const plate = normalizePlate(plateRaw);
                        if (!plate || seen.has(plate)) continue;
                        seen.add(plate);
                        out.push(plate);
                    }
                }

                return out;
            }

            function extractScreenshotUrl(payload) {
                const direct = payload?.data?.screenshot_url;
                if (typeof direct === 'string' && direct.trim() !== '') return direct;
                const path = payload?.data?.screenshot_path;
                if (typeof path === 'string' && path.trim() !== '') {
                    return `${STORAGE_URL_BASE.replace(/\/$/, '')}/${path.replace(/^\//, '')}`;
                }
                return '';
            }

            function openImage(url) {
                imageModalImg.src = url;
                imageModal.classList.add('is-open');
            }

            function closeImage() {
                imageModal.classList.remove('is-open');
                imageModalImg.src = '';
            }

            async function refreshBatches() {
                try {
                    const resp = await fetch(BATCHES_URL, { headers: { 'Accept': 'application/json' } });
                    const json = await resp.json().catch(() => null);
                    if (!resp.ok || !json?.success) throw new Error(json?.error || `HTTP ${resp.status}`);

                    const list = json.data?.batches || [];
                    batchesText.textContent = list.length ? 'Clique no batch para abrir os detalhes.' : 'Nenhum batch disponível.';
                    batchesBody.innerHTML = '';

                    if (!list.length) {
                        batchesBody.innerHTML = '<tr><td colspan="6" class="queue-empty">Sem registros.</td></tr>';
                        return;
                    }

                    for (const b of list) {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td><a class="queue-link-batch" data-batch-id="${b.id}">#${b.id}</a></td>
                            <td>${toSafeText(b.status)}</td>
                            <td>${Number(b.total || 0)}</td>
                            <td>${Number(b.processed || 0)}</td>
                            <td>${Number(b.succeeded || 0)}</td>
                            <td>${Number(b.failed || 0)}</td>
                        `;
                        batchesBody.appendChild(row);
                    }
                } catch (e) {
                    batchesText.textContent = 'Falha ao carregar batches.';
                }
            }

            async function refreshStatus() {
                if (!currentBatchId) return;
                try {
                    const resp = await fetch(`${BATCH_SHOW_BASE_URL}/${currentBatchId}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const json = await resp.json().catch(() => null);
                    if (!resp.ok || !json?.success) throw new Error(json?.error || `HTTP ${resp.status}`);

                    const batch = json.data?.batch;
                    const runner = json.data?.runner;
                    const current = json.data?.current;
                    const requests = json.data?.requests || [];

                    batchPill.textContent = `Batch: #${batch?.id ?? currentBatchId}`;
                    runnerPill.textContent = `Runner: ${runner?.is_running ? 'rodando' : 'ocioso'}${runner?.current_request_id ? ' (#' + runner.current_request_id + ')' : ''}`;

                    const total = Number(batch?.total || 0);
                    const processed = Number(batch?.processed || 0);
                    const succeeded = Number(batch?.succeeded || 0);
                    const failed = Number(batch?.failed || 0);
                    const pct = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 0;
                    progressFill.style.width = `${pct}%`;

                    statusText.textContent = batch?.status === 'completed'
                        ? 'Batch concluído.'
                        : 'Batch em processamento.';
                    countsText.textContent = `Status: ${batch?.status} • Total ${total} • Processado ${processed} • OK ${succeeded} • Falhas ${failed}`;
                    currentText.textContent = current
                        ? `Atual: #${current.id} • ${current.cpf_cgc} • ${current.chassi}`
                        : 'Atual: —';

                    rowsBody.innerHTML = '';
                    if (!requests.length) {
                        rowsBody.innerHTML = '<div class="queue-empty">Sem itens.</div>';
                        return;
                    }

                    for (const r of requests) {
                        const plates = extractPlates(r.response_payload);
                        const screenshotUrl = extractScreenshotUrl(r.response_payload);
                        const platesHtml = plates.length
                            ? plates
                                .map((plate) => `<div class="queue-request__plate">${toSafeText(plate)}</div>`)
                                .join('')
                            : '<div class="queue-empty" style="padding: 0;">Nenhuma placa listada.</div>';
                        const screenshotHtml = SHOW_REQUEST_IMAGE && screenshotUrl
                            ? `
                                <div class="queue-request__field">
                                    <span class="queue-request__label">Imagem</span>
                                    <img class="queue-thumb" src="${toSafeText(screenshotUrl)}" data-image-url="${toSafeText(screenshotUrl)}" alt="Screenshot #${Number(r.id)}">
                                </div>
                            `
                            : '';
                        const errorHtml = r.response_error
                            ? `<div class="queue-request__error">${toSafeText(r.response_error)}</div>`
                            : '';

                        const row = document.createElement('article');
                        row.className = 'queue-request';
                        row.innerHTML = `
                            <div class="queue-request__head">
                                <span class="queue-request__id">Req #${Number(r.id)}</span>
                                ${tagHtml(r.status)}
                            </div>
                            <div class="queue-request__fields">
                                <div class="queue-request__field">
                                    <span class="queue-request__label">CPF/CNPJ</span>
                                    <span class="queue-request__value">${toSafeText(r.cpf_cgc)}</span>
                                </div>
                                <div class="queue-request__field">
                                    <span class="queue-request__label">Nome</span>
                                    <span class="queue-request__value">${toSafeText(r.nome || '') || '—'}</span>
                                </div>
                                <div class="queue-request__field">
                                    <span class="queue-request__label">Chassi</span>
                                    <span class="queue-request__value">${toSafeText(r.chassi || '') || '—'}</span>
                                </div>
                                <div class="queue-request__field">
                                    <span class="queue-request__label">Complemento</span>
                                    <span class="queue-request__value">${toSafeText(r.numeros || '') || '—'}</span>
                                </div>
                                <div class="queue-request__field">
                                    <span class="queue-request__label">Placas disponíveis</span>
                                    <div class="queue-request__plates">${platesHtml}</div>
                                </div>
                            </div>
                            <div class="queue-request__meta">
                                ${screenshotHtml}
                                ${errorHtml}
                            </div>
                        `;
                        rowsBody.appendChild(row);
                    }
                } catch (e) {
                    showError(String(e?.message || e));
                }
            }

            function startPolling() {
                if (pollingTimer) clearInterval(pollingTimer);
                pollingTimer = setInterval(refreshStatus, 2000);
            }

            function setCurrentBatchId(batchId) {
                currentBatchId = String(batchId);
                batchIdInput.value = currentBatchId;
                clearError();
                refreshStatus();
                startPolling();
            }

            loadBatchBtn.addEventListener('click', () => {
                const id = Number(batchIdInput.value || 0);
                if (!Number.isFinite(id) || id <= 0) {
                    showError('Informe um batch válido.');
                    return;
                }
                setCurrentBatchId(id);
            });

            refreshBtn.addEventListener('click', async () => {
                clearError();
                await refreshBatches();
                await refreshStatus();
            });

            batchesBody.addEventListener('click', (event) => {
                const el = event.target.closest('[data-batch-id]');
                if (!el) return;
                const id = Number(el.getAttribute('data-batch-id') || 0);
                if (!id) return;
                setCurrentBatchId(id);
            });

            rowsBody.addEventListener('click', (event) => {
                const img = event.target.closest('[data-image-url]');
                if (!img) return;
                const url = img.getAttribute('data-image-url') || '';
                if (!url) return;
                openImage(url);
            });

            imageModal.addEventListener('click', (event) => {
                if (event.target === imageModal) closeImage();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeImage();
            });

            refreshBatches().then(() => {
                if (Number.isFinite(initialBatchId) && initialBatchId > 0) {
                    setCurrentBatchId(initialBatchId);
                }
            });
        })();
    </script>
@endsection
