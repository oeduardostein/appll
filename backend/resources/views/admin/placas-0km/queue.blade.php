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

        .queue-pill--danger {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
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

        .queue-tag--stuck {
            background: #fee2e2;
            color: #991b1b;
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

        .queue-request--stuck {
            border-color: #fecaca;
            background: #fff7f7;
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

        .queue-filters {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .queue-filter-btn {
            border: 1px solid var(--border);
            border-radius: 999px;
            background: #fff;
            color: var(--text-strong);
            font-weight: 700;
            font-size: 12px;
            padding: 7px 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 120ms ease, border-color 120ms ease, color 120ms ease;
        }

        .queue-filter-btn:hover {
            border-color: #b8c8db;
            background: #f8fafc;
        }

        .queue-filter-btn.is-active {
            border-color: #0b4ea2;
            background: #eaf2ff;
            color: #0b4ea2;
        }

        .queue-filter-btn__count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 20px;
            border-radius: 999px;
            background: #edf2f8;
            color: #1e293b;
            font-size: 11px;
            padding: 0 6px;
        }

        .queue-insight-grid {
            margin-top: 14px;
            display: grid;
            gap: 10px;
        }

        .queue-insight {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            padding: 10px 12px;
            display: grid;
            gap: 8px;
        }

        .queue-insight__title {
            margin: 0;
            color: var(--text-strong);
            font-size: 13px;
            font-weight: 700;
        }

        .queue-insight__line {
            color: var(--text-default);
            font-size: 13px;
            line-height: 1.35;
        }

        .queue-insight__line strong {
            color: var(--text-strong);
        }

        .queue-insight-list {
            display: grid;
            gap: 6px;
        }

        .queue-insight-item {
            border: 1px solid #e5edf7;
            border-radius: 10px;
            background: #f8fbff;
            padding: 8px 10px;
            display: grid;
            gap: 4px;
        }

        .queue-insight-item--error {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .queue-insight-item__title {
            color: #334155;
            font-size: 12px;
            font-weight: 700;
        }

        .queue-insight-item__desc {
            color: #475569;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-word;
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
            <h1>Controle de Consultas de Placas 0KM</h1>
            <p>Acompanhe consultas rodando, travadas, pendentes, completas e com falha.</p>
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
            <div class="queue-filters" id="statusFilters" aria-label="Filtros de status">
                <button class="queue-filter-btn is-active" type="button" data-filter-status="all">
                    Todas
                    <span class="queue-filter-btn__count" data-filter-count="all">0</span>
                </button>
                <button class="queue-filter-btn" type="button" data-filter-status="running">
                    Rodando
                    <span class="queue-filter-btn__count" data-filter-count="running">0</span>
                </button>
                <button class="queue-filter-btn" type="button" data-filter-status="stuck">
                    Travadas
                    <span class="queue-filter-btn__count" data-filter-count="stuck">0</span>
                </button>
                <button class="queue-filter-btn" type="button" data-filter-status="pending">
                    Pendentes
                    <span class="queue-filter-btn__count" data-filter-count="pending">0</span>
                </button>
                <button class="queue-filter-btn" type="button" data-filter-status="completed">
                    Completas
                    <span class="queue-filter-btn__count" data-filter-count="completed">0</span>
                </button>
                <button class="queue-filter-btn" type="button" data-filter-status="failed">
                    Falhas
                    <span class="queue-filter-btn__count" data-filter-count="failed">0</span>
                </button>
            </div>

            <div class="queue-insight-grid">
                <section class="queue-insight">
                    <h3 class="queue-insight__title">Diagnóstico da execução</h3>
                    <div class="queue-insight__line" id="occupiedReasonText">Runner livre.</div>
                    <div class="queue-insight__line" id="heartbeatText">Heartbeat: —</div>
                    <div class="queue-insight__line" id="currentAgeText">Execução atual: —</div>
                </section>

                <section class="queue-insight">
                    <h3 class="queue-insight__title">Falhas recentes</h3>
                    <div class="queue-insight__line" id="latestGlobalFailureText">Última falha global: nenhuma.</div>
                    <div class="queue-insight-list" id="recentFailuresBody">
                        <div class="queue-empty" style="padding: 0;">Sem falhas neste batch.</div>
                    </div>
                </section>

                <section class="queue-insight">
                    <h3 class="queue-insight__title">Eventos recentes do batch</h3>
                    <div class="queue-insight-list" id="eventsBody">
                        <div class="queue-empty" style="padding: 0;">Sem eventos.</div>
                    </div>
                </section>
            </div>
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
            const DEFAULT_STALE_MINUTES = 10;
            const STATUS_KEYS = new Set(['all', 'running', 'stuck', 'pending', 'completed', 'failed']);
            const STATUS_LABELS = {
                all: 'todas',
                running: 'rodando',
                stuck: 'travadas',
                pending: 'pendentes',
                completed: 'completas',
                failed: 'com falha',
            };

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
            const statusFilters = document.getElementById('statusFilters');
            const occupiedReasonText = document.getElementById('occupiedReasonText');
            const heartbeatText = document.getElementById('heartbeatText');
            const currentAgeText = document.getElementById('currentAgeText');
            const latestGlobalFailureText = document.getElementById('latestGlobalFailureText');
            const recentFailuresBody = document.getElementById('recentFailuresBody');
            const eventsBody = document.getElementById('eventsBody');
            const imageModal = document.getElementById('imageModal');
            const imageModalImg = document.getElementById('imageModalImg');

            const urlParams = new URLSearchParams(window.location.search);
            const initialBatchId = Number(urlParams.get('batch_id') || 0);
            let currentBatchId = null;
            let pollingTimer = null;
            let activeStatusFilter = 'all';
            let latestRequests = [];
            let latestRunner = null;
            let latestStaleMinutes = DEFAULT_STALE_MINUTES;
            let latestSummary = {
                total: 0,
                running: 0,
                stuck: 0,
                pending: 0,
                completed: 0,
                failed: 0,
            };
            let latestDiagnostics = {};
            let latestRecentFailures = [];
            let latestGlobalFailure = null;
            let latestEvents = [];

            function toCount(value) {
                const n = Number(value);
                if (!Number.isFinite(n) || n < 0) return 0;
                return Math.floor(n);
            }

            function showError(message) {
                errorBox.textContent = message;
                errorBox.style.display = 'block';
            }

            function clearError() {
                errorBox.textContent = '';
                errorBox.style.display = 'none';
            }

            function toSafeText(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function tagHtml(statusKey) {
                if (statusKey === 'completed') return '<span class="queue-tag queue-tag--ok">COMPLETA</span>';
                if (statusKey === 'failed') return '<span class="queue-tag queue-tag--err">FALHA</span>';
                if (statusKey === 'stuck') return '<span class="queue-tag queue-tag--stuck">TRAVADA</span>';
                if (statusKey === 'running') return '<span class="queue-tag queue-tag--run">RODANDO</span>';
                return '<span class="queue-tag">PENDENTE</span>';
            }

            function parseDateMs(value) {
                const ts = Date.parse(String(value || ''));
                return Number.isFinite(ts) ? ts : null;
            }

            function formatDateTime(value) {
                const ts = parseDateMs(value);
                if (!ts) return '—';
                return new Date(ts).toLocaleString('pt-BR');
            }

            function formatSecondsAge(seconds) {
                const total = toCount(seconds);
                if (!total) return '0s';
                if (total < 60) return `${total}s`;
                const minutes = Math.floor(total / 60);
                const remainSeconds = total % 60;
                if (minutes < 60) return `${minutes}m ${remainSeconds}s`;
                const hours = Math.floor(minutes / 60);
                const remainMinutes = minutes % 60;
                return `${hours}h ${remainMinutes}m`;
            }

            function shortText(value, max = 260) {
                const text = String(value ?? '').trim();
                if (text.length <= max) return text;
                return `${text.slice(0, max)}...`;
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
                const sources = [payload?.data?.ocr?.plates, payload?.data?.placas];

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

            function isRunnerStuck(runner, staleMinutes) {
                if (!runner || Number(runner?.is_running) !== 1) return false;
                const heartbeatTs = parseDateMs(runner?.last_heartbeat_at);
                if (!heartbeatTs) return true;
                return (Date.now() - heartbeatTs) >= staleMinutes * 60 * 1000;
            }

            function normalizeRequestStatus(requestItem, runner, staleMinutes) {
                const displayStatus = String(requestItem?.display_status || '').toLowerCase();
                if (STATUS_KEYS.has(displayStatus) && displayStatus !== 'all') return displayStatus;

                const rawStatus = String(requestItem?.status || '').toLowerCase();
                if (rawStatus === 'succeeded') return 'completed';
                if (rawStatus === 'failed') return 'failed';
                if (rawStatus === 'pending') return 'pending';
                if (rawStatus !== 'running') return 'pending';

                if (requestItem?.is_stuck === true) return 'stuck';

                const startedAtTs = parseDateMs(requestItem?.started_at);
                if (!startedAtTs) return 'stuck';
                if ((Date.now() - startedAtTs) >= staleMinutes * 60 * 1000) return 'stuck';

                const runnerIsStuck = isRunnerStuck(runner, staleMinutes);
                if (runnerIsStuck && Number(runner?.current_request_id || 0) === Number(requestItem?.id || 0)) {
                    return 'stuck';
                }

                return 'running';
            }

            function fallbackSummaryFromRequests(requests, runner, staleMinutes) {
                const base = { total: 0, running: 0, stuck: 0, pending: 0, completed: 0, failed: 0 };
                for (const requestItem of requests) {
                    const key = normalizeRequestStatus(requestItem, runner, staleMinutes);
                    base.total += 1;
                    if (key in base) {
                        base[key] += 1;
                    }
                }
                return base;
            }

            function resolveSummary(summaryRaw, requests, runner, staleMinutes) {
                const fallback = fallbackSummaryFromRequests(requests, runner, staleMinutes);
                return {
                    total: toCount(summaryRaw?.total ?? fallback.total),
                    running: toCount(summaryRaw?.running ?? fallback.running),
                    stuck: toCount(summaryRaw?.stuck ?? fallback.stuck),
                    pending: toCount(summaryRaw?.pending ?? fallback.pending),
                    completed: toCount(summaryRaw?.completed ?? fallback.completed),
                    failed: toCount(summaryRaw?.failed ?? fallback.failed),
                };
            }

            function setActiveFilter(filterKey) {
                activeStatusFilter = STATUS_KEYS.has(filterKey) ? filterKey : 'all';

                const buttons = statusFilters.querySelectorAll('[data-filter-status]');
                for (const button of buttons) {
                    const key = String(button.getAttribute('data-filter-status') || '');
                    button.classList.toggle('is-active', key === activeStatusFilter);
                }
            }

            function updateFilterCounts(summary) {
                const counts = {
                    all: toCount(summary.total),
                    running: toCount(summary.running),
                    stuck: toCount(summary.stuck),
                    pending: toCount(summary.pending),
                    completed: toCount(summary.completed),
                    failed: toCount(summary.failed),
                };

                const countNodes = statusFilters.querySelectorAll('[data-filter-count]');
                for (const node of countNodes) {
                    const key = String(node.getAttribute('data-filter-count') || '');
                    node.textContent = String(counts[key] ?? 0);
                }
            }

            function renderDiagnostics() {
                const occupiedReason = String(latestDiagnostics?.occupied_reason || '').trim();
                occupiedReasonText.textContent = occupiedReason !== '' ? occupiedReason : 'Sem diagnóstico disponível.';

                const heartbeatAge = latestDiagnostics?.heartbeat_age_human
                    ? `${latestDiagnostics.heartbeat_age_human} atrás`
                    : 'não informado';
                heartbeatText.textContent = `Heartbeat: ${heartbeatAge}`;

                const currentAge = latestDiagnostics?.current_request_age_human
                    ? `${latestDiagnostics.current_request_age_human} em execução`
                    : 'sem execução ativa';
                currentAgeText.textContent = `Execução atual: ${currentAge}`;
            }

            function renderRecentFailures() {
                const globalFailure = latestGlobalFailure;
                if (globalFailure?.id) {
                    latestGlobalFailureText.textContent =
                        `Última falha global: Req #${toCount(globalFailure.id)} (Batch #${toCount(globalFailure.batch_id)}) em ${formatDateTime(globalFailure.finished_at || globalFailure.updated_at)}.`;
                } else {
                    latestGlobalFailureText.textContent = 'Última falha global: nenhuma.';
                }

                recentFailuresBody.innerHTML = '';
                if (!latestRecentFailures.length) {
                    recentFailuresBody.innerHTML = '<div class="queue-empty" style="padding: 0;">Sem falhas neste batch.</div>';
                    return;
                }

                for (const failureItem of latestRecentFailures) {
                    const title = `Req #${toCount(failureItem.id)} • Tentativas ${Math.max(1, toCount(failureItem.attempts))} • ${formatDateTime(failureItem.finished_at || failureItem.updated_at)}`;
                    const desc = shortText(failureItem.response_error || 'Sem mensagem de erro.');

                    const row = document.createElement('div');
                    row.className = 'queue-insight-item queue-insight-item--error';
                    row.innerHTML = `
                        <div class="queue-insight-item__title">${toSafeText(title)}</div>
                        <div class="queue-insight-item__desc">${toSafeText(desc)}</div>
                    `;
                    recentFailuresBody.appendChild(row);
                }
            }

            function renderEvents() {
                eventsBody.innerHTML = '';
                if (!latestEvents.length) {
                    eventsBody.innerHTML = '<div class="queue-empty" style="padding: 0;">Sem eventos.</div>';
                    return;
                }

                for (const eventItem of latestEvents) {
                    const statusKey = normalizeRequestStatus(eventItem, latestRunner, latestStaleMinutes);
                    const title = `Req #${toCount(eventItem.id)} • ${String(statusKey).toUpperCase()} • Tentativas ${Math.max(1, toCount(eventItem.attempts))}`;
                    const runningSeconds = toCount(eventItem.running_seconds);
                    const runningText = runningSeconds > 0 ? ` • Rodando há ${formatSecondsAge(runningSeconds)}` : '';
                    const info = `Atualizado em ${formatDateTime(eventItem.updated_at)}${runningText}`;
                    const errorSnippet = eventItem.response_error ? `<div class="queue-insight-item__desc">${toSafeText(shortText(eventItem.response_error, 180))}</div>` : '';

                    const row = document.createElement('div');
                    row.className = statusKey === 'failed' || statusKey === 'stuck'
                        ? 'queue-insight-item queue-insight-item--error'
                        : 'queue-insight-item';
                    row.innerHTML = `
                        <div class="queue-insight-item__title">${toSafeText(title)}</div>
                        <div class="queue-insight-item__desc">${toSafeText(info)}</div>
                        ${errorSnippet}
                    `;
                    eventsBody.appendChild(row);
                }
            }

            function renderRequests() {
                const filtered = latestRequests.filter((requestItem) => {
                    if (activeStatusFilter === 'all') return true;
                    return normalizeRequestStatus(requestItem, latestRunner, latestStaleMinutes) === activeStatusFilter;
                });

                rowsBody.innerHTML = '';
                if (!filtered.length) {
                    if (activeStatusFilter === 'all') {
                        rowsBody.innerHTML = '<div class="queue-empty">Sem itens.</div>';
                    } else {
                        rowsBody.innerHTML = `<div class="queue-empty">Nenhuma consulta ${toSafeText(STATUS_LABELS[activeStatusFilter] || 'neste status')}.</div>`;
                    }
                    return;
                }

                for (const requestItem of filtered) {
                    const statusKey = normalizeRequestStatus(requestItem, latestRunner, latestStaleMinutes);
                    const plates = extractPlates(requestItem.response_payload);
                    const screenshotUrl = extractScreenshotUrl(requestItem.response_payload);
                    const attempts = Math.max(1, toCount(requestItem.attempts));
                    const startedAtText = formatDateTime(requestItem.started_at);
                    const finishedAtText = formatDateTime(requestItem.finished_at);
                    const updatedAtText = formatDateTime(requestItem.updated_at);
                    const startedAtMs = parseDateMs(requestItem.started_at);
                    const finishedAtMs = parseDateMs(requestItem.finished_at);
                    let elapsedText = '—';
                    if (startedAtMs && finishedAtMs && finishedAtMs >= startedAtMs) {
                        elapsedText = formatSecondsAge(Math.floor((finishedAtMs - startedAtMs) / 1000));
                    } else if (startedAtMs && !finishedAtMs) {
                        elapsedText = formatSecondsAge(Math.floor((Date.now() - startedAtMs) / 1000));
                    }
                    const executionText = `Tentativas: ${attempts} • Início: ${startedAtText} • Fim: ${finishedAtText} • Duração: ${elapsedText} • Atualização: ${updatedAtText}`;
                    const platesHtml = plates.length
                        ? plates.map((plate) => `<div class="queue-request__plate">${toSafeText(plate)}</div>`).join('')
                        : '<div class="queue-empty" style="padding: 0;">Nenhuma placa listada.</div>';
                    const screenshotHtml = SHOW_REQUEST_IMAGE && screenshotUrl
                        ? `
                            <div class="queue-request__field">
                                <span class="queue-request__label">Imagem</span>
                                <img class="queue-thumb" src="${toSafeText(screenshotUrl)}" data-image-url="${toSafeText(screenshotUrl)}" alt="Screenshot #${Number(requestItem.id)}">
                            </div>
                        `
                        : '';
                    const errorHtml = requestItem.response_error
                        ? `<div class="queue-request__error">${toSafeText(requestItem.response_error)}</div>`
                        : '';

                    const row = document.createElement('article');
                    row.className = statusKey === 'stuck' ? 'queue-request queue-request--stuck' : 'queue-request';
                    row.innerHTML = `
                        <div class="queue-request__head">
                            <span class="queue-request__id">Req #${Number(requestItem.id)}</span>
                            ${tagHtml(statusKey)}
                        </div>
                        <div class="queue-request__fields">
                            <div class="queue-request__field">
                                <span class="queue-request__label">CPF/CNPJ</span>
                                <span class="queue-request__value">${toSafeText(requestItem.cpf_cgc)}</span>
                            </div>
                            <div class="queue-request__field">
                                <span class="queue-request__label">Nome</span>
                                <span class="queue-request__value">${toSafeText(requestItem.nome || '') || '—'}</span>
                            </div>
                            <div class="queue-request__field">
                                <span class="queue-request__label">Chassi</span>
                                <span class="queue-request__value">${toSafeText(requestItem.chassi || '') || '—'}</span>
                            </div>
                            <div class="queue-request__field">
                                <span class="queue-request__label">Complemento</span>
                                <span class="queue-request__value">${toSafeText(requestItem.numeros || '') || '—'}</span>
                            </div>
                            <div class="queue-request__field">
                                <span class="queue-request__label">Execução</span>
                                <span class="queue-request__value">${toSafeText(executionText)}</span>
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
            }

            async function refreshBatches() {
                try {
                    const resp = await fetch(BATCHES_URL, { headers: { Accept: 'application/json' } });
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
                            <td>${toCount(b.total)}</td>
                            <td>${toCount(b.processed)}</td>
                            <td>${toCount(b.succeeded)}</td>
                            <td>${toCount(b.failed)}</td>
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
                        headers: { Accept: 'application/json' },
                    });
                    const json = await resp.json().catch(() => null);
                    if (!resp.ok || !json?.success) throw new Error(json?.error || `HTTP ${resp.status}`);

                    const batch = json.data?.batch;
                    const runner = json.data?.runner;
                    const runnerStatus = String(json.data?.runner_status || '').toLowerCase();
                    const summaryRaw = json.data?.summary ?? {};
                    const diagnostics = json.data?.diagnostics ?? {};
                    const current = json.data?.current;
                    const requests = Array.isArray(json.data?.requests) ? json.data.requests : [];
                    const events = Array.isArray(json.data?.events) ? json.data.events : [];
                    const recentFailures = Array.isArray(json.data?.recent_failures) ? json.data.recent_failures : [];
                    const latestFailure = json.data?.latest_global_failure ?? null;

                    const staleMinutesRaw = Number(summaryRaw?.stale_minutes);
                    latestStaleMinutes = Number.isFinite(staleMinutesRaw) && staleMinutesRaw > 0
                        ? Math.floor(staleMinutesRaw)
                        : DEFAULT_STALE_MINUTES;
                    latestRunner = runner || null;
                    latestRequests = requests;
                    latestSummary = resolveSummary(summaryRaw, latestRequests, latestRunner, latestStaleMinutes);
                    latestDiagnostics = diagnostics;
                    latestRecentFailures = recentFailures;
                    latestGlobalFailure = latestFailure;
                    latestEvents = events;

                    batchPill.textContent = `Batch: #${batch?.id ?? currentBatchId}`;

                    const runnerLabel = runnerStatus === 'stuck'
                        ? 'travado'
                        : (runnerStatus === 'running'
                            ? 'rodando'
                            : (isRunnerStuck(runner, latestStaleMinutes)
                                ? 'travado'
                                : (Number(runner?.is_running) === 1 ? 'rodando' : 'ocioso')));
                    runnerPill.textContent = `Runner: ${runnerLabel}${runner?.current_request_id ? ' (#' + runner.current_request_id + ')' : ''}`;
                    runnerPill.classList.toggle('queue-pill--danger', runnerLabel === 'travado');

                    const total = toCount(batch?.total);
                    const processed = toCount(batch?.processed);
                    const pct = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 0;
                    progressFill.style.width = `${pct}%`;

                    if (latestSummary.stuck > 0 || runnerLabel === 'travado') {
                        statusText.textContent = `Atenção: existem consultas travadas (limite de ${latestStaleMinutes} min sem avanço).`;
                    } else if (batch?.status === 'completed') {
                        statusText.textContent = 'Batch concluído.';
                    } else if (latestSummary.running > 0) {
                        statusText.textContent = 'Consultas em processamento.';
                    } else if (latestSummary.pending > 0) {
                        statusText.textContent = 'Batch aguardando processamento.';
                    } else {
                        statusText.textContent = 'Status atualizado.';
                    }

                    countsText.textContent =
                        `Total ${latestSummary.total} • Rodando ${latestSummary.running} • Travadas ${latestSummary.stuck} • Pendentes ${latestSummary.pending} • Completas ${latestSummary.completed} • Falhas ${latestSummary.failed}`;
                    currentText.textContent = current
                        ? `Atual: #${current.id} • ${current.cpf_cgc} • ${current.chassi}`
                        : 'Atual: —';

                    updateFilterCounts(latestSummary);
                    renderDiagnostics();
                    renderRecentFailures();
                    renderEvents();
                    renderRequests();
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

            setActiveFilter('all');

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

            statusFilters.addEventListener('click', (event) => {
                const button = event.target.closest('[data-filter-status]');
                if (!button) return;
                const key = String(button.getAttribute('data-filter-status') || 'all');
                setActiveFilter(key);
                renderRequests();
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
